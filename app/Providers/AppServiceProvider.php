<?php

namespace App\Providers;

use App\Models\InventoryCategory;
use App\Models\InventoryUnit;
use App\Models\InventoryBrand;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceivingNote;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Policies\InventoryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\ExpenseRepositoryInterface::class,
            \App\Repositories\ExpenseRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce HTTPS URLs when running in production or configured with HTTPS (prevents Mixed-Content image blocking)
        if ($this->app->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Fix MySQL utf8mb4 key length limit for older MySQL/MariaDB servers
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        // Centralized standard password policy (minimum 8 characters, requiring letters and numbers)
        \Illuminate\Validation\Rules\Password::defaults(function () {
            $rule = \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers();
            return $this->app->environment('production') ? $rule->uncompromised() : $rule;
        });

        // Centralized Gate authorization bridge:
        // 1. Super Admins bypass all authorization checks globally.
        // 2. Named permissions (without model parameters) evaluate against User::hasPermission().
        // 3. Model-specific abilities fall through to dedicated model policies to preserve tenant scoping.
        Gate::before(function ($user, $ability, $params = []) {
            if ($user->isSuperAdmin()) {
                return true;
            }
            if (empty($params) && $user->hasPermission($ability)) {
                return true;
            }
            return null;
        });

        Gate::policy(\App\Models\Booking::class, \App\Policies\BookingPolicy::class);
        Gate::policy(\App\Models\Branch::class, \App\Policies\BranchPolicy::class);
        Gate::policy(\App\Models\Marquee::class, \App\Policies\MarqueePolicy::class);
        Gate::policy(InventoryCategory::class, InventoryPolicy::class);
        Gate::policy(InventoryUnit::class, InventoryPolicy::class);
        Gate::policy(InventoryBrand::class, InventoryPolicy::class);
        Gate::policy(InventoryItem::class, InventoryPolicy::class);
        Gate::policy(Supplier::class, InventoryPolicy::class);
        Gate::policy(PurchaseOrder::class, InventoryPolicy::class);
        Gate::policy(GoodsReceivingNote::class, InventoryPolicy::class);
        Gate::policy(PurchaseInvoice::class, InventoryPolicy::class);
        Gate::policy(PurchaseReturn::class, InventoryPolicy::class);
    }
}
