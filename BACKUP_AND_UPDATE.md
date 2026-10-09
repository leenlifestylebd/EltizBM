# Backup and safe updates

Production: https://app.leen.com.bd, hosted on Hostinger.
Local workspace: `C:\Users\User\EltizBM`.
Update branch: `codex/safe-updates-20260908`.

## Local recovery snapshot

Snapshots are private and excluded from Git under `.local-backups/`.
Run `python .local-backups/create_snapshot.py` to create another local snapshot.
Only treat a snapshot as complete if its `manifest.json` has `verified: true`.
The script currently supports this workspace's verified SQLite configuration.

Each complete snapshot contains:

- `project.zip`: working code, uncommitted files, local SQLite database, `.env`,
  local uploads, built assets, and installed PHP/JavaScript dependencies.
- `repository.bundle`: Git history and refs from the local repository.
- `uncommitted.patch` and `working-tree-status.txt`: recorded local changes.
- `manifest.json`: file hashes, database row counts, and verification results.
- `RESTORE.txt`: instructions for recovery into a separate directory.

The database is captured through SQLite's backup API, then restored to a temporary
copy and checked for integrity. Every archived file is checked against its hash.
The script does not freeze the application or uploads across the whole snapshot;
stop local application writes first if files and database must match one instant.
Nested worktrees are excluded; this archive captures the main workspace only.
JavaScript dependency junctions are recorded as relative targets in the manifest;
recreate them in the recovery directory or reinstall dependencies before rebuilding.
Existing built assets and PHP dependencies are included in the archive.

The archive includes private credentials and business data. Keep it out of public
repositories and web roots. Copy the complete snapshot to a separate private
device or backup destination; a copy on this same disk cannot cover disk failure.

## Production backup status

The September 8 production files and SQL export are preserved under
`.local-backups/hostinger-live-20260908-2324/`. The SQL was restored successfully
in an isolated local MariaDB instance: 47 tables, 9,773 rows, all table storage
checks passed. This is a historical snapshot, not today's production state.

Fresh local files were backed up to `.local-backups/before-update-20260910-173012/`.
Fresh production files were downloaded to `.local-backups/hostinger-live-20260910-1734/`.
The September 10 production set now includes the SQL export generated at
11:38 UTC. It restored successfully into an isolated local database: 47 tables,
10,043 rows, all table storage checks passed. The verification server was stopped.
The file copies and SQL export were collected while the site remained online;
they are not one synchronized snapshot. Refresh the backup before deployment.

The local SQLite database is NOT a backup of Hostinger's live database.
Before changing production, identify the actual application directory, database,
uploaded-file locations, and any files stored outside the application directory.
Capture the live database and application files, including `.env`, APP_KEY,
uploads, and the deployed code version. Verify the timestamps and that the backup
can be restored in an isolated environment. Keep an independent private copy.
Pause application writes and background workers for the final coordinated
snapshot when required; schedule any interruption with the business owner.

