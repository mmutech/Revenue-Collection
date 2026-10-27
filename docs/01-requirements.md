# Revenue Collection & Account Treatment Management System

## 1. System purpose and scope

The system is an internal financial operations platform for an organisation that collects revenue through AES, Direct Pay, Cash, POS and Cheque channels. It controls the full lifecycle:

1. Payment capture at an approved channel.
2. Receipt issuance and cashier shift balancing.
3. Daily collection batching and clearing verification.
4. Bank lodgement with supporting evidence.
5. Treasury confirmation.
6. Reconciliation, discrepancy management and management reporting.
7. Account treatment requests with configurable review and approval.

The system is **not** a core banking system or a payment gateway. It records, verifies and reconciles transactions that happen in external channels. No statutory accounting rules, live bank APIs or gateway integrations are invented. Integration points are represented by external references, settlement references and evidence uploads.

## 2. Business objectives

| ID | Objective | How the system satisfies it |
|----|-----------|-----------------------------|
| BO-01 | Capture revenue from approved channels | Channel-configurable payment capture with server-side validation |
| BO-02 | Unique receipting and references | Locked document sequence table, unique constraints |
| BO-03 | Daily cashier accountability | Shifts, expected/counted cash, daily collection batches |
| BO-04 | Independent clearing verification | Clearing queue, segregation-of-duties checks, verification records |
| BO-05 | Controlled bank lodgement | Batch allocation, over-allocation prevention, partial lodgements, evidence upload |
| BO-06 | Treasury confirmation | Treasury workspace, confirmation references, variance records |
| BO-07 | Reconciliation and exception tracking | Reconciliation runs, reconciliation items, discrepancy register |
| BO-08 | Financial traceability | Immutable audit log, workflow transition history, payment adjustments |
| BO-09 | Account treatment governance | Configurable treatment types, reasons, workflows, approval records |
| BO-10 | Management visibility | Role dashboards, KPIs, filterable reports, queued exports |
| BO-11 | Security and segregation of duties | Spatie permissions, policies, branch scoping, self-approval blocks |
| BO-12 | Configurability | System settings, channel field rules, approval workflows and thresholds |

## 3. Actors and responsibilities

| Role | Core responsibilities | Financial approval capability |
|------|----------------------|-------------------------------|
| Cashier | Capture payments, issue/reprint receipts, open/close shift, create and submit daily batch | Submit only |
| Clearing Cashier | Verify batches, record discrepancies, return batches | Verify; cannot approve own submission |
| Bank Officer | Create lodgements, allocate approved batches, upload evidence, submit to treasury | Lodgement submission only |
| Treasury Officer | Confirm deposits, record confirmation references, reject/return, escalate | Treasury confirmation |
| Supervisor / Manager | Monitor branches, resolve discrepancies, approve exceptions, close reconciliations | Exception and closure approvals |
| Administrator | Users, roles, branches, channels, banks, workflows, settings, audit access | **No automatic financial approval** |
| Auditor | Read-only access to transactions, audit trail and reports | None |

Sensitive permissions are separately granted. The Administrator role deliberately excludes payment reversal, clearing verification, treasury confirmation and lodgement approval.

## 4. Functional requirements

### FR-01 Channel management
- Channels are database records with activation status and required-field configuration.
- Supported codes: `aes`, `direct_pay`, `cash`, `pos`, `cheque`.
- Channel-specific fields: external reference, bank, terminal, cheque details, settlement date.
- Inactive channels cannot accept new payments.
- Duplicate transaction references and duplicate idempotency keys are rejected.

### FR-02 Payment capture
- Capture payer, amount, currency, payment date, branch, collection point and channel.
- Generate a globally unique transaction reference.
- Generate a receipt when the channel requires one (always for cash).
- Record the cashier and shift.
- Accept an idempotency key from integrations; repeat submissions return the original payment.
- Cheque payments are never treated as verified funds until clearance is recorded.

### FR-03 Cashier operations
- A cashier may hold at most one open shift.
- Shift expected cash is derived from received cash payments attached to the shift.
- Closing a shift records counted cash and variance.
- A cashier can create a daily batch from unbatched payments and submit it for clearing.
- Receipts can be previewed, printed and reprinted; reprints are audited and counted.

