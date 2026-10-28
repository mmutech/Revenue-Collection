# System Test Cases

This is the system-wide manual regression and acceptance-test catalogue. It is derived from the documented requirements, workflow states, routes and current PHPUnit test inventory. It is **not** a claim that every case below is already automated.

- **P0** — financial integrity, access-control or release-blocking behavior.
- **P1** — primary operational workflow or important recovery path.
- **P2** — secondary display, filtering or administrative behavior.
- Unless a case specifies otherwise, verify both the user-visible result and the persisted database state. For denials/failures, also verify that no unintended financial record, allocation, transition or audit event was written.

## 1. Test environment and actors

Use an isolated test database and controlled clock where date boundaries matter. Prepare distinct users for cashier, clearing cashier, bank officer, treasury officer, supervisor/manager, treatment requester, approver, administrator and auditor. Give users separate branches to test scope. Do not rely on shared production-like demo credentials.

Prepare active and inactive branches, collection points, payment channels, banks and bank branches; treatment types with and without approval workflows; and records in each relevant lifecycle state. Use valid unique external references and deposit slip numbers except in duplicate tests. Store uploaded files on a test/private disk and clean them between cases.

When testing duties that require different actors, ensure submitter, verifier, approver, bank officer and treasury officer are distinct users. Run concurrency tests against the production database engine as well as SQLite; SQLite alone does not demonstrate row-lock behavior.

## 2. Current automated coverage snapshot

The committed PHPUnit suite currently has focused coverage for routing, permission basics, administrator configuration, component resolution and seeded access credentials. It does not yet provide end-to-end automated coverage for most financial workflows.

| Existing suite | Current coverage |
|---|---|
| `RoutingTest` | Welcome page, login/forgot/reset page responses, guest dashboard redirect and no public default-password hint |
| `MissingRoutesTest` | Key route registration and guest redirects for protected routes |
| `ReportsRoutingTest` | Guest redirects for reports/export pages |
| `LivewireMfcPageTest` | Full-page Livewire component resolution |
| `AdministratorAuthorizationTest` | Registered permission behavior for administrator and non-administrator |
| `AdminManagementTest` | Bank/channel/user/role/treatment configuration, branch deactivation, last-admin protection, secret setting audit masking/cache flush, workflow-history protection and access denial |
| `AccessSeederTest` | Seeded account passwords are not the known default and rerunning seeding preserves a changed password |
| Example tests | Framework starter smoke tests; not domain-workflow coverage |

Use the case IDs below to plan future PHPUnit feature tests. Request-reachable behavior should generally be tested at the Livewire/HTTP boundary and assert the resulting database state, transitions, notifications and files where applicable.

## 3. Authentication, profile and access control

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| AUTH-01 | P0 | Sign in with a valid active user's credentials; then sign out. | Session is established, regenerated on authentication, and invalidated on logout; protected routes redirect after logout. |
| AUTH-02 | P0 | Attempt sign-in with an incorrect password and with an inactive user. | Authentication is denied without exposing which credential was wrong; inactive user cannot access protected features. |
| AUTH-03 | P1 | Request a password reset for a registered account and complete with a valid token and confirmed compliant password. | Reset succeeds, new password works, old password fails, and reset token is no longer reusable. |
| AUTH-04 | P1 | Submit a reset with expired/invalid token, mismatched confirmation or a too-short password. | Validation/reset fails and account credentials remain unchanged. |
| AUTH-05 | P1 | Submit repeated login failures from one identity/IP. | Configured throttling limits attempts; no successful session is created by a throttled request. |
| AUTH-06 | P1 | Update profile with allowed data, then submit invalid values or attempt protected attribute changes. | Allowed fields persist; invalid/unauthorised attributes are rejected and password/role/branch are not silently changed. |
| AUTH-07 | P0 | Change password with wrong current password, then with correct current password and invalid/valid replacement. | Wrong current password and invalid replacement are rejected; only a valid confirmed replacement is stored. |
| AUTH-08 | P0 | Access a protected route as guest, inactive user, authenticated user without permission, and authorised active user. | Guest redirects to sign in; inactive/unauthorised access is denied; authorised user sees only permitted content. |
| AUTH-09 | P0 | Attempt to access another branch's payment, batch, lodgement, treatment and export/download using a guessed identifier. | The request is denied/not found per policy and discloses no out-of-scope record data. |
| AUTH-10 | P1 | Mark one notification read and then mark all notifications read; attempt to alter another user's notification. | Own notification state updates; other user's notification remains unchanged and is inaccessible. |

