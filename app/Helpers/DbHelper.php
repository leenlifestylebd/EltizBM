<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * DB-agnostic SQL expression helpers.
 * Supports SQLite (dev) and MySQL (production) transparently.
 */
class DbHelper
{
    /**
     * Returns a SQL expression that formats a date column as 'YYYY-MM'.
     * Usage: DbHelper::yearMonth('transaction_date')
     */
    public static function yearMonth(string $column): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql'            => "to_char({$column}, 'YYYY-MM')",
            default            => "strftime('%Y-%m', {$column})",  // sqlite
        };
    }

    /**
     * Returns a SQL expression that formats a date column as 'YYYY-MM-DD'.
     */
    public static function dateFormat(string $column): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "DATE({$column})",
            'pgsql'            => "{$column}::date",
            default            => "strftime('%Y-%m-%d', {$column})",
        };
    }

    /**
     * Returns a SQL expression for extracting year from a date column.
     */
    public static function year(string $column): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "YEAR({$column})",
            'pgsql'            => "EXTRACT(YEAR FROM {$column})",
            default            => "strftime('%Y', {$column})",
        };
    }

    /**
     * Returns a SQL expression for extracting month from a date column.
     */
    public static function month(string $column): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "MONTH({$column})",
            'pgsql'            => "EXTRACT(MONTH FROM {$column})",
            default            => "strftime('%m', {$column})",
        };
    }
}
