<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * AuditObserver — writes to activity_logs for all auditable models.
 *
 * Attached to: Order, Transaction, Employee, Product, MaterialPurchase,
 *              ProductionEntry, EmployeePayment, Customer
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $dirty = $model->getDirty();
        if (empty($dirty)) return;

        $old = array_intersect_key($model->getOriginal(), $dirty);
        $this->log($model, 'updated', $old, $dirty);
    }

    public function deleted(Model $model): void
    {
        // Distinguish soft-delete from hard-delete
        $action = method_exists($model, 'trashed') && $model->trashed()
            ? 'soft_deleted'
            : 'deleted';

        $this->log($model, $action, $model->getOriginal(), []);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', [], $model->getAttributes());
    }

    private function log(Model $model, string $action, array $old, array $new): void
    {
        try {
            // Skip fields we don't want to log (passwords, tokens, etc.)
            $skip = ['password', 'remember_token', 'email_verified_at', 'updated_at'];
            $old  = array_diff_key($old, array_flip($skip));
            $new  = array_diff_key($new, array_flip($skip));

            $bizId = $model->business_id
                ?? (auth()->user()?->business_id)
                ?? null;

            if (!$bizId) return;

            ActivityLog::create([
                'business_id' => $bizId,
                'user_id'     => auth()->id(),
                'action'      => $action,
                'model_type'  => class_basename($model),
                'model_id'    => $model->getKey(),
                'old_values'  => $old ?: null,
                'new_values'  => $new ?: null,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Never let audit logging break the app
            Log::warning('AuditObserver failed: ' . $e->getMessage());
        }
    }
}
