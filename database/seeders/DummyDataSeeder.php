<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\BookingExtraService;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\CustomerCommunicationLog;
use App\Models\CustomerLedger;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EventType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExtraService;
use App\Models\FinancialYear;
use App\Models\Hall;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Slot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSale;
use App\Models\VendorService;
use App\Services\BookingFinancialService;
use App\Services\RevenueRecognitionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds using Factories.
     */
    public function run(): void
    {
        // 1. Ensure master setup data exists
        if (Marquee::count() === 0) {
            $this->command->info('No marquees found. Bootstrapping DatabaseSeeder first...');
            $this->call(DatabaseSeeder::class);
        } else {
            $this->call([
                AccountingModuleSeeder::class,
                DepartmentSeeder::class,
                InventoryModuleSeeder::class,
                ExpenseModuleSeeder::class,
            ]);
        }

        $marquees = Marquee::all();
        $financialService = app(BookingFinancialService::class);
        $recognitionService = app(RevenueRecognitionService::class);

        foreach ($marquees as $marquee) {
            $this->command->info("Seeding rich testing & demo data for: {$marquee->name}");

            $branches = $marquee->branches;
            if ($branches->isEmpty()) {
                $branches = collect([Branch::factory()->headOffice()->create(['marquee_id' => $marquee->id])]);
            }
            $mainBranch = $branches->first();

            $adminUser = User::where('marquee_id', $marquee->id)->first() ?? User::first();
            $halls = Hall::where('marquee_id', $marquee->id)->get();
            if ($halls->isEmpty()) {
                $halls = collect([
                    Hall::factory()->marquee()->create(['marquee_id' => $marquee->id, 'branch_id' => $mainBranch->id]),
                    Hall::factory()->banquet()->create(['marquee_id' => $marquee->id, 'branch_id' => $mainBranch->id]),
                ]);
            }

            $slots = Slot::where('marquee_id', $marquee->id)->get();
            if ($slots->isEmpty()) {
                $slots = collect([
                    Slot::factory()->create(['marquee_id' => $marquee->id, 'slot_name' => 'Night Shift']),
                    Slot::factory()->create(['marquee_id' => $marquee->id, 'slot_name' => 'Lunch Shift']),
                ]);
            }

            $eventTypes = EventType::where('marquee_id', $marquee->id)->get();
            if ($eventTypes->isEmpty()) {
                $eventTypes = collect([
                    EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Wedding Barat']),
                    EventType::factory()->create(['marquee_id' => $marquee->id, 'event_type_name' => 'Walima Reception']),
                ]);
            }

            $packages = Package::where('marquee_id', $marquee->id)->get();
            $extraServices = ExtraService::where('marquee_id', $marquee->id)->get();
            $departments = Department::where('marquee_id', $marquee->id)->get();

            $cashAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '1001')->first()
                ?? Account::factory()->cash()->create(['marquee_id' => $marquee->id]);
            $bankAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '1002')->first()
                ?? Account::factory()->bank()->create(['marquee_id' => $marquee->id]);

            // 2. Seed 25 Realistic Pakistani Customers & CRM Communication Logs
            $this->command->info('→ Generating Pakistani Customers & CRM History...');
            $customers = Customer::factory()->count(25)->create([
                'marquee_id' => $marquee->id,
            ]);

            foreach ($customers->take(12) as $cust) {
                CustomerCommunicationLog::factory()->count(rand(1, 3))->create([
                    'customer_id' => $cust->id,
                    'logged_by' => $adminUser->id,
                ]);
            }

            // 3. Seed Staff / Employees & Monthly Attendance
            $this->command->info('→ Generating Staff & Attendance Logs...');
            foreach ($departments as $dept) {
                $employees = Employee::factory()->count(3)->create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $mainBranch->id,
                    'department_id' => $dept->id,
                ]);

                foreach ($employees as $emp) {
                    for ($d = 1; $d <= 10; $d++) {
                        Attendance::factory()->present()->create([
                            'marquee_id' => $marquee->id,
                            'branch_id' => $mainBranch->id,
                            'employee_id' => $emp->id,
                            'date' => Carbon::today()->subDays($d)->format('Y-m-d'),
                            'created_by' => $adminUser->id,
                        ]);
                    }
                }
            }

            // 4. Seed Suppliers & Inventory Items
            $this->command->info('→ Generating Suppliers & Kitchen Inventory Items...');
            Supplier::factory()->count(6)->create(['marquee_id' => $marquee->id]);

            $invCats = InventoryCategory::where('marquee_id', $marquee->id)->get();
            $invUnits = InventoryUnit::where('marquee_id', $marquee->id)->get();

            if ($invCats->isNotEmpty() && $invUnits->isNotEmpty()) {
                foreach ($invCats as $cat) {
                    InventoryItem::factory()->count(3)->create([
                        'marquee_id' => $marquee->id,
                        'category_id' => $cat->id,
                        'unit_id' => $invUnits->first()->id,
                    ]);
                }
            }

            // 5. Seed Vendors & Vendor Services
            $this->command->info('→ Generating Partner Vendors & Service Catalogs...');
            $vendors = Vendor::factory()->count(5)->create(['marquee_id' => $marquee->id]);
            foreach ($vendors as $ven) {
                VendorService::factory()->count(2)->create([
                    'marquee_id' => $marquee->id,
                    'vendor_id' => $ven->id,
                ]);
            }

            $currency = \App\Models\Currency::where('marquee_id', $marquee->id)->first()
                ?? \App\Models\Currency::factory()->create(['marquee_id' => $marquee->id]);

            // 6. Seed Operational Expenses
            $this->command->info('→ Generating Operating Expenses...');
            $expCats = ExpenseCategory::where('marquee_id', $marquee->id)->get();
            if ($expCats->isNotEmpty()) {
                foreach ($expCats as $expCat) {
                    Expense::factory()->count(2)->paid()->create([
                        'marquee_id' => $marquee->id,
                        'branch_id' => $mainBranch->id,
                        'expense_category_id' => $expCat->id,
                        'currency_id' => $currency->id,
                    ]);
                }
            }

            // 7. Seed Bookings in diverse realistic lifecycle & financial stages
            $this->command->info('→ Generating Bookings, Payment Receipts, Journal Vouchers & Sub-Ledger Entries...');

            // A. Upcoming Confirmed Bookings with Cash/Bank Advances
            for ($i = 0; $i < 10; $i++) {
                $hall = $halls->random();
                $slot = $slots->random();
                $evType = $eventTypes->random();
                $cust = $customers->random();

                $booking = Booking::factory()->upcoming()->create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $mainBranch->id,
                    'customer_id' => $cust->id,
                    'hall_id' => $hall->id,
                    'slot_id' => $slot->id,
                    'event_type_id' => $evType->id,
                    'created_by' => $adminUser->id,
                ]);

                // Record Advance Payment (e.g. 30% - 50% deposit)
                $advanceAmount = round(($booking->grand_total * rand(30, 50)) / 100, -3);
                $payAcc = rand(0, 1) ? $cashAccount : $bankAccount;

                $financialService->recordPayment($booking, [
                    'amount' => $advanceAmount,
                    'payment_date' => Carbon::now()->subDays(rand(1, 15))->format('Y-m-d'),
                    'payment_method' => $payAcc->account_code === '1001' ? 'Cash' : 'Bank Transfer',
                    'account_id' => $payAcc->id,
                    'payment_type' => 'advance',
                    'transaction_reference' => 'ADV-' . rand(10000, 99999),
                    'notes' => 'Token advance deposit against wedding booking',
                    'recorded_by' => $adminUser->id,
                ]);
            }

            // B. Today's Events
            for ($i = 0; $i < 2; $i++) {
                $hall = $halls->random();
                $slot = $slots->random();
                $evType = $eventTypes->random();
                $cust = $customers->random();

                $booking = Booking::factory()->today()->create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $mainBranch->id,
                    'customer_id' => $cust->id,
                    'hall_id' => $hall->id,
                    'slot_id' => $slot->id,
                    'event_type_id' => $evType->id,
                    'created_by' => $adminUser->id,
                ]);

                $advanceAmount = round($booking->grand_total * 0.5, -3);
                $financialService->recordPayment($booking, [
                    'amount' => $advanceAmount,
                    'payment_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                    'payment_method' => 'Cash',
                    'account_id' => $cashAccount?->id,
                    'payment_type' => 'advance',
                    'notes' => 'Advance payment for today event',
                    'recorded_by' => $adminUser->id,
                ]);
            }

            // C. Completed & Financially Settled Past Events
            for ($i = 0; $i < 8; $i++) {
                $hall = $halls->random();
                $slot = $slots->random();
                $evType = $eventTypes->random();
                $cust = $customers->random();

                $booking = Booking::factory()->past()->create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $mainBranch->id,
                    'customer_id' => $cust->id,
                    'hall_id' => $hall->id,
                    'slot_id' => $slot->id,
                    'event_type_id' => $evType->id,
                    'created_by' => $adminUser->id,
                ]);

                // 1. Pre-event advance (e.g. 60%)
                $advanceAmount = round($booking->grand_total * 0.6, -3);
                $financialService->recordPayment($booking, [
                    'amount' => $advanceAmount,
                    'payment_date' => Carbon::parse($booking->booking_date)->subDays(10)->format('Y-m-d'),
                    'payment_method' => 'Cash',
                    'account_id' => $cashAccount?->id,
                    'payment_type' => 'advance',
                    'notes' => 'Initial booking advance',
                    'recorded_by' => $adminUser->id,
                ]);

                // 2. Event Completion -> Revenue Recognition
                $recognitionService->recognizeRevenue($booking, $booking->booking_date->format('Y-m-d'), $adminUser->id);
                $booking->refresh();

                // 3. Post-event Final Settlement for remaining receivable
                $remaining = (float) $booking->receivable_amount;
                if ($remaining > 0) {
                    $financialService->recordPayment($booking, [
                        'amount' => $remaining,
                        'payment_date' => Carbon::parse($booking->booking_date)->addDay()->format('Y-m-d'),
                        'payment_method' => 'Bank Transfer',
                        'account_id' => $bankAccount?->id,
                        'payment_type' => 'receivable_payment',
                        'notes' => 'Post-event final balance settlement',
                        'recorded_by' => $adminUser->id,
                    ]);
                }
            }

            // D. Draft / Tentative Reservations
            for ($i = 0; $i < 4; $i++) {
                $hall = $halls->random();
                $slot = $slots->random();
                $evType = $eventTypes->random();

                Booking::factory()->draft()->upcoming()->create([
                    'marquee_id' => $marquee->id,
                    'branch_id' => $mainBranch->id,
                    'customer_id' => $customers->random()->id,
                    'hall_id' => $hall->id,
                    'slot_id' => $slot->id,
                    'event_type_id' => $evType->id,
                    'created_by' => $adminUser->id,
                ]);
            }

            $this->command->info("✓ Completed synthetic data generation for {$marquee->name}!");
        }

        $this->command->info('🎉 All demo & testing data seeded successfully using Laravel Factories.');
    }
}
