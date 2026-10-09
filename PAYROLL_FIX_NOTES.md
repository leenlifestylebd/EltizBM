**Payroll calculation fix — review notes**

Salary history now determines which months earn salary. A recorded July salary remains payable even when the HR joining date says August. Historical months before the first revision earn zero; legacy employees without history use their joining month or creation month until their first revision is saved.

All payroll balances use the shared Employee payroll ledger. Salary is credited once on the first of the month; salary/advance payments and legacy deductions reduce the balance, while paid bonuses are tracked separately. Queries include the full last day and stop at the selected period, so future payments cannot alter earlier balances.

Verified example: July salary 6,000 less 2,000 paid leaves 4,000; August salary 12,000 less 6,200 paid leaves cumulative due 9,800. A September revision to 16,000 with no further payments gives 25,800.

Salary edits preserve the old rate, support zero revisions and production-only transitions, and apply future revisions from their selected month. Employee summaries, worker ledger, payroll, dashboard, CSV and PDF use consistent balances and effective salary labels. PDF production breakdown fields now match the controller's data.

Payment creation/deletion and the corresponding cash-book changes commit or roll back together. Salary edits and history updates are also atomic. Finalization saves server-calculated totals and is labeled as a snapshot; later payments may change the live balance without rewriting that snapshot.

**Validation**

- Integrated release: 34 tests passed, 167 assertions locally and all 34 tests passed on GitHub using production PHP 8.3.
- Separate private saved-record verification: 1 test passed, 9 assertions; July due 4,000 and August due 9,800 with the original joining date and all payments retained in the disposable test database.
- Real PDF output generated in the private test directory; CSV values and PDF view calculations checked.
- Git whitespace check passed. Pre-existing changes outside the payroll files were preserved.

**GitHub and production deployment — September 11, 2026**

The tested release was integrated with current GitHub master in a separate checkout, preserving newer worker-login and PDF changes. PRs #128 and #129 are merged. Production is verified at commit 8732df5e335ef47cd4cf5e643ec7bc31fbc66a60 (Actions run 34618618734). No schema migration or salary/payment data correction was performed. The original worktree still preserves earlier unrelated local work; do not push it wholesale. The exact deployed checkout is .local-backups/payroll-release-20260911.

Keep `.local-backups/` private and excluded from Git. It contains the verified pre-edit backup, private record checks, and a patch of just this task relative to the pre-edit snapshot. Tests under `tests/Feature/PayrollRegressionTest.php` use synthetic employee data and an in-memory database.

The local PHP 8.2 executable is older than the project's PHP >=8.3 requirement. Tests used the private portable compatible runtime. Missing dependencies already specified in composer.lock were installed locally; composer.json and composer.lock were not changed. On a checkout, install locked dependencies and run the normal package-discovery step before testing or serving the application.


Local Chrome verified July due 4,000, August due 9,800 and September due 25,800 after a synthetic 16,000 revision; bonus/salary payments and earlier months remained consistent. CSV and rendered PDF agree. Ten relevant module pages returned HTTP 200. The live login page returned 200 and the server read-checked payroll for 15 employees across July–September.

The live Mahdi Omar records retain two salary revisions (July 6,000; August 12,000), 13 payments and the original August joining date. September 16,000 was a test scenario only; no live salary revision was added.

Fresh preflight and final coordinated production backups both restored successfully: 47 tables, 10,086 rows, all table checks OK. The final recovery set and instructions are in .local-backups/hostinger-live-20260911-final. Deployment maintenance lasted approximately 9 seconds. A code-only Git bundle avoids the server's stale GitHub credentials; the pipeline restores previous code on a deployment verification failure while retaining the database.
