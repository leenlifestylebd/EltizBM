<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * Record a sensitive action (delete/edit of financial or account data) to the
     * activity log so there is an audit trail of who did what and when.
     */
    protected function logActivity(string $action, ?Model $model = null, array $old = [], array $new = []): void
    {
        try {
            ActivityLog::create([
                'business_id' => auth()->user()->business_id,
                'user_id'     => auth()->id(),
                'action'      => $action,
                'model_type'  => $model ? class_basename($model) : null,
                'model_id'    => $model?->getKey(),
                'old_values'  => $old ?: null,
                'new_values'  => $new ?: null,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the primary action.
            \Illuminate\Support\Facades\Log::warning('ActivityLog failed: ' . $e->getMessage());
        }
    }
}
