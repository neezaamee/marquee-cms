<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Package extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'package_name',
        'package_code',
        'description',
        'package_type',
        'minimum_guests',
        'maximum_guests',
        'base_price',
        'per_plate_price',
        'seasonal_package',
        'season_start_date',
        'season_end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'minimum_guests' => 'integer',
        'maximum_guests' => 'integer',
        'base_price' => 'decimal:2',
        'per_plate_price' => 'decimal:2',
        'seasonal_package' => 'boolean',
        'season_start_date' => 'date',
        'season_end_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Package $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Scope to only get active and seasonal-valid packages for booking.
     */
    public function scopeActiveForBooking(Builder $query): Builder
    {
        $today = Carbon::today()->format('Y-m-d');

        return $query->where('status', 'Active')
            ->where(function ($q) use ($today) {
                $q->where('seasonal_package', false)
                  ->orWhere(function ($sq) use ($today) {
                      $sq->where('seasonal_package', true)
                        ->whereNotNull('season_start_date')
                        ->whereNotNull('season_end_date')
                        ->where('season_start_date', '<=', $today)
                        ->where('season_end_date', '>=', $today);
                  });
            });
    }

    /**
     * Helper to check if the seasonal package is currently active.
     */
    public function isSeasonalActive(): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        if (!$this->seasonal_package) {
            return true;
        }

        if (!$this->season_start_date || !$this->season_end_date) {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->season_start_date, $this->season_end_date);
    }

    /**
     * Get the user who registered this package.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the menu items associated with this package.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'package_menu_items')
            ->withPivot('quantity', 'display_order')
            ->orderBy('package_menu_items.display_order')
            ->orderBy('menu_items.item_name');
    }
}
