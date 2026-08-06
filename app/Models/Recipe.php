<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'menu_item_id',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the menu item this recipe belongs to.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Get the details (ingredients) for this recipe.
     */
    public function details(): HasMany
    {
        return $this->hasMany(RecipeDetail::class);
    }
}
