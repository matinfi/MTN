# REST API contract

Base namespace: `/wp-json/mtn/v1`.

All booking mutations require an authenticated WordPress session and a valid REST nonce when using cookie authentication. The current booking-creation endpoint requires a logged-in user. Production clients must not treat a public booking code as an authentication secret.

## Public catalog

`GET /catalog`

Returns published cafes and upcoming published events whose cafe relationship points to a published cafe, plus the configured WordPress timezone and table-selection mode. Cafe objects include `location_path`, an ordered array of assigned hierarchical location term names from root to leaf. It does not expose cafe owner identifiers.

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

A successful request returns HTTP 201 with the booking ID, random booking code, selected listing IDs, party size, status and timestamps. Private booking responses also include `whatsapp_url` when the cafe has a valid WhatsApp number configured; this opens a prefilled manual message and does not mean a message was sent or delivered. New records start as `pending_cafe`. The persisted `booking_at` is UTC; convert it for display using the site's configured timezone.

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

A customer may request cancellation only for their own booking. Only pending or confirmed bookings can transition to customer-cancelled. The server permits cancellation only within three hours after `created_at` and before the event start; the 100 TRY deposit is non-refundable on customer cancellation. Late or past-start requests return HTTP 409. See `/booking-policy/` for the customer-facing policy.

## Confirm a booking

`POST /cafe/bookings/{id}/confirm`

V1 central administration only: the route is restricted to WordPress administrators. A booking cannot be confirmed until its 100 TRY deposit has been manually verified by an administrator.

## Cafe operations

`GET /cafe/bookings`

V1 central administration only: this route and all cafe operational action routes require the WordPress administrator capability. The independent cafe-owner operations panel is retired from v1. The owner ID is never exposed through public cafe metadata.

`POST /cafe/bookings/{id}/assign-table`

Required JSON field: `table_id`. Assigns a published, active table belonging to the cafe, checks party capacity and overlapping active reservations, and saves the assignment while holding the cafe's database lock. Only the assigned cafe owner or an administrator may use it.

`POST /cafe/bookings/{id}/complete` and `POST /cafe/bookings/{id}/no-show`

Move a confirmed booking to completed or no-show. Commission accrual follows the trigger status snapshotted on the booking (`confirmed` by default, configurable to `completed`). In cafe-assigned-table mode, a table must be assigned before the booking can be confirmed or completed.

`POST /cafe/bookings/{id}/cancel`

Cancels a pending or confirmed booking for a cafe managed by the current user (or any booking for an administrator). Optional JSON field: `reason`.

## Reject a booking

`POST /cafe/bookings/{id}/reject`

Optional JSON field: `reason` (plain text). Only the assigned cafe owner or an administrator may reject. The reason is saved as the current cafe note and an append-only audit event.

## Secure customer check-in

`POST /bookings/{id}/checkin-token`

The authenticated customer who owns a confirmed booking can issue a one-time, random check-in token. Only the SHA-256 hash is stored. The response includes the plaintext token once and its UTC expiry; issuing another token invalidates the previous one. Token lifetime is configurable from 5 to 60 minutes (15 minutes by default).

`POST /cafe/check-in`

Requires a WordPress administrator in v1. JSON body: `{"token":"<64-character-hex-token>"}`. A valid unexpired token for a confirmed booking is consumed atomically, the booking's check-in status changes to `checked_in`, and a booking audit event is appended. Reuse, expiry or an invalid token is rejected. Token hashes and plaintext tokens are never included in public booking responses or audit details. Team members can be assigned directly by an administrator or added through the expiring invitation workflow. Check-in token delivery is not automated.

## Cafe team invitations

`POST /admin/cafes/{cafe_id}/invite`

Administrator-only. Requires a JSON email field. The invitation expires after 48 hours, stores only a SHA-256 token hash and can be accepted only by a logged-in WordPress account whose email matches the invitation. The recipient follows the invitation link on `/my-account/` and submits the acceptance form.

## SMS OTP sign-in

`POST /auth/otp/request`

Requires an E.164 phone number. Sends a six-digit code through Twilio when the provider constants are configured. The code expires after five minutes; resends are limited to one per minute, with a maximum of three requests per phone and ten per IP in 15 minutes.

