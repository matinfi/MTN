# Booking engine test plan

Run these checks on a disposable WordPress installation with the same MySQL/MariaDB major version intended for staging. The lint workflow runs PHP/JavaScript syntax and static source contracts. A WordPress PHPUnit workflow covers initial settings and cafe-team authorization tests; the complete scenarios below still require execution on staging.

## Setup
1. Install and activate the plugin on a clean WordPress site.
2. Configure the site timezone explicitly.
3. Create a customer account, one published cafe, one published event linked to that cafe, and at least two active published tables.
4. Set event capacity and minimum/maximum party size. Keep test data disposable.

## Required scenarios

| Scenario | Action | Expected result |
| --- | --- | --- |
| Authentication | Submit booking creation as a logged-out visitor | HTTP 401 |
| Listing relationship | Use an event linked to a different cafe | HTTP 400 |
| Missing schedule | Publish an event without a valid date/start time and request availability | HTTP 400 until the schedule is configured |
| Party validation | Request more than event max or table capacity | HTTP 400 |
| Past time | Submit a local site time in the past | HTTP 400 |
| Timezone conversion | Submit a known local time and inspect the database row | UTC timestamp matches the configured site's timezone |
| Event capacity | Submit requests whose combined active party size exceeds event capacity | The over-capacity request returns HTTP 409 |
| Table overlap | Submit overlapping bookings for the same table | The conflicting request returns HTTP 409 |
| Table boundary | Submit a second booking starting exactly when the first reservation window ends | The second request is accepted if no other rule blocks it |
| Expiry | Leave a pending booking beyond the configured expiry and trigger the scheduled worker | The stale pending booking becomes `expired`, an audit event is written, and capacity is released |
| Expiry lock contention | Run the expiry worker while a cafe booking is being created | Per-cafe lock prevents conflicting status updates; worker retries on a later schedule |
| Idempotency replay | Repeat the same payload and key as the same customer | The original booking is returned; no duplicate row is inserted |
| Idempotency mismatch | Reuse a key with a different payload | HTTP 409 |
| Idempotency isolation | Use the same key for a different customer | A separate booking may be created |
| Ownership | Read or cancel another customer's booking | The request returns not found |
| Customer cancellation cutoff | Configure a cutoff and attempt cancellation before and inside that window; also try after booking start | Cancellation succeeds only before the cutoff window; late or past-start cancellation returns HTTP 409 |
| Audit history privacy | Request history as booking owner, assigned cafe owner, unrelated customer and admin | Only owner, assigned cafe owner and admin can read; actor IDs are not exposed in REST output |
| Manual WhatsApp link | Configure a valid cafe WhatsApp number and view the customer's booking list | A prefilled manual link is shown; no message is reported as sent/delivered, and an invalid/missing number produces no link |
| Check-in token privacy | Issue a token for a confirmed booking and inspect the database/API response | Plaintext is returned once, only its hash is stored, and public booking/history payloads do not contain the token |
| Check-in token expiry | Issue a token and attempt check-in after configured expiry | The request is rejected and booking remains not checked in |
| Check-in token single use | Check in with a valid token, then replay it | First request marks checked-in and appends an audit event; replay is rejected |
| Check-in authorization | Submit a valid token as an unrelated cafe manager | HTTP 403; the token remains unconsumed so the assigned owner can use it |
| Check-in token replacement | Issue a second token for the same booking | The old token is invalid and only the new token can check in the customer |
| Cafe owner isolation | Sign in as owner A and request owner B's booking ID | Confirm/reject/cancel returns HTTP 403; owner A's list never includes owner B's records |
| Cafe team assignment | Assign an existing user as a cafe team manager and sign in as that user | The team member can view and manage assigned-cafe bookings, but not other cafes; unassigned users receive HTTP 403 |
| Team removal | Remove a user from a cafe's team and retry a booking action | The removed user can no longer read or mutate that cafe's bookings even if the manager role remains on the account |
| Cafe dashboard | Sign in as the configured cafe owner | The dashboard loads only bookings for cafes assigned to that user |
| Manual table assignment | Assign a free active table to an unassigned booking | Table is saved; conflicting, inactive, undersized or other-cafe tables are rejected |
| Commission snapshot | Change the default commission after creating a booking, then complete it | The ledger uses the booking-time amount/currency snapshot |
| Currency lock | Create a booking or ledger entry, then attempt to change the global currency | The setting remains unchanged to prevent mixed-currency balances |
| Commission trigger default | Create a booking with default settings and confirm it | Exactly one commission entry is accrued on confirmation when the snapshotted amount is positive |
| Commission trigger alternative | Configure `completed`, create a booking, confirm it and then complete it | No commission entry on confirmation; exactly one on completion |
| Commission trigger snapshot | Change the central trigger setting after creating a booking | The existing booking retains its original trigger; only new bookings use the new setting |
| Cancellation reversal | Configure cancellation reversal, accrue commission on confirmation, then cancel | A single negative reversal entry is appended and net commission returns to zero |
| No-show reversal | Configure trigger `confirmed` and no-show reversal, confirm then mark no-show | Exactly one negative reversal is appended; replaying the status hook does not duplicate it |
| Reversal policy snapshot | Change reversal settings after creating a booking | Existing bookings retain their original reversal policy; new bookings use updated settings |
| Commission eligibility | Configure trigger as `completed`; mark one booking no-show and another completed | No-show creates no commission; completed creates exactly one ledger entry |
| Table assignment gate | In cafe-assigned-table mode, attempt to confirm or complete a booking without a table | HTTP 409 until an active table is assigned |
| Settlement balance | Record a settlement larger than the outstanding cafe balance | The request is rejected and no settlement/ledger row is added |
| Finance reconciliation summary | Add commission accrual, reversal, settlement and signed adjustment entries for a published cafe | Admin summary separates each category and its net outstanding balance by currency; overdue is not inferred without an agreed due-date policy |
| Settlement idempotency | Submit the same settlement form request key twice | Only one settlement and one ledger entry are created |
| Duplicate accrual | Trigger the configured commission-status hook twice for one booking | Unique entry key prevents duplicate commission accrual |
| Ledger correction | Record a signed adjustment with a reason | A new immutable ledger entry is appended and balance recalculates |
| Adjustment idempotency | Submit the same adjustment form request key twice | Only one ledger entry is added; key reuse with changed amount/reason is rejected |
| CSV export | Export the ledger as an administrator | CSV includes entries, amounts, currency, reference, actor and UTC timestamp; non-admin is denied |
| Concurrent table assignment | Assign the same table to overlapping bookings simultaneously | Cafe-scoped lock permits no conflicting assignment |
| Admin metadata | Save cafe, event and table metadata using the editor | Values persist and are used by catalog, schedule and capacity checks |
| Location hierarchy | Create nested country, city, area and neighborhood terms and assign a cafe | The hierarchy persists and the cafe is associated with the selected location term |
| Catalog location path | Assign a cafe to a neighborhood term with parent terms and request `/catalog` | `location_path` lists the assigned term's ancestors in root-to-leaf order without exposing owner IDs |
| Cafe owner privilege boundary | Sign in as an editor who can edit cafes and submit a forged owner ID in cafe metadata | Owner assignment is ignored; only administrators can change the cafe owner or grant the cafe-manager role |
| Default pages | Upgrade a site that already has the booking/account pages | A cafe-dashboard page is added without duplicating existing slugs |
| Frontend assets | Open booking page and inspect browser console | Booking wizard JavaScript loads without syntax/runtime errors |
| SMS OTP provider configuration | Request an OTP with provider constants absent, then with test Twilio credentials configured | Missing credentials fail closed; configured requests are throttled, codes expire in five minutes, wrong attempts lock at five and valid codes authenticate only the matching assigned phone account |
| OTP abuse limits | Request more than three codes for one phone or ten per IP within 15 minutes; request again within 60 seconds | HTTP 429; no additional SMS is sent |
| Team invitation | Invite an email to a published cafe, accept from a logged-in account with matching email, then replay or use an expired token | Invitation token is hash-only, expires after 48 hours, is single-use, and membership is limited to the assigned cafe |
| Recurring event drafts | Configure a weekly/monthly published source event with an end date and run the generator twice | Each date is generated at most once as a draft, inherits source content/featured image and requires editorial publication approval |
| Event cancellation reversal | Accrue commission, enable the booking's event-cancel snapshot, then unpublish its event | A single negative ledger reversal is appended; repeated unpublish transitions do not duplicate the reversal |
| Monthly statement generation | Run statement generation twice after a calendar month closes for a cafe with a valid statement email | One idempotent statement notification per cafe/currency/period; statement includes accruals, reversals, settlements, adjustments, closing balance, due date and current overdue balance |
| Statement overdue allocation | Add old commission accrual, newer accrual, a reversal and a settlement; evaluate due dates | Reversals reduce their booking's accrual first; settlements/credits reduce oldest open items first; overdue balance is reported by currency |
| Status transition | Attempt to confirm/reject a cancelled booking | HTTP 409 |
| Concurrent capacity | Fire simultaneous requests for the final available seats/table | At most one conflicting reservation is created |
| Lock failure | Simulate unavailable advisory locking on a disposable test DB | HTTP 503; no booking row is inserted |

## Release gate

Do not mark integration/concurrency tests complete until these scenarios run automatically or are captured as repeatable staging evidence. Also test upgrade from schema versions 1 through 10, ZIP installation, backup/restore, REST nonce behavior, and permissions on the actual hosting stack.
