# Workflow States and Permitted Transitions

All transitions are enforced by `HasWorkflow::transitionTo()`. Status fields are guarded and cannot be changed by mass assignment. Every transition writes an append-only `workflow_transitions` row with actor and optional reason.

## 1. Payment

```mermaid
stateDiagram-v2
    [*] --> Pending: integration accepted
    Pending --> Received: funds recorded
    Pending --> Cancelled
    Received --> Verified: verification / cheque cleared
    Received --> Rejected
    Received --> Cancelled
    Verified --> Reversed: approved reversal
    Received --> Reversed: approved same-day reversal
    Rejected --> [*]
    Cancelled --> [*]
    Reversed --> [*]
```

| From | To | Actor / trigger |
|------|----|-----------------|
| Pending | Received | Payment service on capture |
| Pending/Received | Cancelled | Cashier with `payments.cancel`, before batching |
| Received | Verified | Clearing verification or cheque clearance |
| Received | Rejected | Clearing/treasury rejection with reason |
| Received/Verified | Reversed | Approved `payment_adjustments` reversal; blocked once lodged |

Cash payments are `Received` immediately. Cheques carry `cheque_status` (`received → pending_clearance → cleared | dishonoured | cancelled`) and only become `Verified` when `cleared` is recorded.

## 2. Collection batch

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Submitted: cashier submits
    Submitted --> PendingVerification: queued
    PendingVerification --> Verified: clearing cashier verifies
    PendingVerification --> ReturnedForCorrection: returned with reason
    ReturnedForCorrection --> Submitted: corrected & resubmitted
    Verified --> ApprovedForLodgement: separate approver
    ApprovedForLodgement --> PartiallyLodged: lodgement < total
    ApprovedForLodgement --> FullyLodged: lodgement = total
    PartiallyLodged --> FullyLodged: remaining lodged
    FullyLodged --> TreasuryReview: lodgement submitted to treasury
    TreasuryReview --> Reconciled: treasury confirms
    TreasuryReview --> PartiallyLodged: one lodgement returned
    Reconciled --> Closed: manager closes
```

| From | To | Permission | Note |
|------|----|------------|------|
| Draft | Submitted | `batches.submit` | Owner cashier; batch must have items |
| Submitted | PendingVerification | system | Automatic queue state |
| PendingVerification | Verified | `batches.verify` | Verifier ≠ submitter |
| PendingVerification | ReturnedForCorrection | `batches.verify` | Reason mandatory |
| ReturnedForCorrection | Submitted | `batches.submit` | Cashier corrects batch |
| Verified | ApprovedForLodgement | `batches.approve_lodgement` | Default: approver ≠ verifier |
| ApprovedForLodgement | Partially/FullyLodged | system | Driven by active allocations |
| FullyLodged | TreasuryReview | system | On lodgement submission |
| TreasuryReview | Reconciled | `treasury.confirm` | Fully confirmed |
| Reconciled | Closed | `batches.close` | Optional period close |

## 3. Cheque lifecycle

```mermaid
stateDiagram-v2
    [*] --> Received
    Received --> PendingClearance
    PendingClearance --> Cleared
    PendingClearance --> Dishonoured
    Received --> Cancelled
    PendingClearance --> Cancelled
    Cleared --> [*]
    Dishonoured --> [*]
    Cancelled --> [*]
```

A dishonoured cheque creates a discrepancy and cannot be batched as verified funds. Clearance is recorded by a user with `payments.cheque_clear`.

## 4. Bank lodgement

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Submitted
    Submitted --> UnderTreasuryReview
    UnderTreasuryReview --> Verified
    UnderTreasuryReview --> Returned
    UnderTreasuryReview --> Rejected
    Returned --> Draft: correct and resubmit
    Verified --> Reconciled
    Rejected --> [*]
    Reconciled --> Closed
```

| From | To | Permission |
|------|----|------------|
| Draft | Submitted | `lodgements.submit` |
| Submitted | UnderTreasuryReview | system |
| UnderTreasuryReview | Verified | `treasury.confirm` |
| UnderTreasuryReview | Returned | `treasury.confirm` / `lodgements.return` |
| UnderTreasuryReview | Rejected | `treasury.confirm` |
| Returned | Draft | `lodgements.create` (owner) |
| Verified | Reconciled | system |
| Reconciled | Closed | `reconciliation.close` |

Returning or rejecting a lodgement releases active allocations and recalculates batch lodged status.

## 5. Discrepancy

```mermaid
stateDiagram-v2
    [*] --> Open
    Open --> UnderInvestigation
    Open --> AwaitingEvidence
    Open --> Escalated
    Open --> Resolved
    UnderInvestigation --> AwaitingEvidence
    UnderInvestigation --> Escalated
    UnderInvestigation --> Resolved
    AwaitingEvidence --> UnderInvestigation
    AwaitingEvidence --> Resolved
    AwaitingEvidence --> Escalated
    Escalated --> UnderInvestigation
    Escalated --> Resolved
    Escalated --> Closed
    Resolved --> Closed
    Closed --> [*]
```

Resolution requires a resolution note and actor. Escalation notifies managers.

## 6. Account treatment

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Submitted
    Submitted --> UnderReview
    UnderReview --> Approved
    UnderReview --> Rejected
    UnderReview --> Returned
    Returned --> Submitted: resubmit
    Approved --> Completed
    Draft --> Cancelled
    Submitted --> Cancelled
    UnderReview --> Cancelled
    Returned --> Cancelled
    Approved --> Cancelled
    Rejected --> [*]
    Completed --> [*]
    Cancelled --> [*]
```

Approval progresses through configured stages. Each stage can require multiple approvals; stage order is enforced. A requester cannot approve their own request. Final approval sets `approved`; operational completion is a separate action (`treatment.complete`).

## 7. Reconciliation run

```mermaid
stateDiagram-v2
    [*] --> Pending
    Pending --> Running
    Running --> Completed
    Running --> Failed
    Failed --> Running: retry
    Completed --> [*]
```

Runs are immutable after completion. Re-running creates a new run.

## 8. Cashier shift

```mermaid
stateDiagram-v2
    [*] --> Open
    Open --> Closed: counted cash recorded
    Closed --> [*]
```

## 9. Approval decisions (generic)

`pending → approved | rejected | returned`. Approval records are append-only; a returned stage causes the subject to return to its owner.
