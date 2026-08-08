<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarqueeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\HallSlotAssignmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ExtraServiceController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AccountingController;
use Illuminate\Support\Facades\Route;

// Redirect homepage to dashboard, which will prompt authentication check
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/debug-cache', function () {
    try {
        $output = [];
        \Artisan::call('config:clear');
        $output[] = 'config:clear success';
        \Artisan::call('cache:clear');
        $output[] = 'cache:clear success';
        
        $output[] = 'DB Database: ' . config('database.connections.mysql.database');
        $output[] = 'DB Username: ' . config('database.connections.mysql.username');
        $output[] = 'DB Host: ' . config('database.connections.mysql.host');
        
        return response()->json($output);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Authentication Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Onboarding Setup Wizard Route
    Route::get('/setup', \App\Livewire\SetupWizard::class)->name('setup.wizard');

    // Operational Routes (Blocked unless initial setup is completed)
    Route::middleware('setup.completed')->group(function () {
        Route::resource('marquees', MarqueeController::class);
        Route::resource('branches', BranchController::class);
        Route::resource('users', UserController::class);
        Route::get('staff/{staff}/logins', [StaffController::class, 'logins'])->name('staff.logins');
        Route::resource('staff', StaffController::class);
        Route::get('customers/referral-analytics', [CustomerController::class, 'referralAnalytics'])->name('customers.referral-analytics');
        Route::resource('customers', CustomerController::class);
        Route::resource('event-types', EventTypeController::class);
        Route::resource('extra-services', ExtraServiceController::class);
        
        // Booking Management
        Route::get('bookings/report', [BookingController::class, 'report'])->name('bookings.report');
        Route::get('bookings/{booking}/slip', [BookingController::class, 'slip'])->name('bookings.slip');
        Route::get('bookings/{booking}/slip-v2', [BookingController::class, 'slipV2'])->name('bookings.slip-v2');
        Route::get('bookings/{booking}/slip-v3', [BookingController::class, 'slipV3'])->name('bookings.slip-v3');
        Route::get('bookings/{booking}/kitchen-slip', [BookingController::class, 'kitchenSlip'])->name('bookings.kitchen-slip');
        Route::get('bookings/{booking}/pdf', [BookingController::class, 'downloadPdf'])->name('bookings.pdf');
        Route::get('bookings/payments/{payment}/receipt', [BookingController::class, 'paymentReceipt'])->name('bookings.payment-receipt');
        Route::resource('bookings', BookingController::class);
        
        // Menu Management
        Route::resource('menu-categories', MenuCategoryController::class);
        Route::resource('menu-items', MenuItemController::class);
        
        Route::get('packages/{package}/builder', [PackageController::class, 'builder'])->name('packages.builder');
        Route::get('packages/{package}/preview', [PackageController::class, 'preview'])->name('packages.preview');
        Route::resource('packages', PackageController::class);
        
        // Availability Engine
        Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
        
        // Halls, Slots, and Assignments
        Route::resource('halls', HallController::class);
        Route::resource('slots', SlotController::class);
        Route::get('hall-slots', [HallSlotAssignmentController::class, 'index'])->name('hall-slots.index');

        // Central Finance & Billing Module
        Route::get('finance/revenue', [FinanceController::class, 'revenue'])->name('finance.revenue');
        Route::get('finance/payments', [FinanceController::class, 'payments'])->name('finance.payments');
        Route::get('finance/security-deposits', [FinanceController::class, 'securityDeposits'])->name('finance.security-deposits');

        // Accounting Foundation Module
        Route::get('finance/financial-years', [AccountingController::class, 'financialYears'])->name('finance.financial-years');
        Route::get('finance/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('finance.chart-of-accounts');
        Route::get('finance/opening-balances', [AccountingController::class, 'openingBalances'])->name('finance.opening-balances');
        Route::get('finance/journal-vouchers', [AccountingController::class, 'journalVouchers'])->name('finance.journal-vouchers.index');
        Route::get('finance/journal-vouchers/create', [AccountingController::class, 'createJournalVoucher'])->name('finance.journal-vouchers.create');
        Route::get('finance/journal-vouchers/{id}/edit', [AccountingController::class, 'editJournalVoucher'])->name('finance.journal-vouchers.edit');
        Route::get('finance/general-ledger', [AccountingController::class, 'generalLedger'])->name('finance.general-ledger');
        Route::get('finance/trial-balance', [AccountingController::class, 'trialBalance'])->name('finance.trial-balance');
        Route::get('finance/profit-loss', [AccountingController::class, 'profitLoss'])->name('finance.profit-loss');
        Route::get('finance/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('finance.balance-sheet');
        Route::get('finance/cash-bank', [AccountingController::class, 'cashBank'])->name('finance.cash-bank');

        // Inventory Foundation Module
        Route::view('inventory/categories', 'inventory.categories')->name('inventory.categories');
        Route::view('inventory/units', 'inventory.units')->name('inventory.units');
        Route::view('inventory/brands', 'inventory.brands')->name('inventory.brands');
        Route::view('inventory/items', 'inventory.items')->name('inventory.items');
        Route::view('inventory/stock', 'inventory.stock')->name('inventory.stock');
        Route::view('inventory/settings', 'inventory.settings')->name('inventory.settings');

        // Supplier Directory Module
        Route::view('inventory/suppliers', 'inventory.suppliers')->name('suppliers.index');
        Route::get('inventory/suppliers/{supplier}/ledger', function (\App\Models\Supplier $supplier) {
            return view('inventory.supplier-ledger', compact('supplier'));
        })->name('suppliers.ledger');

        // Purchase Management Module
        Route::view('purchases/orders', 'purchases.orders')->name('purchase-orders.index');
        Route::get('purchases/orders/create', function () {
            return view('purchases.order-form', ['id' => null]);
        })->name('purchase-orders.create');
        Route::get('purchases/orders/{id}/edit', function ($id) {
            return view('purchases.order-form', compact('id'));
        })->name('purchase-orders.edit');

        Route::view('purchases/receipts', 'purchases.receipts')->name('goods-receipts.index');
        Route::get('purchases/receipts/create', function () {
            return view('purchases.receipt-form', ['id' => null]);
        })->name('goods-receipts.create');
        Route::get('purchases/receipts/{id}', function ($id) {
            return view('purchases.receipt-form', compact('id'));
        })->name('goods-receipts.show');

        Route::view('purchases/invoices', 'purchases.invoices')->name('purchase-invoices.index');
        Route::get('purchases/invoices/create', function () {
            return view('purchases.invoice-form', ['id' => null]);
        })->name('purchase-invoices.create');
        Route::get('purchases/invoices/{id}/edit', function ($id) {
            return view('purchases.invoice-form', compact('id'));
        })->name('purchase-invoices.edit');

        Route::view('purchases/returns', 'purchases.returns')->name('purchase-returns.index');
        Route::get('purchases/returns/create', function () {
            return view('purchases.return-form', ['id' => null]);
        })->name('purchase-returns.create');
        Route::get('purchases/returns/{id}/edit', function ($id) {
            return view('purchases.return-form', compact('id'));
        })->name('purchase-returns.edit');

        // Client-facing SaaS Billing & Online Stripe Payments
        Route::get('billing', [\App\Http\Controllers\TenantBillingController::class, 'index'])->name('billing.index');
        Route::get('billing/success', [\App\Http\Controllers\TenantBillingController::class, 'success'])->name('billing.success');
        Route::get('billing/cancel', [\App\Http\Controllers\TenantBillingController::class, 'cancel'])->name('billing.cancel');

        // Expense Management Module
        Route::get('expenses/dashboard', [\App\Http\Controllers\ExpenseController::class, 'dashboard'])->name('expenses.dashboard');
        Route::get('expenses/categories', [\App\Http\Controllers\ExpenseController::class, 'categories'])->name('expenses.categories');
        Route::get('expenses/petty-cash', [\App\Http\Controllers\ExpenseController::class, 'pettyCash'])->name('expenses.petty-cash');
        Route::get('expenses/budgets', [\App\Http\Controllers\ExpenseController::class, 'budgets'])->name('expenses.budgets');
        Route::get('expenses/recurring', [\App\Http\Controllers\ExpenseController::class, 'recurring'])->name('expenses.recurring');
        Route::get('expenses/reports', [\App\Http\Controllers\ExpenseController::class, 'reports'])->name('expenses.reports');
        Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);

        // Kitchen & Event Operations Sprint Routes
        Route::get('staff-attendance', \App\Livewire\StaffAttendance::class)->name('staff.attendance');
        Route::get('recipes', \App\Livewire\RecipeList::class)->name('recipes.index');
        Route::get('operations/checklists', \App\Livewire\EventChecklistManager::class)->name('operations.checklists');

        // Vendor & Partnership Management Module
        Route::get('vendors/dashboard', \App\Livewire\VendorDashboard::class)->name('vendors.dashboard');
        Route::get('vendors', \App\Livewire\VendorManager::class)->name('vendors.index');
        Route::get('vendors/services', \App\Livewire\VendorServiceManager::class)->name('vendor-services.index');
        Route::get('vendors/agreements', \App\Livewire\VendorAgreementManager::class)->name('vendor-agreements.index');
        Route::get('vendors/sales', \App\Livewire\VendorSaleManager::class)->name('vendor-sales.index');
        Route::get('vendors/ledger', \App\Livewire\VendorLedgerView::class)->name('vendor-ledger.index');
        Route::get('vendors/settlements', \App\Livewire\VendorSettlementManager::class)->name('vendor-settlements.index');
        Route::get('vendors/reports', \App\Livewire\VendorReports::class)->name('vendor-reports.index');
        Route::get('vendors/{vendor}', \App\Livewire\VendorDetail::class)->name('vendors.show');

        // Department Management Module
        Route::get('departments/dashboard', \App\Livewire\DepartmentDashboard::class)->name('departments.dashboard');
        Route::get('departments', \App\Livewire\DepartmentManager::class)->name('departments.index');
        Route::get('departments/employees', \App\Livewire\DepartmentEmployeeManager::class)->name('departments.employees');
        Route::get('departments/attendance', \App\Livewire\DepartmentAttendanceManager::class)->name('departments.attendance');
        Route::get('departments/requests', \App\Livewire\DepartmentRequestManager::class)->name('departments.requests');
        Route::get('departments/issue', \App\Livewire\DepartmentIssueManager::class)->name('departments.issue');
        Route::get('departments/returns', \App\Livewire\DepartmentReturnManager::class)->name('departments.returns');
        Route::get('departments/ledger', \App\Livewire\DepartmentLedgerView::class)->name('departments.ledger');
        Route::get('departments/production', \App\Livewire\DepartmentProductionManager::class)->name('departments.production');
        Route::get('departments/reports', \App\Livewire\DepartmentReports::class)->name('departments.reports');
    });

    // SaaS Subscription Management & Global Defaults (Super Admin only)
    Route::resource('subscription-plans', \App\Http\Controllers\SubscriptionPlanController::class);
    Route::resource('plan-features', \App\Http\Controllers\PlanFeatureController::class);
    Route::resource('billing-cycles', \App\Http\Controllers\BillingCycleController::class);
    Route::resource('saas-invoices', \App\Http\Controllers\SaasInvoiceController::class);
    Route::resource('saas-payments', \App\Http\Controllers\SaasPaymentController::class);
    Route::get('admin/global-defaults', \App\Livewire\SuperAdmin\GlobalDefaultManager::class)->name('super-admin.global-defaults');
    Route::get('settings/default-data', \App\Livewire\Owner\TenantDefaultManager::class)->name('owner.default-data');
});
