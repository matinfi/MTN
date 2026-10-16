# Booking engine test plan

Run these checks on a disposable WordPress installation with the same MySQL/MariaDB major version intended for staging. The current CI workflow runs PHP syntax checks only; it does not execute these integration scenarios.

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
| Party validation | Request more than event max or table capacity | HTTP 400 |
| Past time | Submit a local site time in the past | HTTP 400 |
| Timezone conversion | Submit a known local time and inspect the database row | UTC timestamp matches the configured site's timezone |
| Event capacity | Submit requests whose combined active party size exceeds event capacity | The over-capacity request returns HTTP 409 |
| Table overlap | Submit overlapping bookings for the same table | The conflicting request returns HTTP 409 |
| Table boundary | Submit a second booking starting exactly when the first reservation window ends | The second request is accepted if no other rule blocks it |
| Expiry | Leave a pending booking beyond the configured expiry, then create another booking at that cafe | The stale pending booking becomes `expired` before capacity is evaluated |
| Idempotency replay | Repeat the same payload and key as the same customer | The original booking is returned; no duplicate row is inserted |
| Idempotency mismatch | Reuse a key with a different payload | HTTP 409 |
| Idempotency isolation | Use the same key for a different customer | A separate booking may be created |
| Ownership | Read or cancel another customer's booking | The request returns not found |
| Cafe owner isolation | Sign in as owner A and request owner B's booking ID | Confirm/reject/cancel returns HTTP 403; owner A's list never includes owner B's records |
| Cafe dashboard | Sign in as the configured cafe owner | The dashboard loads only bookings for cafes assigned to that user |
| Manual table assignment | Assign a free active table to an unassigned booking | Table is saved; conflicting, inactive, undersized or other-cafe tables are rejected |
| Concurrent table assignment | Assign the same table to overlapping bookings simultaneously | Cafe-scoped lock permits no conflicting assignment |
| Admin metadata | Save cafe, event and table metadata using the editor | Values persist and are used by catalog, schedule and capacity checks |
| Default pages | Upgrade a site that already has the booking/account pages | A cafe-dashboard page is added without duplicating existing slugs |
| Frontend assets | Open booking page and inspect browser console | Booking wizard JavaScript loads without syntax/runtime errors |
| Status transition | Attempt to confirm/reject a cancelled booking | HTTP 409 |
| Concurrent capacity | Fire simultaneous requests for the final available seats/table | At most one conflicting reservation is created |
| Lock failure | Simulate unavailable advisory locking on a disposable test DB | HTTP 503; no booking row is inserted |

## Release gate

Do not mark integration/concurrency tests complete until these scenarios run automatically or are captured as repeatable staging evidence. Also test upgrade from schema versions 1 and 2, ZIP installation, backup/restore, REST nonce behavior, and permissions on the actual hosting stack.