## 4. Administration and reference data

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| ADMIN-01 | P1 | Create and edit branch, collection point, bank and bank branch with valid values. | Values persist; relationships and active flags are correct. |
| ADMIN-02 | P1 | Create an active payment channel with channel-specific required fields; then deactivate it and attempt payment capture. | Configuration persists; inactive channel cannot be used for new payments. |
| ADMIN-03 | P1 | Submit duplicate reference codes, duplicate bank-branch codes within a bank, and malformed required-field configuration. | Validation prevents invalid duplicates/configuration; existing records are unchanged. |
| ADMIN-04 | P1 | Create a user with primary/additional branches and role; deactivate/reactivate it. | User, role and branch assignments persist; inactive account is blocked from operations. |
| ADMIN-05 | P0 | Attempt to deactivate the last active administrator. | Operation fails and the administrator remains active. |
| ADMIN-06 | P0 | Create a custom role using registered permissions and attempt to assign an unregistered/forbidden permission. | Only permitted registered abilities are assigned; access checks follow the resulting role. |
| ADMIN-07 | P1 | Update a public setting and a private/secret setting, read the setting before and after the update, inspect audit history. | New value is effective after cache invalidation; secret plaintext is masked in audit output. |
| ADMIN-08 | P1 | Attempt to delete/deactivate a branch, reference item or role already in use. | Referenced data is retained and deactivated or the operation is rejected; no financial history is deleted. |
| ADMIN-09 | P0 | Change an approval workflow stage that has existing approval history. | Historical stage definition is protected from destructive alteration; existing approvals remain readable. |
| ADMIN-10 | P1 | Seed reference data more than once where historical workflow-stage records exist. | Seeding remains safe and preserves stages with history. |
| ADMIN-11 | P0 | Open admin pages with no relevant permission and open the treatment configuration area with treatment-only permission. | General admin is denied; treatment user sees only the treatment configuration area it is allowed to manage. |

## 5. Payment capture, receipts, cheque, cancellation and reversal

Run the valid capture scenario for each enabled channel (cash, POS, AES, Direct Pay, Cheque), using the configured required fields for that channel.

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| PAY-01 | P0 | Capture valid cash payment for an authorised branch and active shift. | Payment is created with unique reference, correct actor/branch/amount, received status and required receipt. |
| PAY-02 | P1 | Capture valid POS, AES and Direct Pay payments with their required references/settlement details. | Channel-specific values persist; payment reference is unique; receipt behavior follows channel configuration. |
| PAY-03 | P0 | Capture a cheque with valid details. Attempt to batch/verify before clearance; then clear it as authorised clearing user. | Pending cheque is not treated as cleared funds; clearance records actor/status and makes it eligible only according to workflow. |
| PAY-04 | P1 | Record cheque dishonour or cancellation and attempt to include it in a verified-funds batch. | Cheque is excluded from eligible funds; exception/discrepancy behavior is recorded as configured. |
| PAY-05 | P0 | Omit each channel's required field in turn; submit invalid amount, future date when disallowed, inactive channel and out-of-scope branch. | Validation/authorization fails; no payment or receipt is partially created. |
| PAY-06 | P0 | Submit a repeated idempotency key and duplicate active channel/external-reference pair. | Duplicate does not create a second payment; idempotent retry returns/reuses the original result where supported. |
| PAY-07 | P1 | View, print and reprint an authorised receipt; then request another user's/out-of-branch receipt. | Own receipt is available; each reprint is recorded as configured; unauthorised receipt access is denied. |
| PAY-08 | P1 | Cancel an eligible unbatched payment with a reason; repeat without a reason and after batching. | Eligible payment is cancelled with reason/history; invalid or post-batch cancellation is rejected. |
| PAY-09 | P0 | Request reversal with missing reason, disallowed actor or after lodgement; then request a permitted reversal. | Invalid requests fail; valid request creates an adjustment and leaves original payment intact pending decision. |
| PAY-10 | P0 | Have a distinct authorised approver approve a reversal; repeat with requester/self-approver and with rejection. | Independent approval applies the controlled reversal; self-approval is blocked; rejection retains original payment and decision history. |
| PAY-11 | P1 | Filter/search payment register by date, status, channel and branch, including a branch the user cannot access. | Results match supported filters and remain restricted to actor's scope. |
| PAY-12 | P1 | Inspect payment details after capture, cheque decision, cancellation and reversal. | Detail page shows accurate lifecycle state, receipt/adjustment details and actor history without allowing direct status edits. |

