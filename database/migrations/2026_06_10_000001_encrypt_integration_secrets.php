<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Encrypt integration secrets that were previously stored in plaintext, so the new
 * `encrypted` casts on the settings models can read them back. Safe to re-run: a value
 * that already decrypts cleanly is left untouched.
 */
return new class extends Migration
{
    private array $targets = [
        'woocommerce_settings' => ['consumer_key', 'consumer_secret', 'webhook_secret'],
        'courier_settings'     => ['api_key', 'api_secret'],
        'sms_settings'         => ['api_key'],
    ];

    public function up(): void
    {
        // Encrypted payloads are far longer than the original secrets, so widen the
        // columns to TEXT first to avoid silent truncation in VARCHAR(255).
        foreach ($this->targets as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->text($col)->nullable()->change();
                    }
                }
            });
        }

        foreach ($this->targets as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (DB::table($table)->get() as $row) {
                $updates = [];

                foreach ($columns as $col) {
                    $val = $row->{$col} ?? null;
                    if ($val === null || $val === '') {
                        continue;
                    }

                    // Already encrypted? leave it.
                    try {
                        Crypt::decryptString($val);
                        continue;
                    } catch (DecryptException $e) {
                        // plaintext — encrypt it
                        $updates[$col] = Crypt::encryptString($val);
                    }
                }

                if ($updates) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->targets as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (DB::table($table)->get() as $row) {
                $updates = [];

                foreach ($columns as $col) {
                    $val = $row->{$col} ?? null;
                    if ($val === null || $val === '') {
                        continue;
                    }

                    try {
                        $updates[$col] = Crypt::decryptString($val);
                    } catch (DecryptException $e) {
                        // already plaintext — leave it
                    }
                }

                if ($updates) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }
};
