<?php

namespace Tests\Feature;

use App\Livewire\BookingList;
use App\Livewire\CustomerList;
use App\Livewire\Finance\ExpenseList;
use App\Livewire\Inventory\SupplierList;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PastDueReceivablesAndPayablesFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Hall $hall;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $ownerRole = Role::where('name', 'owner')->first();

        $plan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'price' => 5000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Marquee',
            'slug' => 'royal-marquee',
            'status' => 'active',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@royalmarquee.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Executive Wing',
            'code' => 'EW-01',
            'address' => 'Mall Road Sector A',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001112233',
            'status' => 'Active',
            'is_main_branch' => true,
        ]);

        $this->hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_name' => 'Grand Ballroom',
            'hall_code' => 'HALL-01',
            'hall_type' => 'Banquet',
            'capacity' => 500,
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@royalmarquee.com',
            'password' => bcrypt('password123'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'Active',
            'is_super_admin' => false,
        ]);

        $this->customer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Tariq',
            'last_name' => 'Jamil',
            'phone_number' => '03009876543',
            'email' => 'tariq@example.com',
            'customer_type' => 'Individual',
            'status' => 'Active',
        ]);
    }

    public function test_booking_list_filters_past_due_receivables_correctly(): void
    {
        // 1. Past-due booking: event date was 5 days ago, grand_total = 100,000, advance = 40,000, receivable = 60,000
        $pastDueBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-PASTDUE-01',
            'booking_date' => Carbon::today()->subDays(5)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'price_per_head' => 300,
            'grand_total' => 100000,
            'advance_received' => 40000,
            'receivable_amount' => 60000,
            'booking_status' => 'Confirmed',
            'created_by' => $this->owner->id,
        ]);

        // 2. Future booking: event date is 10 days ahead, balance is pending (advance paid 30k of 100k)
        $futureBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-FUTURE-01',
            'booking_date' => Carbon::today()->addDays(10)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 300,
            'price_per_head' => 300,
            'grand_total' => 100000,
            'advance_received' => 30000,
            'receivable_amount' => 70000,
            'booking_status' => 'Confirmed',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(BookingList::class)
            ->set('filterBalanceStatus', 'past_due')
            ->assertSee($pastDueBooking->booking_number)
            ->assertDontSee($futureBooking->booking_number)
            ->assertViewHas('pastDueReceivablesAmount', 60000.0)
            ->assertViewHas('pastDueReceivablesCount', 1);
    }

    public function test_booking_list_shortcut_past_due_activates_filter(): void
    {
        $pastDueBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-SHORTCUT-01',
            'booking_date' => Carbon::today()->subDays(2)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 200,
            'price_per_head' => 400,
            'grand_total' => 80000,
            'advance_received' => 20000,
            'receivable_amount' => 60000,
            'booking_status' => 'Completed',
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(BookingList::class)
            ->call('applyShortcutFilter', 'past_due')
            ->assertSet('filterQuickShortcut', 'past_due')
            ->assertSet('filterBalanceStatus', 'past_due')
            ->assertSee($pastDueBooking->booking_number);
    }

    public function test_expense_list_filters_past_due_payables_correctly(): void
    {
        $currency = \App\Models\Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs.',
            'is_base' => true,
            'exchange_rate' => 1.000000,
        ]);

        $category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Decorations',
            'category_code' => 'CAT-DEC',
            'status' => 'Active',
        ]);

        // 1. Past-due expense: due date was 3 days ago, unpaid = 25,000
        $pastDueExpense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-PASTDUE-01',
            'title' => 'Stage Floral Setup',
            'expense_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'due_date' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'total_amount' => 25000,
            'paid_amount' => 0,
            'payment_status' => 'Unpaid',
            'payment_method' => Expense::METHOD_CREDIT,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->owner->id,
        ]);

        // 2. Future expense: due date is 15 days ahead, unpaid = 40,000
        $futureExpense = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-FUTURE-01',
            'title' => 'Lighting Equipment Rental',
            'expense_date' => Carbon::today()->format('Y-m-d'),
            'due_date' => Carbon::today()->addDays(15)->format('Y-m-d'),
            'total_amount' => 40000,
            'paid_amount' => 0,
            'payment_status' => 'Unpaid',
            'payment_method' => Expense::METHOD_CREDIT,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->owner->id,
        ]);

        Livewire::actingAs($this->owner)
            ->test(ExpenseList::class)
            ->set('payment_status', 'past_due')
            ->assertSee($pastDueExpense->expense_number)
            ->assertDontSee($futureExpense->expense_number)
            ->assertViewHas('pastDuePayablesTotal', 25000.0)
            ->assertViewHas('pastDuePayablesCount', 1);
    }

    public function test_customer_list_filters_customers_with_past_due_receivables(): void
    {
        $pastDueBooking = Booking::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'hall_id' => $this->hall->id,
            'customer_id' => $this->customer->id,
            'booking_number' => 'BK-CUST-PASTDUE-01',
            'booking_date' => Carbon::today()->subDays(4)->format('Y-m-d'),
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'guest_count' => 150,
            'price_per_head' => 500,
            'grand_total' => 75000,
            'advance_received' => 20000,
            'receivable_amount' => 55000,
            'booking_status' => 'Completed',
            'created_by' => $this->owner->id,
        ]);

        // Another customer with cleared balance
        $clearedCustomer = Customer::create([
            'marquee_id' => $this->marquee->id,
            'first_name' => 'Waqas',
            'last_name' => 'Ahmed',
            'phone_number' => '03001112233',
            'customer_type' => 'Individual',
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->owner)
            ->test(CustomerList::class)
            ->set('filterBalance', 'past_due')
            ->assertSee($this->customer->first_name)
            ->assertDontSee($clearedCustomer->first_name);
    }

    public function test_supplier_list_filters_past_due_payables(): void
    {
        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-001',
            'name' => 'Al-Madina Meat Supplier',
            'mobile_number' => '03001234567',
            'opening_balance' => 15000.00,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->owner)
            ->test(SupplierList::class)
            ->set('balanceFilter', 'past_due')
            ->assertSee('Al-Madina Meat Supplier');
    }

    public function test_business_owner_dashboard_toggle_past_due_filter(): void
    {
        Livewire::actingAs($this->owner)
            ->test(BusinessOwnerDashboard::class)
            ->assertSet('dueFilter', 'all')
            ->call('setDueFilter', 'past_due')
            ->assertSet('dueFilter', 'past_due');
    }
}
