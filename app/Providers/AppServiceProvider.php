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
        // Fix MySQL utf8mb4 key length limit for older MySQL/MariaDB servers
        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

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