`POST /auth/otp/verify`

Requires the same phone number and the six-digit code. A valid code is single-use and authenticates the WordPress account whose administrator-assigned `mtn_cb_phone` matches the phone. Five failed attempts lock the code. Configure `MTN_CB_TWILIO_ACCOUNT_SID`, `MTN_CB_TWILIO_AUTH_TOKEN` and `MTN_CB_TWILIO_FROM` outside source control. Provider delivery and login-cookie behavior must be verified on staging before production use.

## Finance behavior

The fixed 100 TRY reservation deposit is separate from the cafe commission ledger. The customer submits a bank-transfer reference through `POST /bookings/{id}/deposit-reference`; this moves the deposit to `under_review` and is not proof of payment. An administrator reconciles the actual bank statement using the admin booking console and marks the deposit verified or rejected. Booking confirmation is blocked until verified. Configure the account owner, IBAN and transfer instructions in central settings before opening booking to the public. No online gateway or automatic refund is available. The fixed commission amount, currency, trigger status and cancellation/no-show reversal policies are snapshotted when a booking is created. The central setting defaults to accruing commission on `confirmed`, with `completed` available as an alternative; later setting changes affect only new bookings. When the booking-time reversal option is enabled, a later cancellation or no-show appends a negative reversal entry if commission was already accrued. No payment refund is initiated or verified. When the booking-time event-cancellation policy is enabled, moving a published event to draft, private or trash appends an idempotent reversal for accrued commissions. The admin finance screen records received settlements as negative ledger entries and supports signed manual adjustments with a required reason. These records track accounting state only; they do not initiate or verify a real payment.

## Current contract limitations

- Idempotency is optional; clients should send a stable unique `idempotency_key` for each booking attempt. Reusing a key with a different payload returns HTTP 409.
- Cafe-assigned-table mode does not yet allocate a table automatically. Team invitations require the recipient to have a WordPress account whose email matches the invitation.
- Event capacity counts active bookings attached to each event post. Weekly/monthly recurrence generates draft occurrences for administrator review; each occurrence has its own capacity pool.
- Locking relies on MySQL/MariaDB `GET_LOCK()`. Validate behavior on the production database and test concurrent requests before launch.
- Online payments, automated WhatsApp delivery/chatbot, multi-currency and automatic refunds are not implemented. Optional Twilio SMS OTP requires provider constants in `wp-config.php`; it must not be advertised as live until credentials and delivery are verified.


## AJAX event discovery

`GET /events?q={term}&city={city}&artist={name}&genre={genre}&venue_type={cafe|restaurant}&date_from=YYYY-MM-DD&date_to=YYYY-MM-DD&min_price={amount}&max_price={amount}&sort={popular|distance}&lat={latitude}&lng={longitude}&page={n}&per_page={n}`

Returns paginated published future events with artist, price, city, genre, venue type, optional coordinates and computed distance. The public discovery UI loads more results without a page reload. Distance sorting requires the customer to explicitly grant browser location access and requires valid coordinates entered by an administrator. The map action opens OpenStreetMap; it does not imply geocoding or live traffic data.

## Saved events

- `GET /my/favorites` — authenticated customer's saved event IDs.
- `POST /my/favorites/{event_id}` — save a published event.
- `DELETE /my/favorites/{event_id}` — remove a saved event.

## Manual bank-transfer deposit

`POST /bookings/{id}/deposit-reference`

Authenticated owner only. JSON: `{"reference":"bank-transfer-reference"}`. A 4–100 character bank reference changes the status to `under_review`; it does not confirm payment. Administrators reconcile the real account statement in **MTN Bookings** and can set `verified` or `rejected`. Booking confirmation returns HTTP 409 until the deposit status is verified. The deposit is fixed at 100 TRY, separate from the remaining amount payable at the venue, and non-refundable when the customer cancels.

## Partner applications

The `[mtn_partner_application]` shortcode accepts cafe, restaurant and artist applications. Applicants must be logged in to submit so they can track status with `[mtn_my_applications]` on `/my-account/`. Administrators review submissions under **درخواست‌های همکاری**; a submission never publishes an event automatically. `[mtn_support_whatsapp]` opens a direct WhatsApp support chat only; no bot or delivery webhook is claimed.
