# Finance and settlement model

## Principles

- Customer online payment gateway is disabled in this release. The separate fixed 100 TRY booking deposit is handled by bank transfer/card-to-card and manually reconciled by an administrator; entering a transfer reference is not proof of payment.
- The commission amount and currency are copied to the booking when the booking is created. Changing the default setting later does not change existing booking snapshots.
- The commission trigger (`confirmed` or `completed`) is selected in central settings and snapshotted on each booking. The default is `confirmed`, matching the v1 project appendix; `completed` is available as an alternative.
- A positive commission is accrued to the append-only ledger when the booking reaches its snapshotted trigger status. Changing the setting later affects new bookings only.
- Rejection or expiry before the configured trigger does not accrue commission. Cancellation and no-show reversal policies are snapshotted on each booking at creation; when enabled, a negative append-only reversal entry is written if commission was already accrued. These policies do not initiate refunds or prove money was returned.
- Cancelled-event commission reversal is implemented via an append-only reversal entry where the booking snapshot enables it.
- Booking deposit amount/currency/status/reference and verification timestamp are separate from the cafe commission ledger. The fixed 100 TRY customer deposit is not a cafe commission and must not be counted as commission revenue.
- Customer cancellation is allowed only within three hours after `created_at` and before the event start. A customer cancellation never triggers an automatic deposit refund. The policy is displayed before booking and in account views.
- A recorded settlement adds a negative ledger entry. A signed manual adjustment adds a new entry; existing ledger rows are never edited in the normal UI.
- The commission ledger is accounting evidence maintained by this application. It does not prove that commission money moved and does not connect to a payment processor or bank. Deposit verification is only an administrator's manual assertion after checking the actual bank statement.

## Balance calculation

For a cafe, the current ledger balance is the sum of all signed ledger entries in the configured currency:

- commission_accrued: positive amount owed to the platform.
- settlement_received: negative amount representing a payment manually confirmed by an administrator.
- manual_adjustment: signed correction or reversal with a required explanation.
- commission_reversal: automatic negative entry when a snapshotted cancellation/no-show policy requires reversal.

A positive balance means the cafe owes the platform. A zero balance means the recorded entries net to zero. A negative balance means recorded credits/adjustments exceed recorded charges and must be reviewed.

## Controls

- Commission accrual uses a deterministic unique key per booking to prevent duplicate entries.
- Settlement recording uses a per-cafe advisory lock and rejects amounts above the current positive balance.
- Adjustments require an administrator, a published cafe, a non-zero signed amount and a reason.
- CSV export is administrator-only and text fields are escaped against spreadsheet formula injection.
- The default commission amount, currency and trigger status are snapshotted at booking creation; zero commission means no positive commission entry is written for that booking.
- The deposit IBAN and account owner must be configured and independently checked by the site operator before the booking page is promoted. The customer submits a bank transfer reference; only an administrator can set `deposit_status=verified` after manual reconciliation. Booking confirmation is blocked until that state is reached.
- The global currency setting is locked after the first booking or ledger entry to prevent mixing currencies in a single balance.

## Operational cautions

- Reconcile the ledger against external bank/payment records before considering a settlement final.
- Back up the database before migration and test schema upgrade on staging.
- Monthly cafe statement emails, a configurable month-end due-date policy and overdue balance reporting are implemented. External bank-statement reconciliation, period-close approval, tax/VAT handling and payment-gateway processing remain out of scope. The v1 deposit workflow is a manual bank-transfer reconciliation, not an online checkout.
- Booking status audit events and financial ledger entries are separate records with different purposes.
