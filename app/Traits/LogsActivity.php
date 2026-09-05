<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Boot the trait.
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logModelActivity($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = [];
            $new = [];
            
            // Log dirty fields
            foreach ($model->getDirty() as $key => $value) {
                // Skip common audit and timestamp fields to keep logs clean
                if (in_array($key, ['updated_at', 'created_at', 'updated_by'])) {
                    continue;
                }
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            if (!empty($new)) {
                static::logModelActivity($model, 'updated', $old, $new);
            }
        });

        static::deleted(function ($model) {
            static::logModelActivity($model, 'deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Store the activity log record.
     */
    protected static function logModelActivity($model, string $action, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();
        $marqueeId = null;

        // Determine tenant / marquee_id
        if ($user && method_exists($user, 'getActiveMarqueeId') && $user->getActiveMarqueeId()) {
            $marqueeId = $user->getActiveMarqueeId();
        } elseif ($user && $user->marquee_id) {
            $marqueeId = $user->marquee_id;
        } elseif (isset($model->marquee_id) && $model->marquee_id) {
            $marqueeId = $model->marquee_id;
        } elseif (isset($model->booking) && isset($model->booking->marquee_id)) {
            $marqueeId = $model->booking->marquee_id;
        }

        // Build human-friendly descriptive message
        $modelName = class_basename($model);
        if ($modelName === 'Booking' && !empty($model->booking_number)) {
            $description = "Booking #{$model->booking_number} was {$action}";
        } elseif ($modelName === 'BookingPayment' && !empty($model->payment_number)) {
            $amountStr = isset($model->amount) ? ' (Rs. ' . number_format($model->amount, 2) . ')' : '';
            $description = "Payment #{$model->payment_number}{$amountStr} was {$action}";
        } elseif ($modelName === 'Customer' && !empty($model->full_name)) {
            $description = "Customer '{$model->full_name}' was {$action}";
        } elseif ($modelName === 'Lead' && !empty($model->client_name)) {
            $description = "Inquiry Lead for '{$model->client_name}' was {$action}";
        } elseif ($modelName === 'BookingFinalBill') {
            $bNum = $model->booking ? " for Booking #{$model->booking->booking_number}" : '';
            $description = "Final Bill{$bNum} was {$action}";
        } elseif ($modelName === 'Expense' && !empty($model->expense_number)) {
            $description = "Expense voucher #{$model->expense_number} was {$action}";
        } elseif ($modelName === 'JournalVoucher' && !empty($model->voucher_number)) {
            $description = "Journal Voucher #{$model->voucher_number} was {$action}";
        } elseif ($modelName === 'User' && !empty($model->name)) {
            $description = "User account '{$model->name}' was {$action}";
        } else {
            $description = "{$modelName} (ID: {$model->id}) was {$action}";
        }

        // Safely check if database is running and table exists before logging
        // (Prevents failures during database seeding/migrations if table isn't created yet)
        try {
            ActivityLog::create([
                'marquee_id' => $marqueeId,
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Silently catch exceptions during early migration / CLI boots
        }
    }
}
