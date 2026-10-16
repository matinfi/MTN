# REST API contract

Base namespace: `/wp-json/mtn/v1`.

All booking mutations require an authenticated WordPress session and a valid REST nonce when using cookie authentication. The current booking-creation endpoint requires a logged-in user. Production clients must not treat a public booking code as an authentication secret.

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

- Idempotency keys are not implemented; clients should not blindly retry a timed-out create request without first checking whether a booking was created.
- Cafe-assigned-table mode does not yet allocate a table automatically.
- Event capacity counts active bookings attached to the event post. Each event post is treated as one capacity pool; recurring schedules are not implemented.
- Locking relies on MySQL/MariaDB `GET_LOCK()`. Validate behavior on the production database and test concurrent requests before launch.
- Online payments, SMS OTP, automatic WhatsApp delivery and check-in tokens are not implemented.
