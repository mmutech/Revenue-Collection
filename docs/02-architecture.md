# System Architecture

## 1. Technology baseline

| Layer | Choice |
|-------|--------|
| Runtime | PHP 8.5 |
| Framework | Laravel 13 |
| UI | Livewire 4 class components, Blade, Tailwind CSS 4, Alpine (bundled by Livewire) |
| Charts | Chart.js 4 |
| Database | MySQL 9.7 (compatible with MySQL 8+) |
| Auth | Laravel session authentication, custom Livewire login, password broker |
| Authorisation | Spatie Laravel Permission 8 + Laravel policies + branch scope |
| Exports | Maatwebsite Excel 4 (CSV/XLSX), Dompdf (PDF) |
| Queue | Database driver, retry/backoff, scheduled worker |
| Tests | PHPUnit 12 feature/unit tests with Livewire testing |
| Assets | Vite 8 |

## 2. Logical architecture

```
┌──────────────────────────────────────────────────────────────────┐
│ Presentation                                                     │
│  Livewire pages (dashboards, registers, workspaces, admin)       │
│  Blade layout/components, Tailwind, Chart.js, print styles       │
└───────────────────────────────┬──────────────────────────────────┘
                                │ calls (validated DTO/arrays)
┌───────────────────────────────▼──────────────────────────────────┐
│ Application                                                      │
│  Services: Payment, Shift, CollectionBatch, Lodgement,           │
│            Reconciliation, AccountTreatment, Reporting, Audit    │
│  Actions: focused single operations (reverse, confirm, etc.)     │
│  Events/Listeners, Notifications, Jobs, Policies                 │
└───────────────────────────────┬──────────────────────────────────┘
                                │ Eloquent / query builder
┌───────────────────────────────▼──────────────────────────────────┐
│ Domain & persistence                                             │
│  Enums (statuses, decisions, discrepancy types)                  │
│  Models + relationships + workflow trait + audit trait           │
│  MySQL schema with FKs, unique constraints, indexes, decimals    │
└──────────────────────────────────────────────────────────────────┘
```

Business rules do not live in Blade or Livewire. Livewire components validate input with Form Requests/Form objects, authorise, call a service or action, and render. Services own transactions, locking, invariants and event dispatch.

## 3. Module breakdown

| Module | Key classes | Responsibility |
|--------|-------------|----------------|
| Identity & access | `User`, `Branch`, roles/permissions, policies | Authentication, branch scope, RBAC |
| Reference data | `PaymentChannel`, `Bank`, `BankBranch`, `CollectionPoint`, `SystemSetting` | Configurable master data |
| Payments | `PaymentService`, `Payment`, `PaymentReceipt`, `PaymentAdjustment` | Capture, receipt, reversal/adjustment |
| Cashier | `CashierShiftService`, `CashierShift` | Shift lifecycle and cash balancing |
| Collections | `CollectionBatchService`, `CollectionBatch`, `CollectionVerification`, `CollectionDiscrepancy` | Daily batching, clearing, exceptions |
| Lodgements | `LodgementService`, `BankLodgement`, `BankLodgementItem`, `BankLodgementDocument` | Bank deposit allocation and evidence |
| Treasury | `TreasuryVerification`, `LodgementService::confirm` | Confirmation and variance |
| Reconciliation | `ReconciliationService`, `ReconciliationRun`, `ReconciliationItem` | Period matching and exception generation |
| Account treatment | `AccountTreatmentService`, types, reasons, requests, approvals, documents | Configurable account treatment |
| Governance | `ApprovalWorkflow`, stages/records, `AuditLogger`, `WorkflowTransition` | Configurable approvals and traceability |
| Reporting | `ReportRegistry`, `ReportService`, `ExportReportJob` | Definitions, filters, exports |
| Notifications | Notification classes, scheduler commands | Workflow alerts and escalation |

## 4. Directory map

