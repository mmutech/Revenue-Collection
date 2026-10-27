<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\TreatmentStatus;
use App\Events\TreatmentDecisionRecorded;
use App\Events\TreatmentSubmitted;
use App\Exceptions\FinancialRuleViolation;
use App\Models\Account;
use App\Models\AccountTreatmentApproval;
use App\Models\AccountTreatmentDocument;
use App\Models\AccountTreatmentReason;
use App\Models\AccountTreatmentRequest;
use App\Models\AccountTreatmentType;
use App\Models\ApprovalRecord;
use App\Models\ApprovalWorkflow;
use App\Models\User;
use App\Support\RoleRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountTreatmentService
{
    public function __construct(private readonly DocumentNumberService $numbers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): AccountTreatmentRequest
    {
        Gate::forUser($actor)->authorize('create', AccountTreatmentRequest::class);

        $account = Account::query()->findOrFail($data['account_id']);
        $type = AccountTreatmentType::query()->where('is_active', true)->findOrFail($data['account_treatment_type_id']);

        $branchId = $account->branch_id ?? $actor->branch_id;
        if (! $actor->canAccessBranch($branchId)) {
            throw new AuthorizationException('You are not authorised for the account branch.');
        }

        if (! empty($data['account_treatment_reason_id'])) {
            $validReason = AccountTreatmentReason::query()
                ->where('account_treatment_type_id', $type->id)
                ->whereKey($data['account_treatment_reason_id'])
                ->where('is_active', true)
                ->exists();

            if (! $validReason) {
                throw ValidationException::withMessages([
                    'account_treatment_reason_id' => 'The selected reason does not belong to this treatment type.',
                ]);
            }
        }

        $request = AccountTreatmentRequest::query()->create([
            'reference' => $this->numbers->treatmentReference(),
            'account_id' => $account->id,
            'branch_id' => $branchId,
            'account_treatment_type_id' => $type->id,
            'account_treatment_reason_id' => $data['account_treatment_reason_id'] ?? null,
            'approval_workflow_id' => $type->approval_workflow_id,
            'requested_by' => $actor->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'amount' => isset($data['amount']) ? round((float) $data['amount'], 2) : null,
            'currency' => $data['currency'] ?? 'NGN',
            'effective_date' => $data['effective_date'] ?? null,
            'description' => $data['description'],
            'payment_id' => $data['payment_id'] ?? null,
            'collection_batch_id' => $data['collection_batch_id'] ?? null,
            'bank_lodgement_id' => $data['bank_lodgement_id'] ?? null,
            'reconciliation_run_id' => $data['reconciliation_run_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $request->transitionTo(TreatmentStatus::Draft, $actor, 'Treatment request created');

        return $request->refresh()->load(['account', 'type', 'reason']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AccountTreatmentRequest $request, User $actor, array $data): AccountTreatmentRequest
    {
        Gate::forUser($actor)->authorize('create', AccountTreatmentRequest::class);

        if ((int) $request->requested_by !== (int) $actor->id && ! $actor->can('treatment.manage')) {
            throw new AuthorizationException('Only the requester can edit this treatment request.');
        }

        return DB::transaction(function () use ($request, $data) {
            /** @var AccountTreatmentRequest $request */
            $request = AccountTreatmentRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! in_array($request->status, [TreatmentStatus::Draft, TreatmentStatus::Returned], true)) {
                throw FinancialRuleViolation::because('Only draft or returned treatment requests can be edited.');
            }

            $request->fill([
                'account_treatment_reason_id' => $data['account_treatment_reason_id'] ?? $request->account_treatment_reason_id,
                'assigned_to' => $data['assigned_to'] ?? $request->assigned_to,
                'amount' => array_key_exists('amount', $data) ? round((float) $data['amount'], 2) : $request->amount,
                'effective_date' => $data['effective_date'] ?? $request->effective_date,
                'description' => $data['description'] ?? $request->description,
            ])->save();

            return $request->refresh();
        });
    }

    public function submit(AccountTreatmentRequest $request, User $actor): AccountTreatmentRequest
    {
        Gate::forUser($actor)->authorize('create', AccountTreatmentRequest::class);

        if ((int) $request->requested_by !== (int) $actor->id && ! $actor->can('treatment.manage')) {
            throw new AuthorizationException('Only the requester can submit this treatment request.');
        }

        $request = DB::transaction(function () use ($request, $actor) {
            /** @var AccountTreatmentRequest $request */
            $request = AccountTreatmentRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! in_array($request->status, [TreatmentStatus::Draft, TreatmentStatus::Returned], true)) {
                throw FinancialRuleViolation::because('Only draft or returned treatment requests can be submitted.');
            }

            $this->validateRequiredDocuments($request);

            $type = $request->type;
            $workflow = $type->approvalWorkflow;

            if ($type->requires_approval) {
                if (! $workflow || ! $workflow->is_active) {
                    throw FinancialRuleViolation::because('This treatment type requires an active approval workflow.');
                }

                $firstStage = $workflow->stages()->orderBy('sequence')->first();
                if (! $firstStage) {
                    throw FinancialRuleViolation::because('The configured approval workflow has no stages.');
                }

                $request->fill([
                    'submitted_at' => now(),
                    'current_stage' => $firstStage->sequence,
                    'decision_notes' => null,
                ])->save();

                $request->transitionTo(TreatmentStatus::Submitted, $actor, 'Submitted for approval');
                $request->transitionTo(TreatmentStatus::UnderReview, $actor, 'Stage '.$firstStage->sequence.': '.$firstStage->name);

                return $request->refresh();
            }

            $request->fill(['submitted_at' => now()])->save();
            $request->transitionTo(TreatmentStatus::Submitted, $actor, 'Submitted');
            $request->transitionTo(TreatmentStatus::UnderReview, $actor, 'Reviewed');
            $request->fill(['approved_at' => now()])->save();
            $request->transitionTo(TreatmentStatus::Approved, $actor, 'No approval workflow required');

            return $request->refresh();
        });

        TreatmentSubmitted::dispatch($request);

        return $request;
    }

    public function decide(
        AccountTreatmentRequest $request,
        User $actor,
        ApprovalDecision $decision,
        ?string $comments = null,
    ): AccountTreatmentRequest {
        if (! in_array($decision, [ApprovalDecision::Approved, ApprovalDecision::Rejected, ApprovalDecision::Returned], true)) {
            throw ValidationException::withMessages(['decision' => 'Select a valid decision.']);
        }

        Gate::forUser($actor)->authorize(
            $decision === ApprovalDecision::Approved ? 'approve' : 'review',
            $request
        );

        if ($decision !== ApprovalDecision::Approved && trim((string) $comments) === '') {
            throw ValidationException::withMessages(['comments' => 'Comments are required for this decision.']);
        }

        $request = DB::transaction(function () use ($request, $actor, $decision, $comments) {
            /** @var AccountTreatmentRequest $request */
            $request = AccountTreatmentRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== TreatmentStatus::UnderReview) {
                throw FinancialRuleViolation::because('This request is not awaiting a review decision.');
            }

            if ((int) $request->requested_by === (int) $actor->id) {
                throw FinancialRuleViolation::because('You cannot decide on a treatment request that you raised.');
            }

            $workflow = ApprovalWorkflow::query()->findOrFail($request->approval_workflow_id);
            $stage = $workflow->stages()->where('sequence', $request->current_stage)->firstOrFail();

            if (! $this->actorMayActOnStage($actor, $stage->approver_role, $stage->permission)) {
                throw new AuthorizationException('You are not an approver for the current stage.');
            }

            $alreadyDecided = $request->approvals()
                ->where('stage_sequence', $stage->sequence)
                ->where('approver_id', $actor->id)
                ->where('decided_at', '>=', $request->submitted_at)
                ->exists();

            if ($alreadyDecided) {
                throw FinancialRuleViolation::because('You have already acted on this approval stage.');
            }

            AccountTreatmentApproval::query()->create([
                'account_treatment_request_id' => $request->id,
                'approval_workflow_stage_id' => $stage->id,
                'stage_sequence' => $stage->sequence,
                'stage_name' => $stage->name,
                'approver_role' => $stage->approver_role,
                'approver_id' => $actor->id,
                'decision' => $decision->value,
                'comments' => $comments,
                'decided_at' => now(),
            ]);

            ApprovalRecord::query()->create([
                'approval_workflow_id' => $workflow->id,
                'subject_type' => $request->getMorphClass(),
                'subject_id' => $request->id,
                'approval_workflow_stage_id' => $stage->id,
                'stage_sequence' => $stage->sequence,
                'actor_id' => $actor->id,
                'decision' => $decision->value,
                'comments' => $comments,
                'created_at' => now(),
            ]);

            if ($decision === ApprovalDecision::Returned) {
                $request->fill(['decision_notes' => $comments])->save();
                $request->transitionTo(TreatmentStatus::Returned, $actor, $comments);

                return $request->refresh();
            }

            if ($decision === ApprovalDecision::Rejected) {
                $request->fill([
                    'decision_notes' => $comments,
                    'rejected_at' => now(),
                ])->save();
                $request->transitionTo(TreatmentStatus::Rejected, $actor, $comments);

                return $request->refresh();
            }

            $approvedCount = $request->approvals()
                ->where('stage_sequence', $stage->sequence)
                ->where('decision', ApprovalDecision::Approved->value)
                ->where('decided_at', '>=', $request->submitted_at)
                ->count();

            if ($approvedCount < $stage->required_approvals) {
                return $request->refresh();
            }

            $nextStage = $workflow->stages()
                ->where('sequence', '>', $stage->sequence)
                ->orderBy('sequence')
                ->first();

            if ($nextStage) {
                $request->fill(['current_stage' => $nextStage->sequence])->save();

                return $request->refresh();
            }

            $request->fill(['approved_at' => now()])->save();
            $request->transitionTo(TreatmentStatus::Approved, $actor, 'Approval workflow completed');

            return $request->refresh();
        });

        TreatmentDecisionRecorded::dispatch($request, $decision->value);

        return $request;
    }

    public function complete(AccountTreatmentRequest $request, User $actor, ?string $notes = null): AccountTreatmentRequest
    {
        Gate::forUser($actor)->authorize('complete', $request);

        return DB::transaction(function () use ($request, $actor, $notes) {
            /** @var AccountTreatmentRequest $request */
            $request = AccountTreatmentRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== TreatmentStatus::Approved) {
                throw FinancialRuleViolation::because('Only approved treatment requests can be completed.');
            }

            $request->fill([
                'completed_at' => now(),
                'decision_notes' => $notes ?? $request->decision_notes,
            ])->save();
            $request->transitionTo(TreatmentStatus::Completed, $actor, $notes ?: 'Treatment completed');

            return $request->refresh();
        });
    }

    public function cancel(AccountTreatmentRequest $request, User $actor, string $reason): AccountTreatmentRequest
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A cancellation reason is required.']);
        }

        if ((int) $request->requested_by !== (int) $actor->id && ! $actor->can('treatment.manage')) {
            throw new AuthorizationException('Only the requester or a treatment administrator can cancel this request.');
        }

        return DB::transaction(function () use ($request, $actor, $reason) {
            /** @var AccountTreatmentRequest $request */
            $request = AccountTreatmentRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status->isTerminal()) {
                throw FinancialRuleViolation::because('This treatment request is already closed.');
            }

            $request->fill([
                'cancelled_at' => now(),
                'decision_notes' => $reason,
            ])->save();
            $request->transitionTo(TreatmentStatus::Cancelled, $actor, $reason);

            return $request->refresh();
        });
    }

    public function uploadDocument(
        AccountTreatmentRequest $request,
        User $actor,
        UploadedFile $file,
        ?string $documentType = null,
    ): AccountTreatmentDocument {
        Gate::forUser($actor)->authorize('create', AccountTreatmentRequest::class);

        if (! $actor->canAccessBranch($request->branch_id)) {
            throw new AuthorizationException('You are not authorised for this branch.');
        }

        if ($request->status->isTerminal()) {
            throw FinancialRuleViolation::because('Documents cannot be added to a closed treatment request.');
        }

        $validated = validator(['file' => $file], [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ])->validate();

        $path = $validated['file']->store('treatments/'.$request->id, 'local');

        return AccountTreatmentDocument::query()->create([
            'account_treatment_request_id' => $request->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            'document_type' => $documentType,
            'uploaded_by' => $actor->id,
        ]);
    }

    public function deleteDocument(AccountTreatmentDocument $document, User $actor): void
    {
        Gate::forUser($actor)->authorize('create', AccountTreatmentRequest::class);

        $request = $document->request;

        if (! $actor->canAccessBranch($request->branch_id)) {
            throw new AuthorizationException('You are not authorised for this branch.');
        }

        if (! in_array($request->status, [TreatmentStatus::Draft, TreatmentStatus::Returned], true)) {
            throw FinancialRuleViolation::because('Documents can only be removed before submission.');
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    private function actorMayActOnStage(User $actor, ?string $role, ?string $permission): bool
    {
        if ($actor->hasRole(RoleRegistry::ADMINISTRATOR) || ($role && $actor->hasRole($role))) {
            return true;
        }

        return $permission !== null && $actor->can($permission);
    }

    private function validateRequiredDocuments(AccountTreatmentRequest $request): void
    {
        $required = $request->type->required_document_types ?? [];

        if ($required === []) {
            return;
        }

        $present = $request->documents()->pluck('document_type')->filter()->all();
        $missing = array_diff($required, $present);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'document' => 'Upload the required documents: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
