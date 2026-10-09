# Production rate editing

Deployed to Hostinger on 2026-09-17 at commit `1b4cb49da0f6fc571e5a0cc3a4f8658abd744860` via PR #130 and successful Actions run 35191179956.

## Behavior

- Active Rate Setup rows have an Edit button and an amount-only edit dialog.
- A changed rate creates a new active row and archives the old row in one transaction. Product, variant, stage, unit, color, and size are copied unchanged.
- Existing tasks and entries retain their original rate ID. Future submissions against existing tasks therefore retain the original default rate, and historical earnings remain unchanged.
- Explicit submission-level rate overrides retain their existing behavior.
- New assignments, stage forwarding, and inventory cutting assignments reject archived rates. Assignment creation locks the selected rate inside a transaction to serialize it with edits.
- Archive replaces physical deletion so earnings and task rate links are preserved. Archived rates are visible, read-only, and excluded from new-work selections.
- No schema migration or data backfill is required.

## Verification

- Before editing: verified private local recovery snapshot at `.local-backups/before-update-20260917-122400`; 13,444 files checked, SQLite restore integrity passed, 47 tables.
- Baseline: 34 tests, 170 assertions passed.
- Final: 43 tests, 301 assertions passed using PHP 8.5.10 and an isolated in-memory SQLite database.
- Added `tests/Feature/ProductionRateHistoryTest.php`: partial/unstarted/completed work, repeated changes, zero rates, stale forms, forwarding, cutting, archive protection, tenant boundaries, amount validation, and rendered edit controls.
- The inventory cutting test widens the isolated SQLite stage columns to match production's existing MySQL-only widening migration. No application database was migrated.
- Whitespace validation passed. The Blade page was rendered in tests; no interactive browser check was performed.

## Live release verification

The change was reconciled onto the actual September 11 production code in `.local-backups/rate-release-20260917`, preserving admin-only archive authorization, task/submission date handling, and earlier payroll fixes. The exact release passed 43 tests / 298 assertions locally and in GitHub CI with production PHP 8.3.

Fresh production file/SQL backups were captured before release and again under maintenance immediately before activation. Both backups passed archive integrity checks and independent MariaDB restores: 47 tables, 10,484 rows, all table checks passed. The final private recovery copy is `.local-backups/hostinger-live-20260917-final` with private keys and RESTORE.txt; a remote private backup and encrypted Actions artifact provide additional copies.

Deployment confirmed commit `1b4cb49da0f6fc571e5a0cc3a4f8658abd744860`; cached views compiled; payroll read checks passed for 15 employees across July-September. Public login and health endpoints returned 200, and unauthenticated Rate Setup access redirected to login. Interactive browser verification was unavailable because the browser tool could not initialize in the Windows sandbox. No business rates were changed for smoke testing and no migrations ran.

The main working tree retains older unrelated local work. Do not deploy it wholesale. The original task-only patch was reconciled before release; `.local-backups/rate-release-20260917.patch` is the clean deployed feature patch based on commit `8732df5e335ef47cd4cf5e643ec7bc31fbc66a60`.
