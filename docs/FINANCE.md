# Finance and settlement model

## Principles

- Customer online payment is disabled in this release.
- The commission amount and currency are copied to the booking when the booking is created. Changing the default setting later does not change existing booking snapshots.
- The commission trigger (`confirmed` or `completed`) is selected in central settings and snapshotted on each booking. The default is `confirmed`, matching the v1 project appendix; `completed` is available as an alternative.
- A positive commission is accrued to the append-only ledger when the booking reaches its snapshotted trigger status. Changing the setting later affects new bookings only.
- Rejection, cancellation or expiry before the configured trigger does not accrue commission. If the trigger is `confirmed`, a later no-show does not automatically reverse the already accrued commission; no-show reversal policy remains an explicit outstanding requirement.
- A recorded settlement adds a negative ledger entry. A signed manual adjustment adds a new entry; existing ledger rows are never edited in the normal UI.
- The ledger is accounting evidence maintained by this application. It does not prove that money moved and does not connect to a payment processor or bank.

## Balance calculation

For a cafe, the current ledger balance is the sum of all signed ledger entries in the configured currency:

- commission_accrued: positive amount owed to the platform.
- settlement_received: negative amount representing a payment manually confirmed by an administrator.
- manual_adjustment: signed correction or reversal with a required explanation.

A positive balance means the cafe owes the platform. A zero balance means the recorded entries net to zero. A negative balance means recorded credits/adjustments exceed recorded charges and must be reviewed.

## Controls

- Commission accrual uses a deterministic unique key per booking to prevent duplicate entries.
- Settlement recording uses a per-cafe advisory lock and rejects amounts above the current positive balance.
- Adjustments require an administrator, a published cafe, a non-zero signed amount and a reason.
- CSV export is administrator-only and text fields are escaped against spreadsheet formula injection.
- The default commission amount, currency and trigger status are snapshotted at booking creation; zero commission means no positive commission entry is written for that booking.
- The global currency setting is locked after the first booking or ledger entry to prevent mixing currencies in a single balance.

## Operational cautions

- Reconcile the ledger against external bank/payment records before considering a settlement final.
- Back up the database before migration and test schema upgrade on staging.
- The application does not yet provide automated cafe statements, period close, tax/VAT handling, bank reconciliation or a payment gateway.
- Booking status audit events and financial ledger entries are separate records with different purposes.
