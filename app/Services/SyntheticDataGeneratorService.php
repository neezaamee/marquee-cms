<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerCommunicationLog;
use App\Models\CustomerLedger;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EventType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraService;
use App\Models\Hall;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherItem;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Role;
use App\Models\Slot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSale;
use App\Models\VendorService;
use Carbon\Carbon;
use Database\Seeders\AccountingModuleSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\ExpenseModuleSeeder;
use Database\Seeders\InventoryModuleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyntheticDataGeneratorService
{
    public function __construct(
        protected BookingFinancialService $financialService,
        protected RevenueRecognitionService $recognitionService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Get real-time metric snapshot for a marquee tenant.
     */
    public function getTenantStats(int $marqueeId): array
    {
        return [
            'customers_count' => Customer::where('marquee_id', $marqueeId)->count(),
            'bookings_count' => Booking::where('marquee_id', $marqueeId)->count(),
            'confirmed_bookings' => Booking::where('marquee_id', $marqueeId)->where('booking_status', 'Confirmed')->count(),
            'completed_bookings' => Booking::where('marquee_id', $marqueeId)->where('booking_status', 'Completed')->count(),
            'payments_count' => BookingPayment::whereHas('booking', fn($q) => $q->where('marquee_id', $marqueeId))->count(),
            'total_payments_amount' => BookingPayment::whereHas('booking', fn($q) => $q->where('marquee_id', $marqueeId))->sum('amount'),
            'staff_count' => Employee::where('marquee_id', $marqueeId)->count(),
            'inventory_items_count' => InventoryItem::where('marquee_id', $marqueeId)->count(),
            'suppliers_count' => Supplier::where('marquee_id', $marqueeId)->count(),
            'vendors_count' => Vendor::where('marquee_id', $marqueeId)->count(),
            'expenses_count' => Expense::where('marquee_id', $marqueeId)->count(),
            'total_expenses_amount' => Expense::where('marquee_id', $marqueeId)->sum('total_amount'),
            'journal_vouchers_count' => JournalVoucher::where('marquee_id', $marqueeId)->count(),
        ];
    }

    /**
     * Ensure baseline master configurations exist for the marquee.
     */
    public function ensureMasterConfigurations(Marquee $marquee): void
    {
        // 1. Ensure at least one branch
        $branches = $marquee->branches;
        if ($branches->isEmpty()) {
            Branch::factory()->headOffice()->create(['marquee_id' => $marquee->id]);
        }

        // 2. Ensure standard Chart of Accounts
        $cashAcc = Account::where('marquee_id', $marquee->id)->where('account_code', '1001')->first();
        if (!$cashAcc) {
            app(AccountingModuleSeeder::class)->run();
        }

        // 3. Ensure master departments, inventory categories, expense categories
        if (Department::where('marquee_id', $marquee->id)->count() === 0) {
            app(DepartmentSeeder::class)->run();
        }
        if (InventoryCategory::where('marquee_id', $marquee->id)->count() === 0) {
            app(InventoryModuleSeeder::class)->run();
        }
        if (ExpenseCategory::where('marquee_id', $marquee->id)->count() === 0) {
            app(ExpenseModuleSeeder::class)->run();
        }

        // 4. Ensure Halls, Slots, and Event Types exist
        $mainBranch = $marquee->branches()->first();
        if (Hall::where('marquee_id', $marquee->id)->count() === 0 && $mainBranch) {
            Hall::factory()->marquee()->create(['marquee_id' => $marquee->id, 'branch_id' => $mainBranch->id]);
            Hall::factory()->banquet()->create(['marquee_id' => $marquee->id, 'branch_id' => $mainBranch->id]);
        }

        if (Slot::where('marquee_id', $marquee->id)->count() === 0) {
            Slot::factory()->create(['marquee_id' => $marquee->id, 'slot_name' => 'Night / Dinner Shift', 'start_time' => '19:00:00', 'end_time' => '23:30:00']);
            Slot::factory()->create(['marquee_id' => $marquee->id, 'slot_name' => 'Lunch / Day Shift', 'start_time' => '12:00:00', 'end_time' => '16:30:00']);
        }

        if (EventType::where('marquee_id', $marquee->id)->count() === 0) {
            EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Wedding Barat', 'event_type_code' => 'BARAT-01']);
            EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Walima Reception', 'event_type_code' => 'WALIMA-01']);
            EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Mehndi Night', 'event_type_code' => 'MEHNDI-01']);
            EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Corporate Gala Dinner', 'event_type_code' => 'CORP-01']);
        }
    }

    /**
     * Generate synthetic customers with CRM history.
     */
    public function generateCustomers(int $marqueeId, int $count = 15, bool $withLogs = true): array
    {
        $adminUser = User::where('marquee_id', $marqueeId)->first() ?? User::where('role_id', 1)->first() ?? User::first();
        $customers = Customer::factory()->count($count)->create(['marquee_id' => $marqueeId]);

        $logCount = 0;
        if ($withLogs && $adminUser) {
            foreach ($customers->take(ceil($count * 0.6)) as $cust) {
                $logs = CustomerCommunicationLog::factory()->count(rand(1, 3))->create([
                    'customer_id' => $cust->id,
                    'logged_by' => $adminUser->id,
                ]);
                $logCount += $logs->count();
            }
        }

        return [
            'customers_created' => $customers->count(),
            'crm_logs_created' => $logCount,
        ];
    }

    /**
     * Generate operational staff and attendance history.
     */
    public function generateStaffAndAttendance(int $marqueeId, int $staffCount = 8, int $attendanceDays = 10, ?int $branchId = null): array
    {
        $marquee = Marquee::findOrFail($marqueeId);
        $branch = $branchId ? Branch::findOrFail($branchId) : ($marquee->branches->first() ?? Branch::factory()->create(['marquee_id' => $marqueeId]));
        $departments = Department::where('marquee_id', $marqueeId)->get();

        if ($departments->isEmpty()) {
            $departments = collect([
                Department::factory()->create(['marquee_id' => $marqueeId, 'name' => 'Main Kitchen', 'type' => 'Kitchen']),
                Department::factory()->create(['marquee_id' => $marqueeId, 'name' => 'Banquet Service', 'type' => 'Service']),
            ]);
        }

        $employees = collect();
        for ($i = 0; $i < $staffCount; $i++) {
            $dept = $departments->random();
            $emp = Employee::factory()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
            ]);
            $employees->push($emp);
        }

        $attendanceCount = 0;
        foreach ($employees as $emp) {
            for ($d = 0; $d < $attendanceDays; $d++) {
                $date = Carbon::now()->subDays($d);
                if ($date->isSunday()) continue;

                Attendance::factory()->create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branch->id,
                    'employee_id' => $emp->id,
                    'date' => $date->format('Y-m-d'),
                    'created_by' => null,
                ]);
                $attendanceCount++;
            }
        }

        return [
            'staff_created' => $employees->count(),
            'attendance_records_created' => $attendanceCount,
        ];
    }

    /**
     * Generate additional branches with halls for a marquee.
     */
    public function generateBranches(int $marqueeId, int $count = 2): array
    {
        $marquee = Marquee::findOrFail($marqueeId);
        $locations = [
            'Lahore - Gulberg Suite',
            'Lahore - DHA Phase 5 Arena',
            'Lahore - Johar Town Complex',
            'Islamabad - Blue Area Grand',
            'Rawalpindi - Bahria Town Hall',
            'Karachi - Clifton Pavilion',
        ];

        $createdBranches = 0;
        $createdHalls = 0;

        for ($i = 0; $i < $count; $i++) {
            $branchName = fake()->randomElement($locations) . ' ' . fake()->numerify('###');
            $branch = Branch::factory()->create([
                'marquee_id' => $marqueeId,
                'name' => $branchName,
                'is_head_office' => false,
            ]);
            $createdBranches++;

            // Create 2 halls for this new branch
            Hall::factory()->marquee()->create(['marquee_id' => $marqueeId, 'branch_id' => $branch->id]);
            Hall::factory()->banquet()->create(['marquee_id' => $marqueeId, 'branch_id' => $branch->id]);
            $createdHalls += 2;
        }

        return [
            'branches_created' => $createdBranches,
            'halls_created' => $createdHalls,
        ];
    }

    /**
     * Generate suppliers and inventory raw materials.
     */
    public function generateSupplyChain(int $marqueeId, int $suppliersCount = 5, int $itemsCount = 15): array
    {
        $suppliers = Supplier::factory()->count($suppliersCount)->create(['marquee_id' => $marqueeId]);

        $categories = InventoryCategory::where('marquee_id', $marqueeId)->get();
        if ($categories->isEmpty()) {
            $categories = collect([InventoryCategory::factory()->create(['marquee_id' => $marqueeId])]);
        }

        $units = InventoryUnit::where('marquee_id', $marqueeId)->get();
        if ($units->isEmpty()) {
            $units = collect([InventoryUnit::factory()->create(['marquee_id' => $marqueeId])]);
        }

        $items = collect();
        for ($i = 0; $i < $itemsCount; $i++) {
            $item = InventoryItem::factory()->create([
                'marquee_id' => $marqueeId,
                'category_id' => $categories->random()->id,
                'unit_id' => $units->random()->id,
            ]);
            $items->push($item);
        }

        return [
            'suppliers_created' => $suppliers->count(),
            'inventory_items_created' => $items->count(),
        ];
    }

    /**
     * Generate partner vendors and service catalogs.
     */
    public function generateVendorPartners(int $marqueeId, int $vendorCount = 4, int $servicesPerVendor = 2): array
    {
        $vendors = Vendor::factory()->count($vendorCount)->create(['marquee_id' => $marqueeId]);
        $servicesCount = 0;

        foreach ($vendors as $vendor) {
            $services = VendorService::factory()->count($servicesPerVendor)->create([
                'marquee_id' => $marqueeId,
                'vendor_id' => $vendor->id,
            ]);
            $servicesCount += $services->count();
        }

        return [
            'vendors_created' => $vendors->count(),
            'vendor_services_created' => $servicesCount,
        ];
    }

    /**
     * Generate operating expenses.
     */
    public function generateExpenses(int $marqueeId, int $count = 10, ?int $branchId = null): array
    {
        $marquee = Marquee::findOrFail($marqueeId);
        $branches = $marquee->branches;
        $branch = $branchId ? Branch::findOrFail($branchId) : ($branches->first() ?? Branch::factory()->create(['marquee_id' => $marqueeId]));
        $currency = Currency::where('marquee_id', $marqueeId)->first() ?? Currency::factory()->create(['marquee_id' => $marqueeId]);
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->get();

        if ($categories->isEmpty()) {
            $categories = collect([ExpenseCategory::factory()->create(['marquee_id' => $marqueeId])]);
        }

        $expenses = collect();
        for ($i = 0; $i < $count; $i++) {
            $targetBranch = $branchId ? $branch : ($branches->random() ?? $branch);
            $exp = Expense::factory()->paid()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $targetBranch->id,
                'expense_category_id' => $categories->random()->id,
                'currency_id' => $currency->id,
            ]);
            $expenses->push($exp);
        }

        return [
            'expenses_created' => $expenses->count(),
            'total_amount' => $expenses->sum('total_amount'),
        ];
    }

    /**
     * Generate rich bookings with full accounting integration.
     */
    public function generateBookings(int $marqueeId, int $count = 20, array $options = []): array
    {
        $marquee = Marquee::findOrFail($marqueeId);
        $branches = $marquee->branches;
        if ($branches->isEmpty()) {
            $branches = collect([Branch::factory()->create(['marquee_id' => $marqueeId])]);
        }
        $adminUser = User::where('marquee_id', $marqueeId)->first() ?? User::where('role_id', 1)->first() ?? User::first();

        $halls = Hall::where('marquee_id', $marqueeId)->get();
        if ($halls->isEmpty()) {
            $halls = collect([Hall::factory()->create(['marquee_id' => $marqueeId, 'branch_id' => $branches->first()->id])]);
        }

        $slots = Slot::where('marquee_id', $marqueeId)->get();
        if ($slots->isEmpty()) {
            $slots = collect([Slot::factory()->create(['marquee_id' => $marqueeId])]);
        }

        $eventTypes = EventType::where('marquee_id', $marqueeId)->get();
        if ($eventTypes->isEmpty()) {
            $eventTypes = collect([EventType::factory()->create(['marquee_id' => $marqueeId])]);
        }

        $customers = Customer::where('marquee_id', $marqueeId)->get();
        if ($customers->isEmpty()) {
            $customers = Customer::factory()->count(10)->create(['marquee_id' => $marqueeId]);
        }

        $cashAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->first()
            ?? Account::factory()->cash()->create(['marquee_id' => $marqueeId]);
        $bankAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '1002')->first()
            ?? Account::factory()->bank()->create(['marquee_id' => $marqueeId]);

        $createdBookings = 0;
        $createdPayments = 0;
        $recognizedRevenue = 0;
        $finalSettlements = 0;

        // Partition count: ~50% Upcoming with Advance, ~30% Past Completed & Settled, ~10% Today's Live, ~10% Drafts
        $upcomingCount = max(1, (int) round($count * 0.50));
        $pastCount = max(1, (int) round($count * 0.30));
        $todayCount = max(1, (int) round($count * 0.10));
        $draftCount = max(1, $count - ($upcomingCount + $pastCount + $todayCount));

        // Helper to get branch & hall
        $getBranchAndHall = function () use ($branches, $halls) {
            $b = $branches->random();
            $bHalls = $halls->where('branch_id', $b->id);
            $h = $bHalls->isNotEmpty() ? $bHalls->random() : $halls->random();
            return [$b, $h];
        };

        // 1. Upcoming Bookings with Advance Payments
        for ($i = 0; $i < $upcomingCount; $i++) {
            [$branch, $hall] = $getBranchAndHall();
            $booking = Booking::factory()->upcoming()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id,
                'hall_id' => $hall->id,
                'slot_id' => $slots->random()->id,
                'event_type_id' => $eventTypes->random()->id,
                'created_by' => $adminUser->id,
            ]);
            $createdBookings++;

            $advanceAmount = round(($booking->grand_total * rand(30, 50)) / 100, -3);
            $payAcc = rand(0, 1) ? $cashAccount : $bankAccount;

            $this->financialService->recordPayment($booking, [
                'amount' => $advanceAmount,
                'payment_date' => Carbon::now()->subDays(rand(1, 15))->format('Y-m-d'),
                'payment_method' => $payAcc->account_code === '1001' ? 'Cash' : 'Bank Transfer',
                'account_id' => $payAcc->id,
                'payment_type' => 'advance',
                'transaction_reference' => 'ADV-' . rand(10000, 99999),
                'notes' => 'Token advance deposit against wedding booking',
                'recorded_by' => $adminUser->id,
            ]);
            $createdPayments++;
        }

        // 2. Today's Events
        for ($i = 0; $i < $todayCount; $i++) {
            [$branch, $hall] = $getBranchAndHall();
            $booking = Booking::factory()->today()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id,
                'hall_id' => $hall->id,
                'slot_id' => $slots->random()->id,
                'event_type_id' => $eventTypes->random()->id,
                'created_by' => $adminUser->id,
            ]);
            $createdBookings++;

            $advanceAmount = round($booking->grand_total * 0.5, -3);
            $this->financialService->recordPayment($booking, [
                'amount' => $advanceAmount,
                'payment_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
                'payment_method' => 'Cash',
                'account_id' => $cashAccount->id,
                'payment_type' => 'advance',
                'notes' => 'Advance payment for today event',
                'recorded_by' => $adminUser->id,
            ]);
            $createdPayments++;
        }

        // 3. Past Completed & Financially Settled Events
        for ($i = 0; $i < $pastCount; $i++) {
            [$branch, $hall] = $getBranchAndHall();
            $booking = Booking::factory()->past()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id,
                'hall_id' => $hall->id,
                'slot_id' => $slots->random()->id,
                'event_type_id' => $eventTypes->random()->id,
                'created_by' => $adminUser->id,
            ]);
            $createdBookings++;

            // Initial advance
            $advanceAmount = round($booking->grand_total * 0.6, -3);
            $this->financialService->recordPayment($booking, [
                'amount' => $advanceAmount,
                'payment_date' => Carbon::parse($booking->booking_date)->subDays(10)->format('Y-m-d'),
                'payment_method' => 'Cash',
                'account_id' => $cashAccount->id,
                'payment_type' => 'advance',
                'notes' => 'Initial booking advance deposit',
                'recorded_by' => $adminUser->id,
            ]);
            $createdPayments++;

            // Recognize Revenue
            $this->recognitionService->recognizeRevenue($booking, $booking->booking_date->format('Y-m-d'), $adminUser->id);
            $booking->refresh();
            $recognizedRevenue++;

            // Settle remainder
            $remaining = (float) $booking->receivable_amount;
            if ($remaining > 0) {
                $this->financialService->recordPayment($booking, [
                    'amount' => $remaining,
                    'payment_date' => Carbon::parse($booking->booking_date)->addDay()->format('Y-m-d'),
                    'payment_method' => 'Bank Transfer',
                    'account_id' => $bankAccount->id,
                    'payment_type' => 'receivable_payment',
                    'notes' => 'Post-event final balance settlement',
                    'recorded_by' => $adminUser->id,
                ]);
                $createdPayments++;
                $finalSettlements++;
            }
        }

        // 4. Drafts
        for ($i = 0; $i < $draftCount; $i++) {
            [$branch, $hall] = $getBranchAndHall();
            Booking::factory()->draft()->upcoming()->create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id,
                'hall_id' => $hall->id,
                'slot_id' => $slots->random()->id,
                'event_type_id' => $eventTypes->random()->id,
                'created_by' => $adminUser->id,
            ]);
            $createdBookings++;
        }

        return [
            'bookings_created' => $createdBookings,
            'payments_recorded' => $createdPayments,
            'revenue_recognized_count' => $recognizedRevenue,
            'final_settlements_count' => $finalSettlements,
        ];
    }

    /**
     * Purge transactional and demo data safely for a marquee.
     */
    public function purgeSyntheticData(int $marqueeId, bool $keepMasterCatalogs = true): array
    {
        return DB::transaction(function () use ($marqueeId, $keepMasterCatalogs) {
            $bookingIds = Booking::where('marquee_id', $marqueeId)->pluck('id');
            $deletedBookings = $bookingIds->count();
            $deletedPayments = BookingPayment::whereIn('booking_id', $bookingIds)->count();
            $deletedJVs = JournalVoucher::where('marquee_id', $marqueeId)->count();
            $deletedExpenses = Expense::where('marquee_id', $marqueeId)->count();
            $deletedCustomers = Customer::where('marquee_id', $marqueeId)->count();
            $deletedEmployees = Employee::where('marquee_id', $marqueeId)->count();
            $deletedSuppliers = Supplier::where('marquee_id', $marqueeId)->count();
            $deletedVendors = Vendor::where('marquee_id', $marqueeId)->count();

            // 1. Delete transactional data
            BookingPayment::whereIn('booking_id', $bookingIds)->delete();
            CustomerLedger::where('marquee_id', $marqueeId)->forceDelete();
            VendorSale::where('marquee_id', $marqueeId)->forceDelete();
            
            // Delete bookings and items
            DB::table('booking_menu_items')->whereIn('booking_id', $bookingIds)->delete();
            DB::table('booking_extra_services')->whereIn('booking_id', $bookingIds)->delete();
            DB::table('booking_halls')->whereIn('booking_id', $bookingIds)->delete();
            DB::table('booking_histories')->whereIn('booking_id', $bookingIds)->delete();
            DB::table('booking_final_bills')->whereIn('booking_id', $bookingIds)->delete();
            Booking::where('marquee_id', $marqueeId)->forceDelete();

            // Delete Journal vouchers & items
            $jvIds = JournalVoucher::where('marquee_id', $marqueeId)->pluck('id');
            JournalVoucherItem::whereIn('journal_voucher_id', $jvIds)->forceDelete();
            JournalVoucher::where('marquee_id', $marqueeId)->forceDelete();

            // Delete Expenses
            Expense::where('marquee_id', $marqueeId)->forceDelete();

            // Delete Attendance & Staff
            $empIds = Employee::where('marquee_id', $marqueeId)->pluck('id');
            Attendance::whereIn('employee_id', $empIds)->delete();
            Employee::where('marquee_id', $marqueeId)->forceDelete();

            // Delete Customer CRM logs and customers
            $custIds = Customer::where('marquee_id', $marqueeId)->pluck('id');
            CustomerCommunicationLog::whereIn('customer_id', $custIds)->delete();
            Customer::where('marquee_id', $marqueeId)->forceDelete();

            // Delete Suppliers, items, and vendors if not keeping master
            if (!$keepMasterCatalogs) {
                InventoryItem::where('marquee_id', $marqueeId)->forceDelete();
                Supplier::where('marquee_id', $marqueeId)->forceDelete();
                VendorService::where('marquee_id', $marqueeId)->forceDelete();
                Vendor::where('marquee_id', $marqueeId)->forceDelete();
            }

            return [
                'bookings_deleted' => $deletedBookings,
                'payments_deleted' => $deletedPayments,
                'journal_vouchers_deleted' => $deletedJVs,
                'expenses_deleted' => $deletedExpenses,
                'customers_deleted' => $deletedCustomers,
                'staff_deleted' => $deletedEmployees,
                'suppliers_deleted' => $deletedSuppliers,
                'vendors_deleted' => $deletedVendors,
            ];
        });
    }

    /**
     * Delete an entire demo marquee tenant, including all branches and records.
     */
    public function deleteMarquee(int $marqueeId): array
    {
        if ($marqueeId === 1) {
            throw new \Exception("Cannot delete primary default marquee (ID: 1).");
        }

        return DB::transaction(function () use ($marqueeId) {
            $marquee = Marquee::findOrFail($marqueeId);
            $name = $marquee->name;

            // 1. Purge all transactions, items, staff, customers, expenses
            $this->purgeSyntheticData($marqueeId, false);

            // 2. Delete Halls, Slots, Event Types, Packages, Menu Categories, Accounts
            Hall::where('marquee_id', $marqueeId)->forceDelete();
            Slot::where('marquee_id', $marqueeId)->forceDelete();
            EventType::where('marquee_id', $marqueeId)->forceDelete();
            Package::where('marquee_id', $marqueeId)->forceDelete();
            Account::where('marquee_id', $marqueeId)->forceDelete();
            Department::where('marquee_id', $marqueeId)->forceDelete();
            ExpenseCategory::where('marquee_id', $marqueeId)->forceDelete();
            InventoryCategory::where('marquee_id', $marqueeId)->forceDelete();
            InventoryUnit::where('marquee_id', $marqueeId)->forceDelete();

            // 3. Delete Branches
            $deletedBranches = Branch::where('marquee_id', $marqueeId)->count();
            Branch::where('marquee_id', $marqueeId)->forceDelete();

            // 4. Delete tenant users (non-super-admin)
            User::where('marquee_id', $marqueeId)->where('role_id', '!=', 1)->forceDelete();

            // 5. Delete Marquee
            $marquee->forceDelete();

            return [
                'marquee_name' => $name,
                'branches_deleted' => $deletedBranches,
            ];
        });
    }

    /**
     * Purge all demo marquees except primary tenant.
     */
    public function purgeAllDemoMarquees(int $keepMarqueeId = 1): array
    {
        $demoMarquees = Marquee::where('id', '!=', $keepMarqueeId)->get();
        $deletedCount = 0;

        foreach ($demoMarquees as $m) {
            $this->deleteMarquee($m->id);
            $deletedCount++;
        }

        return [
            'marquees_deleted' => $deletedCount,
        ];
    }
}