## 6. Cashier shifts and collection batches

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| COLL-01 | P0 | Open a shift; attempt to open a second shift for the same cashier; open a permitted shift for another cashier. | First shift opens; duplicate simultaneous shift is blocked; independent cashier can open their own shift. |
| COLL-02 | P1 | Record cash payments during a shift and compare expected total to shift summary. | Expected cash uses eligible shift payments and excludes ineligible/reversed amounts according to business rules. |
| COLL-03 | P1 | Close shift with exact count, overage and shortage (with/without notes). | Shift closes once; expected/counted/variance are correct; required explanation and discrepancy are enforced for variance. |
| COLL-04 | P0 | Create a batch with eligible payments; attempt to add another branch's, already batched, cancelled, reversed, uncleared cheque or otherwise ineligible payment. | Only eligible in-scope unbatched payments are added; invalid additions leave batch unchanged. |
| COLL-05 | P1 | Remove a payment while batch is draft or returned; attempt removal after submission/verification. | Editable batch recalculates totals; protected workflow states prevent item mutation. |
| COLL-06 | P0 | Submit empty batch, submit valid batch and submit a declared total different from expected. | Empty batch is rejected; valid batch snapshots amounts and queues verification; variance follows the configured explanatory control. |
| COLL-07 | P0 | Verify submitted batch as its submitter; then verify as a distinct clearing user with matching count. | Submitter cannot self-verify; authorised independent verifier records decision/count and batch transitions to verified. |
| COLL-08 | P0 | Verify with shortage/overage with and without a reason. | Variance requires explanation when configured, verification record is persisted and discrepancy is raised. |
| COLL-09 | P1 | Return a batch for correction with reason; correct and resubmit; verify it. | Return reason is retained; returned batch becomes editable; resubmission enters verification again with updated total. |
| COLL-10 | P0 | Reject invalid batch decisions, wrong-branch verification and an unapproved actor's attempt to verify. | Operation is denied with no status, count or transition mutation. |
| COLL-11 | P0 | Approve batch as verifier; then approve with a distinct authorised approver. | Self-approval is blocked when configured; separate approver transitions eligible batch to approved-for-lodgement. |
| COLL-12 | P1 | Close a fully reconciled eligible batch and attempt to close a batch with outstanding/unconfirmed amount. | Eligible batch closes; premature close is rejected and retains status. |
| COLL-13 | P1 | Inspect batch, verification records, items and workflow transitions after submit, return, resubmit, verify and approval. | Snapshots, actors, timestamps, reasons and transitions match the actions and remain append-only. |

## 7. Lodgement evidence and treasury

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| LOD-01 | P0 | Create lodgement with eligible approved batches and valid bank details. | Lodgement number is unique; branch/bank data and allocations persist. |
| LOD-02 | P0 | Allocate over a batch's remaining balance, allocate an unapproved batch, duplicate batch allocation, or allocate a different branch's batch. | Invalid allocation is rejected atomically; no batch balance is overdrawn or unrelated allocation written. |
| LOD-03 | P0 | Create two lodgements for same bank and deposit slip; repeat slip for another bank. | Duplicate for same bank is blocked; same slip at a different bank follows unique-per-bank rule. |
| LOD-04 | P1 | Create partial lodgement, submit it and then create a later lodgement for the remaining amount. | Outstanding balance is calculated correctly and remaining approved amount remains allocatable. |
| LOD-05 | P0 | Submit without mandatory evidence, with valid private evidence, and with invalid type/oversized file. | Missing/invalid evidence blocks submission; permitted evidence is stored privately and linked to lodgement. |
| LOD-06 | P0 | Download evidence as authorised related user, unrelated user and guest; tamper with document ID. | Authorised access streams file; other requests are denied/redirected and do not reveal file contents/path. |
| LOD-07 | P1 | Edit draft lodgement and allocations; attempt edit after submit. | Draft updates recalculate allocations; submitted/locked record is not silently rewritten. |
| LOD-08 | P0 | Submit lodgement as an authorised bank officer; attempt submission from wrong branch or without permission. | Valid submission enters treasury review; invalid actor is denied without transition. |
| LOD-09 | P0 | Confirm treasury decision as the submitting bank officer; confirm as a distinct authorised treasury officer. | Separation-of-duties rule is enforced; independent confirmation records decision/reference/amount. |
| LOD-10 | P0 | Confirm with an amount mismatch without notes and then with required explanation/reference. | Unexplained variance is rejected; valid variance is retained with decision and notes. |
| LOD-11 | P1 | Return a lodgement for correction and resubmit; reject another lodgement. | Return reopens an editable correction path; rejection is terminal per workflow and both decisions remain in history. |
| LOD-12 | P0 | After return/rejection, attempt to allocate released funds again. | Released active allocation no longer counts against remaining batch balance; original allocation history remains. |
| LOD-13 | P1 | Inspect lodgement list filters, detail, evidence and treasury verification history. | Displayed amounts/statuses and records reflect persisted data and do not expose another branch. |

