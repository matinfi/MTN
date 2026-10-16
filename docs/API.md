# REST API contract

Base namespace: `/wp-json/mtn/v1`.

All booking mutations require an authenticated WordPress session and a valid REST nonce when using cookie authentication. The current booking-creation endpoint requires a logged-in user. Production clients must not treat a public booking code as an authentication secret.

## Public catalog

`GET /catalog`

Returns published cafes and events whose cafe relationship points to a published cafe, plus the configured WordPress timezone and current table-selection mode. It does not expose cafe owner identifiers.

## Availability preview

`GET /availability?cafe_id={id}&event_id={id}&party_size={n}&booking_at=YYYY-MM-DD%20HH:MM:SS`

Returns whether event headcount capacity and selected-table availability appear sufficient, along with eligible table IDs/capacities. The time must be expressed in the WordPress site's timezone. The event must have a valid date and start time configured by an administrator. The requested time must fall within that schedule; an optional end time is enforced, including supported overnight events. This is an advisory snapshot, not a reservation hold; `POST /bookings` repeats validation.

## Booking audit history

`GET /bookings/{id}/history`

The customer who owns the booking, its assigned cafe owner, or an administrator can read the append-only event timeline. Actor user IDs are not returned. Events include creation, status transitions and table assignments; the timeline is operational audit evidence, not a substitute for a database backup.

## Customer booking history

`GET /my/bookings`

Requires an authenticated user and returns up to the latest 100 bookings owned by that user. Other customers' records are never included.

## Health

`GET /health`

Returns a minimal status/version payload. This endpoint is public and must not include environment details or credentials.

## Create a booking

`POST /bookings`

Required JSON fields:

| Field | Type | Contract |
| --- | --- | --- |
| `cafe_id` | integer | ID of a published `mtn_cafe` post |
| `event_id` | integer | ID of a published `mtn_event` post belonging to the cafe |
| `party_size` | integer | 1–100, also subject to event min/max |
| `booking_at` | string | `YYYY-MM-DD HH:MM:SS` in the WordPress site's timezone |

Optional fields:

| Field | Type | Contract |
| --- | --- | --- |
| `table_id` | integer | Required in customer-selects-table mode; must be active, published, belong to the cafe, and fit the party |
| `customer_note` | string | Plain text; sanitized on the server |
| `idempotency_key` | string | Optional client-generated key, 8–64 letters/digits or `. _ : -`; replays the same request safely when the payload matches |

A successful request returns HTTP 201 with the booking ID, random booking code, selected listing IDs, party size, status and timestamps. New records start as `pending_cafe`. The persisted `booking_at` is UTC; convert it for display using the site's configured timezone.

Possible errors include:
- `400`: invalid input, invalid listing relationship, party size, past time or table.
- `401`: login required.
- `409`: event capacity reached, table time conflict or invalid status transition.
- `503`: the cafe's booking lock could not be acquired in the configured wait period.

## Read a booking

`GET /bookings/{id}`

A customer may read only their own booking. Administrators may read any booking. Non-owners receive a not-found response to avoid disclosing another customer's record.

## Cancel a booking

`POST /bookings/{id}/cancel`

A customer may request cancellation only for their own booking. Cancellation follows the server-side state machine; only pending or confirmed bookings can transition to customer-cancelled. The administrator-configured `mtn_cb_customer_cancel_cutoff_minutes` setting controls the cutoff: `0` permits cancellation until the booking start, while a positive value blocks cancellation inside that many minutes before start. Late or past-start requests return HTTP 409.

## Confirm a booking

`POST /cafe/bookings/{id}/confirm`

The assigned cafe owner or a WordPress administrator can confirm a booking. The permission callback verifies ownership against the cafe's private `mtn_cafe_owner_id` metadata.

## Cafe operations

`GET /cafe/bookings`

Administrators can see all bookings; a user with the `mtn_cafe_manager` role sees only bookings for cafes whose `mtn_cafe_owner_id` matches their account. The owner ID is never exposed through public cafe metadata.

`POST /cafe/bookings/{id}/assign-table`

Required JSON field: `table_id`. Assigns a published, active table belonging to the cafe, checks party capacity and overlapping active reservations, and saves the assignment while holding the cafe's database lock. Only the assigned cafe owner or an administrator may use it.

`POST /cafe/bookings/{id}/complete` and `POST /cafe/bookings/{id}/no-show`

Move a confirmed booking to completed or no-show. Completing a booking triggers commission accrual from the amount snapshotted at booking creation. In cafe-assigned-table mode, a table must be assigned before the booking can be confirmed or completed.

`POST /cafe/bookings/{id}/cancel`

Cancels a pending or confirmed booking for a cafe managed by the current user (or any booking for an administrator). Optional JSON field: `reason`.

## Reject a booking

`POST /cafe/bookings/{id}/reject`

Optional JSON field: `reason` (plain text). Only the assigned cafe owner or an administrator may reject. The reason is saved as the current cafe note and an append-only audit event.

## Finance behavior

The fixed commission amount and currency are snapshotted when a booking is created. A positive commission is added to the append-only ledger only when an administrator or the assigned cafe owner marks a confirmed booking as completed. Marking a booking as no-show does not accrue commission. The admin finance screen records received settlements as negative ledger entries and supports signed manual adjustments with a required reason. These records track accounting state only; they do not initiate or verify a real payment.

## Current contract limitations

- Idempotency is optional; clients should send a stable unique `idempotency_key` for each booking attempt. Reusing a key with a different payload returns HTTP 409.
- Cafe-assigned-table mode does not yet allocate a table automatically. Cafe dashboard access is limited to the single configured owner ID per cafe; staff invitations and multi-user cafe teams are not implemented.
- Event capacity counts active bookings attached to the event post. Each event post is treated as one capacity pool; recurring schedules are not implemented.
- Locking relies on MySQL/MariaDB `GET_LOCK()`. Validate behavior on the production database and test concurrent requests before launch.
- Online payments, SMS OTP, automatic WhatsApp delivery and check-in tokens are not implemented.
