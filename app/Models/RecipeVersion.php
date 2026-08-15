<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class RecipeVersion extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'recipe_id',
        'version_number',
        'is_active',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version_number' => 'integer',
    ];

    /**
     * Get the details (ingredients) for this recipe version.
     */
    public function details(): HasMany
    {
        return $this->hasMany(RecipeVersionDetail::class, 'recipe_version_id');
    }

    /**
     * Get the parent recipe.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the branch, if this version is a branch override.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created this version.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Set only this version as active within the tenant + recipe + branch scope using DB row-locking.
     */
    public static function makeVersionActive(int $marqueeId, int $recipeId, ?int $branchId, int $versionId): void
    {
        DB::transaction(function () use ($marqueeId, $recipeId, $branchId, $versionId) {
            // Lock other active versions in this scope
            self::where('marquee_id', $marqueeId)
                ->where('recipe_id', $recipeId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->update(['is_active' => false]);

            // Lock and activate the target version
            $version = self::where('marquee_id', $marqueeId)
                ->where('id', $versionId)
                ->lockForUpdate()
                ->firstOrFail();

            $version->update(['is_active' => true]);
        });
    }
}