The September 11 payroll release is live at commit `8732df5e335ef47cd4cf5e643ec7bc31fbc66a60` (PRs #128/#129, Actions run 34618618734). Fresh preflight files/SQL are in `.local-backups/hostinger-live-20260911-predeploy/`. The final coordinated backup, captured under maintenance immediately before deployment, is in `.local-backups/hostinger-live-20260911-final/`; its SQL independently restored with 47 tables and 10,086 rows, and all table checks passed. Both backup archives passed integrity checks. The final folder includes private encryption recovery keys and `RESTORE.txt`; keep it private. The live application and login check passed after deployment. No database migrations ran.

The exact tested/deployed code is in `.local-backups/payroll-release-20260911`, separate from earlier uncommitted changes in this main working tree. The production deployment now transfers a verified code-only Git bundle, takes a coordinated backup, verifies payroll, and can restore the previous code without restoring the database. Schema/dependency changes require a separate reviewed deployment path.

## September 17 rate editing release

Production rate editing is live at commit `1b4cb49da0f6fc571e5a0cc3a4f8658abd744860` (PR #130; Actions deployment 35191179956). This supersedes the September 11 live-code reference above. The exact feature checkout is `.local-backups/rate-release-20260917`, based on the prior deployed release; the main working tree still contains older unrelated local changes and must not be deployed wholesale.

Fresh preflight backup: `.local-backups/hostinger-live-20260917-predeploy`. Final coordinated backup under maintenance: `.local-backups/hostinger-live-20260917-final`. Both archives passed integrity checks; each SQL independently restored with 47 tables and 10,484 rows, all table checks passed. The final folder includes private encryption recovery keys and restore instructions. Verification servers were stopped.

All 43 tests / 298 assertions passed on the reconciled release locally and in CI using production PHP 8.3. Deployment completed successfully, cached views compiled, payroll read checks passed for 15 employees across July-September, and login/health checks passed. No database migrations ran. See `RATE_SETUP_NOTES.md` for feature behavior.

## September 18 Work Assign interface release

Work Assign is live at commit `94c668805707d5ba1010a3c60d57889919077ce5` (PR #131; deployment run 35329147123). This supersedes the September 17 live-code reference. The exact feature checkout is `.local-backups/work-assign-release-20260918`, based on the previously deployed commit `1b4cb49da0f6fc571e5a0cc3a4f8658abd744860`; only `resources/views/production/tasks/create.blade.php` changed. Do not deploy the main working tree wholesale.

The form now has three Bengali-guided sections, worker search for longer lists, stage selection, rate autofill, a live assignment/earnings summary, and optional assignment date and notes. Stage changes clear the selected rate; validation retries preserve entered values safely. Existing assignment-date behavior is retained. No migrations or dependency changes ran.

Fresh preflight backup: `.local-backups/hostinger-live-20260918-predeploy` (run 35328798433). Final coordinated backup under maintenance: `.local-backups/hostinger-live-20260918-final` (run 35329147123). Both archives passed hash and content verification; each SQL independently restored into isolated MariaDB with TCP disabled: 47 tables, 10,992 rows, all table checks passed. Verification servers were stopped. Each private folder contains encryption recovery keys and `RESTORE.txt`. The user explicitly approved encrypted private GitHub artifact storage for seven days.

All 43 release tests / 298 assertions passed locally and CI passed. An additional isolated preview check passed 13 assertions, and browser tests passed for worker search, autofill, stage/rate switching, zero rates, restored input, safe quoting, forwarding limits, and mobile layouts. Production cached views compiled, payroll read checks passed for 15 employees across July-September, and post-deploy public login/health checks returned HTTP 200. The assignment route still requires login. No production business records were created by these checks. Published form content matches the tested feature commit. Details are recorded in `.local-backups/work-assign-release-status.json`.

## Update process

1. Complete and verify the production backup before deploying any change.
2. Reconcile the local code with the actually deployed version.
3. Implement requested features on the update branch using a separate test database.
4. Keep staging credentials, sessions, storage, queues, couriers, payments, SMS,
   and WooCommerce connections isolated from production.
5. Prefer additive database migrations that preserve existing records and allow
   the previous application version to run. Review data transformations separately.
6. Check login/permissions, orders, stock, production/payroll, accounting, and the
   requested feature in staging. Exercise migrations on a restored database copy.
7. Immediately before deployment, take a fresh coordinated production backup.
8. Deploy and verify business-critical workflows; retain the previous release.

Never run `migrate:fresh`, `migrate:refresh`, or `db:wipe` on production.
A branch or Git rollback does not recover business data. Prefer reverting code
while retaining new transactions. Restoring an old production database would
discard later orders/payments unless those changes are separately recovered and
reconciled. Do not perform a database restore automatically.


## September 20 order stock location release

Production order stock location selection is live at commit `c7cc6d2ddd7da7aeec0e2eaff876d7dce7889510` (PR #132; deployment run 35522908120). This supersedes the September 18 live-code reference. The exact feature checkout is `.local-backups/stock-location-release-20260920`; its tested feature commit `48fb9ee5ce9c46885d55addcccbad5ca7bf78e77` has the same tree as the deployed merge. The main workspace still contains older unrelated changes and must not be deployed wholesale.

This release adds a warehouse/outlet selector for new orders and restores cancelled/returned stock to the saved location. Existing numbering, manager price overrides, wholesale bulk entry, backdated orders, and courier fee accounting remain. Partial returns are capped at unreturned quantities; subsequent full cancellation restores only the remainder. Historical orders remain unassigned and cannot be assigned a guessed location. Only the additive nullable `orders.warehouse_id` migration ran.

Fresh preflight backup: `.local-backups/hostinger-live-20260920-predeploy` (run 35522200737). Final coordinated backup under maintenance: `.local-backups/hostinger-live-20260920-final` (run 35522908120). Both archives passed hash/content checks and independently restored into isolated local MariaDB: 47 tables, 11,103 rows, all checks passed. Verification servers were stopped. Encryption recovery keys and current RESTORE.txt instructions are retained privately. The user explicitly approved encrypted private GitHub artifact storage for seven days in this task.

All 70 release tests / 526 assertions passed locally, and CI on production PHP 8.3 passed (run 35522809475). The exact migration and repeat migration, form rendering, and payroll checks passed on a restored production copy. During production deployment, order/item/product/warehouse/stock-adjustment fingerprints were unchanged across the migration; cached views compiled and payroll read checks passed for 15 employees. A normal external browser returned HTTP 200 for login and health; order-create correctly requires authentication. Browser preview with fictitious data verified desktop/mobile layout, validation retries, and stock deduction from the selected source. No production business records were created by verification.

Release evidence: `.local-backups/stock-location-release-status.json` and `.local-backups/stock-location-release-qa`. Future deployment scripts allow this exact migration only; all other schema or dependency changes require separate staging. Code rollback preserves business data and the backward-compatible nullable column.
