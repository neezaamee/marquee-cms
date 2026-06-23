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
        if ($user && $user->marquee_id) {
            $marqueeId = $user->marquee_id;
        } elseif (isset($model->marquee_id)) {
            $marqueeId = $model->marquee_id;
        }

        // Build descriptive message
        $modelName = class_basename($model);
        $description = "{$modelName} (ID: {$model->id}) was {$action}";

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
