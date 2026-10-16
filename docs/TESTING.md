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
| Status transition | Attempt to confirm/reject a cancelled booking | HTTP 409 |
| Concurrent capacity | Fire simultaneous requests for the final available seats/table | At most one conflicting reservation is created |
| Lock failure | Simulate unavailable advisory locking on a disposable test DB | HTTP 503; no booking row is inserted |

## Release gate

Do not mark integration/concurrency tests complete until these scenarios run automatically or are captured as repeatable staging evidence. Also test upgrade from schema versions 1 and 2, ZIP installation, backup/restore, REST nonce behavior, and permissions on the actual hosting stack.