## 8. Reconciliation and discrepancies

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| REC-01 | P0 | Run reconciliation for period/branch containing fully confirmed and matched collections. | Run completes with expected/actual totals and matched items for selected scope. |
| REC-02 | P0 | Include verified payment without fully confirmed lodgement and confirmed lodgement without corresponding payment/batch match. | Unmatched items are recorded with correct variance and appropriate discrepancy is created or linked. |
| REC-03 | P1 | Run reconciliation for empty period, invalid date range and user-inaccessible branch. | Empty period completes with zero totals; invalid range is rejected; branch scope is enforced. |
| REC-04 | P1 | Run reconciliation again after correcting source records. | A new run is created; previous completed run and its items remain unchanged. |
| REC-05 | P1 | Run large date range and verify chunked processing/result counts against a known fixture set. | All intended records are represented without omission/duplication; run status is completed or visibly failed. |
| DISC-01 | P1 | Open a discrepancy and move it through investigation, awaiting evidence, escalation, resolution and closure where allowed. | Only legal transitions succeed; each stores actor/reason; resolution requires a note. |
| DISC-02 | P0 | Attempt illegal discrepancy transition, resolution without note, escalation without assignee/reason or action without permission. | Invalid action is rejected; status, assignment and history remain unchanged. |
| DISC-03 | P1 | Escalate a discrepancy and inspect assignee notification. | Assignment and escalation are recorded; expected database notification is created when configured. |
| DISC-04 | P1 | Search/filter discrepancy register and open an out-of-scope discrepancy. | Search results are accurate and branch scope is enforced. |

## 9. Account treatment and approvals

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| TREAT-01 | P1 | Create treatment draft with valid account/type/reason/amount and optional financial-record link. | Draft has unique reference, requester and correct branch/links; linked financial amounts are unchanged. |
| TREAT-02 | P0 | Attempt creation with inactive type, invalid account/reason pairing, out-of-scope account or invalid amount. | Validation/authorization fails; no request is persisted. |
| TREAT-03 | P1 | Update a draft and attach each required document; attempt submission before all required files are attached. | Draft fields/documents persist; missing required documents block submission. |
| TREAT-04 | P0 | Submit valid request and attempt approval by requester or actor without required stage permission. | Required stages are created; self-approval and unauthorized approval are blocked. |
| TREAT-05 | P0 | Approve workflow stages out of order or provide fewer than required approvals. | Out-of-order/insufficient approvals do not advance the request; current stage remains correct. |
| TREAT-06 | P1 | Return request with comments, correct request and resubmit. | Return notes are retained; requester can correct; request re-enters configured approval path. |
| TREAT-07 | P1 | Reject a request and attempt later approval/completion. | Rejection is retained and terminal; further decisions/completion are denied. |
| TREAT-08 | P1 | Fully approve request, then complete with notes as authorised operations user. | Approval and operational completion are separate transitions; completion actor/notes persist. |
| TREAT-09 | P1 | Cancel an eligible request with reason and attempt cancellation after terminal state. | Eligible cancellation records reason; terminal request cannot be changed. |
| TREAT-10 | P0 | Upload/download treatment evidence as authorised and unrelated user; delete evidence while request is locked. | Private access is enforced; document deletion obeys state/policy and audit requirements. |
| TREAT-11 | P1 | Configure a treatment type without a workflow and submit a request. | Request follows the configured no-approval path without inventing approval stages. |
| TREAT-12 | P1 | Review treatment queue/detail by branch, stage, age and status. | List/detail are correctly scoped and show current approval stage and history. |

