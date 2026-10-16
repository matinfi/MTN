# REST API contract

Base namespace: `/wp-json/mtn/v1`.

All booking mutations require an authenticated WordPress session and a valid REST nonce when using cookie authentication. The current booking-creation endpoint requires a logged-in user. Production clients must not treat a public booking code as an authentication secret.

## Public catalog

`GET /catalog`

Returns published cafes and events whose cafe relationship points to a published cafe, plus the configured WordPress timezone and current table-selection mode. It does not expose cafe owner identifiers.

## Availability preview

`GET /availability?cafe_id={id}&event_id={id}&party_size={n}&booking_at=YYYY-MM-DD%20HH:MM:SS`

Returns whether event headcount capacity and selected-table availability appear sufficient, along with eligible table IDs/capacities. The time must be expressed in the WordPress site's timezone. If an event date or operating hours are configured, the requested time must fall within that schedule, including supported overnight events. This is an advisory snapshot, not a reservation hold; `POST /bookings` repeats validation.

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

A customer may request cancellation only for their own booking. Cancellation follows the server-side state machine; only pending or confirmed bookings can transition to customer-cancelled.

## Confirm a booking (temporary administrator-only behavior)

`POST /cafe/bookings/{id}/confirm`

Until cafe ownership and staff authorization are implemented, only WordPress administrators can confirm a booking. The route must not be exposed as a functioning cafe-staff workflow.

## Reject a booking (temporary administrator-only behavior)

`POST /cafe/bookings/{id}/reject`

Optional JSON field: `reason` (plain text). Only administrators may reject until cafe ownership and staff authorization are implemented. The reason is saved as the current cafe note; append-only status history is not implemented yet.

## Current contract limitations

- Idempotency is optional; clients should send a stable unique `idempotency_key` for each booking attempt. Reusing a key with a different payload returns HTTP 409.
- Cafe-assigned-table mode does not yet allocate a table automatically; the admin console is currently administrator-only.
- Event capacity counts active bookings attached to the event post. Each event post is treated as one capacity pool; recurring schedules are not implemented.
- Locking relies on MySQL/MariaDB `GET_LOCK()`. Validate behavior on the production database and test concurrent requests before launch.
- Online payments, SMS OTP, automatic WhatsApp delivery and check-in tokens are not implemented.
