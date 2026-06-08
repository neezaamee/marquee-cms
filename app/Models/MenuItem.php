<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'category_id',
        'item_name',
        'item_code',
        'description',
        'unit',
        'base_cost',
        'selling_price',
        'image',
        'status',
        'created_by',
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (MenuItem $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Get the category associated with this menu item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    /**
     * Get the user who registered this menu item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the packages that include this menu item.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_menu_items')
            ->withPivot('quantity', 'display_order');
    }

    /**
     * Get the URL for the item image or a placeholder.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return Storage::url($this->image);
        }

        return 'https://placehold.co/100x100?text=' . urlencode($this->item_name);
    }
}
