# Financial Controls Fix Notes

Date: 2026-10-07

## Changes

- ABA simulation cannot create confirmed payments or reduce loan balances. Fake checkout and QR generation have been removed from the controller. Gateway create/checkout requests return HTTP 503 until a verified provider integration is implemented.
- Financial actions enforce server-side permissions. Collection requires `loan_management.payments.create` or `loan_management.payment`. Settlement, restructuring, and payoff require `loan_management.approve` or `loan_management.loans.approve`. Rejection also accepts `loan_management.loans.reject`.
- Settlement calculates the outstanding amount from current, locked, non-archived schedules. Cash must equal outstanding balance minus the approved unpaid-interest rebate plus the prepayment fee. Principal cannot be discounted through the interest-rebate field. Repeating settlement on a completed loan is rejected.
- The ordinary payoff path applies the same balance and rebate checks, preserves schedule history, and records rebates separately from actual cash.
- Web and mobile collection include overdue installments. Advance credit updates both paid and balance columns for every affected installment. Small shortfalls are retained instead of being treated as money received. Excess payments and invalid installment selection are rejected.
- Mobile collection checks customer ownership, loan currency, converted payment-detail totals, and selected installments. Archived schedules do not contribute to the current balance.
- Restructuring uses the existing quotation schedule calculator for flat and declining interest. The entered annual rate is converted to the monthly rate used by the system. Month-end dates remain valid. Non-waived penalties carry into the first replacement installment; archived schedules retain their history. Historical cash receipts remain included in the loan total.
- Penalty accrual separates `daily_penalty_rate` from accumulated `penalty_amount`. Loan locking and penalty history prevent a repeated run from charging the same installment twice for a given day. Pending loans do not accrue penalties, and a configured zero rate is respected.
- Posted payment amounts cannot be edited or deleted through either web or mobile handlers, including schedule-editing shortcuts. Unfunded draft/rejected/cancelled loans with no payment history are archived rather than physically deleted. Schedule regeneration after recorded payments requires the approved restructuring workflow.
- Business-settings saves handle omitted optional subtitle and currency-symbol fields.

## Database Migration

The migration adds a nullable `daily_penalty_rate` column on the `mysql_loan` connection. It deliberately does not copy accumulated penalties into a daily rate.

Apply it after the configured MySQL account can connect:

```bash
php artisan migrate --path=database/migrations/2026_10_07_000001_add_daily_penalty_rate_to_loans.php
```

A null daily rate uses `loanmanagement.daily_penalty_rate`, currently 0.50 in `config/config.php`. An explicit zero disables daily charges for that loan. Standalone loan creation accepts an optional nonnegative `daily_penalty_rate`.

The development MySQL connection rejected the configured account, so the migration was tested against an isolated SQLite database and was not applied to the configured MySQL database. No historical financial records were repaired or backfilled.

## Verification

Final result: 130 feature tests passed with 834 assertions, including 37 new financial-control tests. PHP syntax checks and whitespace checks passed for the changed application files.

The regression tests cover permissions, disabled simulated posting, settlement amounts and rebates, fees, repeat settlement, partial repayments, overdue collection, advance credit, shortfalls, payoff, interest methods, month-end dates, penalty retention/waiver, repeated penalty runs, zero rates, dry runs, immutable receipts, history-preserving deletion, schedule-editor restrictions, and mobile currency/customer checks.

Run the feature suite with enough memory for the existing tests, which bootstrap a Laravel application repeatedly:

```bash
php -d memory_limit=512M -d error_reporting=22527 vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Feature
```

## Remaining Work

Verified ABA transactions require a real provider adapter, credentials, signed callback verification, and duplicate-transaction protection. This fix disables fabricated transactions; it does not add a live bank integration.

An approved payment-reversal workflow with durable payment-to-installment allocation records is still needed for correcting posted receipts. Until it exists, destructive correction through the old edit/delete endpoints is blocked.

Row-lock behavior under simultaneous production requests still needs verification on MySQL; the isolated regression tests use SQLite. Duplicate submission tokens are a separate remaining improvement. The larger approval, disbursement, reconciliation, accounting, backup, and underwriting features recommended in the project review are not implemented by these fixes.