## 10. Reports, exports, dashboard and notifications

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| REP-01 | P1 | Open dashboard as cashier, supervisor, treasury and auditor with different fixture data. | KPIs and queues reflect the user's role and visible branch scope. |
| REP-02 | P1 | Run each available report with default filters and representative date/branch/status filters. | Totals, rows and filters agree with source records; empty results are handled clearly. |
| REP-03 | P0 | Run reports and export from a restricted user while another branch has matching records. | Neither report rows nor totals leak out-of-scope data. |
| REP-04 | P1 | Request each supported export format; inspect export record, generated file, format/content and download. | Export stores requester/filter/format/status; output matches selected report and is downloadable only by authorised requester. |
| REP-05 | P0 | Attempt to download another user's export, an incomplete/failed export and a guessed export ID. | Download is denied or unavailable until ready; private files and paths are not disclosed. |
| REP-06 | P1 | Exercise export failure and retry/cleanup behavior where configured. | Failure is visible and auditable; retry does not duplicate financial actions; expired files follow retention policy. |
| NOTIF-01 | P1 | Submit/return/approve batch, lodge/confirm deposit, escalate discrepancy and decide treatment. | Each configured event produces notifications for intended recipients, without duplicate alerts on retry. |
| NOTIF-02 | P2 | Open notifications, mark one and all as read, refresh page. | Read state persists and only the current user's notifications are changed. |

## 11. Audit, workflow history and data integrity

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| AUD-01 | P0 | Perform each financial state transition and inspect workflow history. | Every transition records correct subject, old/new status, actor, reason and timestamp. |
| AUD-02 | P0 | Create/update/cancel/reverse a financial record and inspect audit old/new values. | Audit record is written and sensitive fields (passwords/secrets) are masked. |
| AUD-03 | P0 | Attempt direct mass assignment/update of guarded workflow status and hard-delete a referenced financial record. | Status cannot bypass workflow service; historical parent/financial records cannot be destructively deleted. |
| AUD-04 | P1 | Inspect audit register as auditor and as unauthorized user; filter by actor/date/subject. | Authorized auditor sees appropriate read-only records; filters work; unauthorized access is denied. |
| INT-01 | P0 | Submit duplicate payment reference, receipt number, batch number, lodgement number, treatment reference and reconciliation reference (where test harness permits). | Unique constraints/service generation prevent duplicate business identifiers. |
| INT-02 | P0 | Simultaneously allocate the same remaining batch balance to two lodgements. | Only a non-over-allocated result commits; losing transaction fails cleanly and totals remain consistent. |
| INT-03 | P1 | Verify decimals at cent boundaries, large supported amount and computed variance/outstanding balance. | Decimal values remain exact; no floating-point drift or negative impossible balance. |
| INT-04 | P1 | Force service failure during a multi-write transaction (payment+receipt, allocation+lodgement, approval+transition). | Transaction rolls back all related writes; no success-shaped partial record remains. |

## 12. UI, accessibility and platform smoke tests

| ID | Priority | Scenario and steps | Expected result |
|---|---|---|---|
| UI-01 | P1 | Load every navigation destination as an authorised user and exercise primary controls. | Pages resolve, navigation points to registered routes and no unexpected server/client error is shown. |
| UI-02 | P2 | Use forms with keyboard only at desktop and tablet/mobile widths. | Controls are reachable, focus is visible, labels/errors are associated and layout remains usable. |
| UI-03 | P1 | Submit Livewire actions twice quickly and refresh after success. | Duplicate actions are prevented/idempotent where required; persisted state remains correct after refresh. |
| UI-04 | P1 | Verify file, receipt and export screens in a production-built asset environment. | Vite assets resolve and protected downloads still enforce authorization. |

## 13. Release exit criteria

A release candidate is ready for business acceptance only when:

1. All P0 cases pass for each applicable role and branch scope.
2. Happy paths and the key return/reject/correction paths pass for payments, batches, lodgements, treasury, reconciliation and treatment.
3. Financial totals, allocations and workflow/audit histories reconcile against test fixtures.
4. Private documents, receipts, reports and exports pass cross-user and cross-branch authorization checks.
5. No unresolved P0 defect remains; P1/P2 failures have documented owner and disposition.
6. Automated and manual outcomes are recorded with build/commit, environment, tester, date, case ID and evidence.

Automate cases incrementally as Laravel feature tests. Prioritize payment capture and duplicate protection, shift/batch separation of duties, allocation concurrency, treasury decisions, treatment-stage controls, private file access and branch isolation before expanding broad report/UI coverage.