### FR-04 Clearing verification
- Clearing cashiers see submitted batches within their branch scope.
- Verification compares expected total against counted/declared total.
- A variance requires an explanation and creates a discrepancy.
- Verify, return-for-correction and reject decisions are recorded.
- A returned batch can be corrected and resubmitted.
- Approval for lodgement is a separate permission/stage and cannot be performed by the cashier who submitted the batch. By default, the verifier cannot also approve; this is configurable.

### FR-05 Bank lodgement
- Only approved/partially lodged batches are allocatable.
- A lodgement records bank, branch, deposit slip number, date, amount, depositor and evidence.
- One lodgement may contain several batches; one batch may be lodged across several lodgements.
- Allocated amount may not exceed the batch approved total.
- Deposit slip numbers are unique per bank.
- Lodgement submission requires evidence when configured.
- Outstanding balance per batch is derived from approved total minus active allocations.

### FR-06 Treasury verification
- Treasury reviews lodgement evidence and compares approved versus deposited amounts.
- Confirmation records a bank confirmation reference and optional variance explanation.
- Confirmed fully lodged batches become reconciled.
- Returned/rejected lodgements release their allocations so the funds can be re-lodged.
- Unresolved differences can be escalated to management.

### FR-07 Reconciliation
- A reconciliation run compares verified payments, batch allocations, active lodgements and treasury confirmations for a period and branch.
- Results are stored as immutable run items with expected, actual and variance amounts.
- Unmatched items create discrepancy records.
- Runs are auditable and can be re-run; previous runs are retained.

### FR-08 Account treatment
- Configurable types, reasons, required documents, approval workflow and assigned officer.
- Requests can link to a payment, batch, lodgement or reconciliation record.
- Approval stages are generated from the configured workflow.
- Self-approval is blocked.
- Returned requests can be corrected and resubmitted; rejected/cancelled/completed are terminal.
- Documents are private and access-controlled.
- Case age and stage duration are reportable.

### FR-09 Reporting
- Role-aware dashboards, 25+ report definitions, filters, CSV/XLSX/PDF exports.
- Exports are queued; the resulting file is downloadable only by authorised users.
- All exports record actor, filters, format and timestamp.

### FR-10 Notifications
- Database notifications for submission, return, approval, lodgement due, treasury action, discrepancies and treatment actions.
- Optional mail channel controlled by configuration.
- Scheduled overdue lodgement alerts.

### FR-11 Audit and governance
- Immutable audit trail for create/update/delete on financial models plus explicit events (exports, downloads, logins, reversals).
- Workflow transitions record from-state, to-state, actor, reason and timestamp.
- Approved financial records are never deleted; corrections use reversal/adjustment records.
- Uploaded evidence is stored on a private disk and served through authorised routes.

## 5. Non-functional requirements

| Area | Requirement |
|------|-------------|
| Security | CSRF, secure cookies, session regeneration, rate-limited login, password hashing, policies on sensitive operations |
| Integrity | DB transactions, row locks, unique constraints, decimal money, server-side allocation checks |
| Performance | Indexed transaction references, dates, branch/channel/cashier/status; pagination; aggregate queries |
| Reliability | Queued jobs, retries, failed-job handling, scheduled tasks, database queue by default |
| Auditability | Append-only audit and workflow history |
| Accessibility | Keyboard-operable controls, visible focus, contrast-checked palette, semantic tables |
| Responsiveness | Desktop-first, usable at tablet and mobile widths |
| Maintainability | Enums, services, actions, policies, events, form objects, tests; thin Livewire components |

## 6. Out of scope

- Live bank APIs, payment gateway SDKs, card acquiring integrations.
- General ledger postings, tax computations and statutory financial statements.
- Customer self-service portal.
- SMS gateway integration (notification channel is configurable but not implemented).
- Multi-currency FX revaluation; currency codes are stored, but one functional currency is assumed per deployment.

## 7. Acceptance criteria (summary)

1. A cashier can capture all five payment types with correct conditional validation.
2. Cash receipts are numbered uniquely under concurrent load.
3. A batch cannot be verified by its submitter and cannot be approved by its verifier (default configuration).
4. A batch cannot be allocated beyond its approved total, including concurrent requests.
5. A cheque cannot be verified before clearance.
6. Treasury cannot confirm more than the deposited amount without a recorded variance.
7. Every status change creates a workflow transition and audit entry.
8. An administrator without financial permissions cannot approve a payment, batch, lodgement or treatment.
9. All reports respect branch scope and export permissions.
10. Automated tests cover the happy path, rejection paths, concurrency guards and authorisation.
