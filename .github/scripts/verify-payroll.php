<?php
// Bootstrapping Laravel's console kernel installs an exception handler that lets
// an uncaught exception exit 0, which would let `set -e` continue past a failed
// check. Fail loudly with a real non-zero status instead.
function verification_failed(string $message): void
{
    fwrite(STDERR, 'VERIFICATION FAILED: '.$message."\n");
    exit(1);
}

require $argv[1].'/vendor/autoload.php';
$app=require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$count=0;
foreach (App\Models\Employee::with('salaries')->cursor() as $employee) {
    foreach (['2026-07','2026-08','2026-09'] as $month) {
        $from=Carbon\Carbon::parse($month.'-01');
        $ledger=$employee->payrollLedger($from,$from->copy()->endOfMonth());
        if (abs($ledger['closing'] - round($ledger['opening']+$ledger['earned']-$ledger['paid']-$ledger['deduction'],2)) > 0.001) verification_failed('Ledger check failed');
        if (!is_finite($ledger['closing'])) verification_failed('Invalid balance');
    }
    $count++;
}
echo "Payroll read check passed for $count employees across July, August and September\n";
