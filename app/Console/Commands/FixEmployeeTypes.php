<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixEmployeeTypes extends Command
{
    protected $signature   = 'app:fix-employee-types {--force}';
    protected $description = 'Set type=production for employees who have production entries (DB default was salary for all)';

    public function handle(): int
    {
        // Employees with at least one production entry are production workers
        $affected = DB::table('employees')
            ->where('type', 'salary')
            ->whereIn('id', function ($q) {
                $q->select('employee_id')->from('production_entries')->distinct();
            })
            ->update(['type' => 'production']);

        $this->info("Updated {$affected} employees → type=production (had production entries).");

        // Show remaining salary employees so admin can verify
        $salaryEmps = DB::table('employees')
            ->where('type', 'salary')
            ->where('status', 'active')
            ->select('id', 'name', 'salary')
            ->get();

        if ($salaryEmps->count()) {
            $this->newLine();
            $this->info("Remaining salary employees (no production entries):");
            $this->table(['ID', 'Name', 'Salary'], $salaryEmps->map(fn($e) => [$e->id, $e->name, $e->salary]));
        }

        return self::SUCCESS;
    }
}
