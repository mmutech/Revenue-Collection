# Assumptions and Resolved Ambiguities

The workflow diagram referenced in the brief was not available in the workspace. The implementation therefore follows the six-stage narrative in the brief and records every assumption below. All policy-like values live in configuration so they can be changed without code edits.

## 1. Organisational assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| A1 | Single or multi-entity organisation | Single legal entity, multiple branches |
| A2 | Functional currency | NGN by default, configurable in `system_settings`; payments store their currency code |
| A3 | Branch scope | Users have one primary branch and optional additional branches; `access_all_branches` grants group-wide visibility |
| A4 | Working/business date | Calendar date of payment; no fiscal calendar or period close is enforced |
| A5 | Multiple shifts per day | Allowed sequentially; only one open shift per cashier at a time |
| A6 | Collection point types | Free-text/configurable; no separate terminal master is required |
| A7 | Account vs customer | Customers own accounts; account treatment targets an `account`, optional link to a customer |

## 2. Payment assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| P1 | Receipt required for every channel | Cash always requires a receipt. Other channels follow `payment_channels.requires_receipt`, default false, but a receipt can still be issued if configured |
| P2 | When funds are "received" | AES/Direct Pay/POS/Cash captured records are `received` (subject to clearing). Cheques are `received` but flagged pending clearance and are excluded from verified totals |
| P3 | Duplicate external references | Rejected when the same channel + external reference already exists and the existing payment is not cancelled/reversed. Enforced in the service and indexed |
| P4 | Idempotency | Integrations must supply `idempotency_key`; a repeat key returns the existing payment rather than creating a new one |
| P5 | Back-dating | Allowed for cashier capture; date cannot be in the future by default (`payments.allow_future_date=false`) |
| P6 | Reversal policy | Reversal requires a reason and a second approver when `payments.require_reversal_approval=true`. Blocked if the payment is in a submitted/approved/lodged batch |
| P7 | Partial refunds | Not implemented; corrections use a full reversal plus a new payment |

## 3. Clearing assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| C1 | Who approves for lodgement | Clearing cashier role may hold `batches.verify`, but `batches.approve_lodgement` is separately permissioned. Default requires a different user from the verifier |
| C2 | Can a batch be verified with a shortage? | Yes, if an explanation is recorded; a discrepancy is created automatically. Verification with variance is an explicit accounting decision, not a silent pass |
| C3 | Returned batch edits | Items can be added/removed only while `draft` or `returned_for_correction`; totals are recomputed and resubmitted |
| C4 | Batch scope | One batch per cashier per business date is the default; the service prevents duplicate active batches for the same shift/date |
| C5 | Cash counted vs declared | `declared_total` is the cashier's declared total; `counted_total` is the clearing cashier's physical count. Variance = counted − expected |

## 4. Lodgement assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| L1 | Batch-level or payment-level allocation | Batch-level, matching the workflow's "allocate approved collection batches" |
| L2 | Partial deposits | Supported; outstanding remains on the batch and is visible in reports |
| L3 | Deposit slip uniqueness | Unique per bank. Branches share a bank, so the same slip number cannot be reused for that bank |
| L4 | Evidence mandatory | Configurable (`lodgements.require_document`, default true) |
| L5 | Lodgement creator | Bank Officer role; the creator cannot confirm treasury verification |
| L6 | Difference handling | Treasury may confirm with a variance only when notes are provided; otherwise the lodgement is returned |
| L7 | Returned/rejected allocations | Released immediately so funds can be re-lodged; the lodgement history is retained |

## 5. Reconciliation assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| R1 | Bank statement import | Not implemented. Reconciliation compares internal records: verified payments → batch items → active lodgements → treasury confirmations |
| R2 | Matching rule | A payment is matched when it belongs to a batch that is fully lodged and treasury-confirmed within the run period; otherwise it is unmatched |
| R3 | Run scope | Period + optional branch; no automatic period locking |
| R4 | Discrepancy creation | Unmatched verified payments and unmatched confirmed lodgements create one open discrepancy each per run |
| R5 | Re-running | Creates a new run; prior runs are never deleted or mutated |

## 6. Account treatment assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| T1 | Meaning of "treatment" | A governed adjustment/instruction on a customer account (e.g. write-off, credit adjustment, refund instruction). No ledger postings are made by this system |
| T2 | Approval stages | Configurable per treatment type through `approval_workflows`; a default two-stage workflow is seeded |
| T3 | Required documents | Configurable per treatment type; document upload is available for all types |
| T4 | Self-approval | Always blocked; the requester cannot act on their own request |
| T5 | Completion | Approval and completion are separate; completion records operational execution and is permissioned separately |
| T6 | Financial linkage | Optional links to payment/batch/lodgement/reconciliation; no automatic amount mutation of linked records |

## 7. Reporting assumptions

| # | Ambiguity | Assumption |
|---|-----------|------------|
| X1 | Report formats | CSV and XLSX via PhpSpreadsheet; PDF via Dompdf |
| X2 | Export size | All exports run through the queue; a notification and download record are produced |
| X3 | Branch scope on exports | Same scope as the viewing user; group-wide only with `access_all_branches` |
| X4 | Time zone | Application timezone (UTC by default); dates are stored as dates, timestamps as UTC |
| X5 | KPI "today" | Current application date, not user-selected period, unless the dashboard has an explicit period selector |

## 8. Deliberately not invented

- Statutory revenue recognition rules, tax treatment, or general-ledger postings.
- Live bank confirmation APIs, payment gateway APIs or card settlement files.
- SMS/WhatsApp delivery.
- Interest, penalties or FX gain/loss calculations.
- Customer-facing statements or self-service.

These integrations can be added behind interfaces without changing the core workflow.

## 9. Configurable settings (seeded defaults)

| Key | Default | Purpose |
|-----|---------|---------|
| `organisation.name` | Revenue Collection Office | Display name |
| `organisation.currency` | NGN | Default currency |
| `organisation.timezone` | UTC | Display timezone |
| `payments.allow_future_date` | false | Reject future payment dates |
| `payments.require_reversal_approval` | true | Dual control for reversals |
| `collections.require_separate_lodgement_approver` | true | Verifier ≠ lodgement approver |
| `collections.variance_requires_notes` | true | Explanation on variance |
| `lodgements.require_document` | true | Evidence required to submit |
| `lodgements.overdue_days` | 3 | Days before a lodgement is overdue |
| `treasury.variance_requires_notes` | true | Difference explanation |
| `treatment.allow_self_approval` | false | Fixed to false by policy even if changed |
| `notifications.mail_enabled` | false | Email notifications |
| `exports.retention_days` | 30 | Cleanup of generated files |