```
app/
  Actions/                     Single-purpose state changes
  Enums/                       WorkflowStatus implementations
  Events/  Listeners/
  Exceptions/                  Domain exceptions
  Exports/                     Excel/PDF export definitions
  Http/Controllers/            Auth, downloads, print, export downloads
  Http/Middleware/             EnsureUserIsActive, security headers
  Http/Requests/               Form validation
  Jobs/                        Export and scheduled work
  Livewire/
    AccountTreatment/ Admin/ Auth/ Collections/ Dashboard/
    Lodgements/ Notifications/ Payments/ Reconciliation/ Reports/
    Shifts/ Treasury/ Users/
  Models/  Models/Concerns/
  Notifications/
  Policies/
  Providers/
  Services/
  Support/
database/
  factories/ migrations/ seeders/
docs/
resources/
  css/ js/ views/
routes/
  web.php console.php
tests/
  Feature/ Unit/
```

## 5. Request lifecycle (example: verify collection batch)

1. Clearing cashier opens `/collections/queue`.
2. Livewire component authorises `batches.verify` and branch scope.
3. `verify()` validates counted total, notes and decision.
4. Component calls `CollectionBatchService::verify($batch, $actor, $decision, $data)`.
5. Service opens a transaction, reloads the batch `lockForUpdate()`, re-checks status and segregation, writes `collection_verifications`, creates discrepancies for variance, transitions status, writes `workflow_transitions` and audit log, commits.
6. Service dispatches `CollectionBatchVerified` / `CollectionBatchReturned`.
7. Listener sends database notifications to cashier and supervisors.
8. Livewire re-renders with a toast.

## 6. Security architecture

- Every route (except auth and signed document routes) uses `auth`, `verified` optional, and `active` middleware.
- Policies are the authorisation boundary; services call `Gate::forUser($actor)->authorize(...)` so queued/CLI calls cannot bypass policy.
- Branch scope is applied both in policy checks and in list queries (`scopeVisibleTo`).
- Sensitive approvals check actor identity against the submitter/verifier.
- File downloads use a controller that authorises the related record and streams from the private disk; no public URLs.
- Login is rate limited; sessions regenerate on login/logout; cookies are `httponly`, `samesite=lax`, secure in production.
- Audit logs are append-only at the application layer; deployment grants the app DB user no `DELETE` on audit tables if required.
- Security headers middleware adds `X-Frame-Options`, `X-Content-Type-Options`, referrer policy and a conservative CSP for app pages.

## 7. Financial integrity controls

| Risk | Control |
|------|---------|
| Duplicate payment | Unique `reference`, unique `idempotency_key`, service check on channel + external reference |
| Duplicate receipt | Unique receipt number generated under row lock |
| Concurrent over-allocation | Batch and lodgement rows locked; sum of active allocations re-checked in transaction |
| Duplicate lodgement | Unique bank + deposit slip number |
| Unauthorised status edit | Status fields are not mass assignable; only services call `transitionTo` |
| Deleting history | No delete routes; policies deny deletes; reversals create adjustment records |
| Self-approval | Submit/verify/approve/treasury actors compared; configurable settings |
| Amount tampering | Server-side decimal validation; snapshots set inside transaction |
| Silent edits | Audit trait logs old/new values; approved batches locked from edits |

## 8. Scalability and operations

- Queue workers process exports and notifications.
- Scheduler triggers overdue lodgement detection and export cleanup.
- Database indexes target the primary filter dimensions: date, branch, channel, cashier, status, references.
- List screens paginate; dashboards aggregate in SQL, not PHP.
- Reconciliation is chunked by date range and can be queued for large periods.
- Storage: private `local` disk by default; S3-compatible disk can be configured without code changes.

## 9. Key architectural decisions

| Decision | Rationale |
|----------|-----------|
| Status strings + PHP enums | Readable in DB, type-safe in code, explicit transitions |
| Workflow transition table | Enables stage-duration KPIs and a complete history independent of audit JSON |
| Amount snapshots on batch items/allocations | Historical reporting remains stable if a payment is later reversed |
| Batch-level lodgement allocation | Matches the workflow output; payment-level allocation would complicate partial deposits |
| Derived outstanding balances | Avoids denormalised drift; explicitly controlled snapshots only on submission/approval |
| Separate account treatment module | Account treatment is not assumed to equal payment collection |
| Database queue | No Redis dependency; can switch via `QUEUE_CONNECTION` |
| Server-rendered Livewire pages | Fast delivery of a secure internal back-office without a separate API |
