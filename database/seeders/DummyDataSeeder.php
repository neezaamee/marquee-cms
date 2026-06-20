<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingExtraService;
use App\Models\BookingHistory;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EventType;
use App\Models\ExtraService;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Package;
use App\Models\Slot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. If database is completely empty of marquees, run core seeders first
        if (Marquee::count() === 0) {
            $this->command->info('No marquees found. Running default DatabaseSeeder first...');
            $this->call(DatabaseSeeder::class);
        }

        $marquees = Marquee::all();
        $admin = User::where('email', 'superadmin@marquee.cms')->first();
        $adminId = $admin ? $admin->id : null;

        foreach ($marquees as $marquee) {
            $this->command->info("Seeding dummy testing data for Marquee: {$marquee->name}");

            // 2. Seed Realistic Customers
            $customersData = [
                [
                    'customer_type' => 'Individual',
                    'first_name' => 'Muhammad',
                    'last_name' => 'Ali',
                    'email' => 'ali.testing@example.com',
                    'phone_number' => '+923005550101',
                    'cnic_national_id' => '35201-1111111-1',
                    'address' => 'House 12, Street 3, Sector Y, DHA',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Individual',
                    'first_name' => 'Fatima',
                    'last_name' => 'Sajid',
                    'email' => 'fatima.sajid@example.com',
                    'phone_number' => '+923005550102',
                    'cnic_national_id' => '35201-2222222-2',
                    'address' => 'Apartment 4B, Royal Heights, Gulberg III',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Individual',
                    'first_name' => 'Ayesha',
                    'last_name' => 'Khan',
                    'email' => 'ayesha.khan@example.com',
                    'phone_number' => '+923005550103',
                    'cnic_national_id' => '35201-3333333-3',
                    'address' => 'Plot 45, Phase V, DHA',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Individual',
                    'first_name' => 'Zainab',
                    'last_name' => 'Bibi',
                    'email' => 'zainab.bibi@example.com',
                    'phone_number' => '+923005550104',
                    'cnic_national_id' => '35201-4444444-4',
                    'address' => 'House 102-C, Model Town',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Individual',
                    'first_name' => 'Hamza',
                    'last_name' => 'Riaz',
                    'email' => 'hamza.riaz@example.com',
                    'phone_number' => '+923005550105',
                    'cnic_national_id' => '35201-5555555-5',
                    'address' => 'Sector G, Phase I, DHA',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Corporate',
                    'first_name' => 'Kamran',
                    'last_name' => 'Siddiqui',
                    'company_name' => 'Systems Limited',
                    'email' => 'corporate.contact@systemsltd.example.com',
                    'phone_number' => '+924211179779',
                    'cnic_national_id' => '35201-6666666-6',
                    'ntn_number' => '4455667-8',
                    'address' => 'Software Technology Park, Lahore',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ],
                [
                    'customer_type' => 'Corporate',
                    'first_name' => 'Saad',
                    'last_name' => 'Ahmed',
                    'company_name' => 'Telenor Pakistan',
                    'email' => 'events@telenor.example.com',
                    'phone_number' => '+923455550111',
                    'cnic_national_id' => '35201-7777777-7',
                    'ntn_number' => '1122334-5',
                    'address' => 'Telenor 345 HQ, Sector G-10, Islamabad',
                    'city' => 'Islamabad',
                    'province' => 'Islamabad Capital Territory',
                ],
                [
                    'customer_type' => 'Corporate',
                    'first_name' => 'Nabeel',
                    'last_name' => 'Qureshi',
                    'company_name' => 'Nestle Pakistan Ltd',
                    'email' => 'nestle.bookings@nestle.example.com',
                    'phone_number' => '+924235550122',
                    'cnic_national_id' => '35201-8888888-8',
                    'ntn_number' => '7788990-1',
                    'address' => 'Ferozepur Road, Lahore',
                    'city' => $marquee->city ?: 'Lahore',
                    'province' => $marquee->province ?: 'Punjab',
                ]
            ];

            $createdCustomers = [];
            foreach ($customersData as $index => $cust) {
                // Ensure unique customer code per marquee
                $code = 'CUST-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
                
                $customer = Customer::updateOrCreate(
                    [
                        'marquee_id' => $marquee->id,
                        'email' => $cust['email'],
                    ],
                    [
                        'customer_code' => $code,
                        'customer_type' => $cust['customer_type'],
                        'first_name' => $cust['first_name'],
                        'last_name' => $cust['last_name'],
                        'company_name' => $cust['company_name'] ?? null,
                        'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                        'phone_number' => $cust['phone_number'],
                        'cnic_national_id' => $cust['cnic_national_id'],
                        'ntn_number' => $cust['ntn_number'] ?? null,
                        'address' => $cust['address'],
                        'city' => $cust['city'],
                        'province' => $cust['province'],
                        'status' => 'Active',
                        'created_by' => $adminId,
                    ]
                );
                $createdCustomers[] = $customer;
            }

            // 3. Seed Staff/Employees for each branch of the marquee
            $branches = Branch::where('marquee_id', $marquee->id)->get();
            $employeeDesignations = [
                ['title' => 'Hall Manager', 'salary' => 75000.00, 'type' => 'Full-time'],
                ['title' => 'Front Desk Executive', 'salary' => 45000.00, 'type' => 'Full-time'],
                ['title' => 'Executive Chef', 'salary' => 90000.00, 'type' => 'Full-time'],
                ['title' => 'Inventory Assistant', 'salary' => 35000.00, 'type' => 'Full-time'],
                ['title' => 'Lead Valet Driver', 'salary' => 25000.00, 'type' => 'Contractor'],
                ['title' => 'Security Coordinator', 'salary' => 30000.00, 'type' => 'Full-time'],
            ];

            $names = [
                'Sajid Mahmood', 'Kamran Khan', 'Chef Zakir', 'Noman Shah', 'Farhan Ghafoor', 'Zafar Iqbal',
                'Rashid Latif', 'Yousaf Ali', 'Chef Shirazi', 'Bilal Ahmed', 'Akram Mughal', 'Haris Rauf'
            ];

            $empIndex = 0;
            foreach ($branches as $branch) {
                for ($i = 0; $i < 4; $i++) {
                    $desig = $employeeDesignations[$i];
                    $name = $names[($empIndex) % count($names)];
                    $empIdStr = 'EMP-' . $branch->id . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);

                    Employee::updateOrCreate(
                        [
                            'employee_id' => $empIdStr,
                            'marquee_id' => $marquee->id,
                        ],
                        [
                            'branch_id' => $branch->id,
                            'name' => $name,
                            'cnic' => '35201-' . rand(1000000, 9999999) . '-1',
                            'mobile_number' => '+92321' . rand(1000000, 9999999),
                            'designation' => $desig['title'],
                            'joining_date' => Carbon::now()->subMonths(rand(3, 24))->format('Y-m-d'),
                            'salary' => $desig['salary'],
                            'employment_type' => $desig['type'],
                            'status' => 'active',
                        ]
                    );
                    $empIndex++;
                }
            }

            // 4. Seed Bookings
            // Fetch halls, event types, packages, slots, extra services
            $halls = Hall::where('marquee_id', $marquee->id)->get();
            $eventTypes = EventType::where('marquee_id', $marquee->id)->get();
            $packages = Package::where('marquee_id', $marquee->id)->get();
            $slots = Slot::where('marquee_id', $marquee->id)->get();
            $extraServices = ExtraService::where('marquee_id', $marquee->id)->get();

            if ($halls->isEmpty() || $eventTypes->isEmpty() || $packages->isEmpty() || $slots->isEmpty()) {
                $this->command->warn("Missing base configurations (halls, slots, packages, or event types) for marquee: {$marquee->name}. Skipping bookings seeding.");
                continue;
            }

            // Create a variety of past, present, and future events
            $bookingDates = [
                // Past dates (Completed/Paid)
                Carbon::now()->subDays(25),
                Carbon::now()->subDays(15),
                Carbon::now()->subDays(5),
                // Near Future/Today (Confirmed/Partial/Unpaid)
                Carbon::now()->addDays(1),
                Carbon::now()->addDays(4),
                Carbon::now()->addDays(7),
                // Far Future (Reserved/Draft/Unpaid)
                Carbon::now()->addDays(15),
                Carbon::now()->addDays(30),
                Carbon::now()->addDays(45),
                Carbon::now()->addDays(60),
                Carbon::now()->addDays(90),
            ];

            foreach ($bookingDates as $index => $bDate) {
                // Pick random related objects
                $hall = $halls[$index % $halls->count()];
                $eventType = $eventTypes[$index % $eventTypes->count()];
                $package = $packages[$index % $packages->count()];
                $slot = $slots[$index % $slots->count()];
                $customer = $createdCustomers[$index % count($createdCustomers)];

                // Calculate realistic stats
                $guests = rand($package->minimum_guests, min($package->maximum_guests ?: 500, $hall->capacity));
                $perPlate = (float) $package->per_plate_price;
                $packageAmount = $perPlate * $guests;
                $hallCharges = (float) $hall->default_booking_price;
                
                // Add some extra charges (like 10,000 for lighting)
                $extraCharges = $index % 3 === 0 ? 15000.00 : 0.00;
                // Add discount for corporates or special promotions
                $discount = $index % 4 === 0 ? 10000.00 : 0.00;
                $securityDeposit = 20000.00;

                $subtotal = $packageAmount + $hallCharges + $extraCharges - $discount;
                // Tax (16% local services tax)
                $taxAmount = round($subtotal * 0.16, 2);
                $grandTotal = $subtotal + $taxAmount;

                // Determine booking/payment statuses based on event date
                $isPast = $bDate->isPast();
                if ($isPast) {
                    $bookingStatus = 'Confirmed';
                    $paymentStatus = 'Paid';
                    $depositStatus = 'Refunded';
                    $depositRefunded = $securityDeposit;
                    $depositDeducted = 0.00;
                } else {
                    // Future booking cases
                    if ($index % 4 === 0) {
                        $bookingStatus = 'Draft';
                        $paymentStatus = 'Unpaid';
                        $depositStatus = 'Held';
                        $depositRefunded = 0.00;
                        $depositDeducted = 0.00;
                    } elseif ($index % 4 === 1) {
                        $bookingStatus = 'Cancelled';
                        $paymentStatus = 'Refunded';
                        $depositStatus = 'Refunded';
                        $depositRefunded = $securityDeposit;
                        $depositDeducted = 0.00;
                    } elseif ($index % 4 === 2) {
                        $bookingStatus = 'Reserved';
                        $paymentStatus = 'Partially Paid';
                        $depositStatus = 'Held';
                        $depositRefunded = 0.00;
                        $depositDeducted = 0.00;
                    } else {
                        $bookingStatus = 'Confirmed';
                        $paymentStatus = 'Paid';
                        $depositStatus = 'Held';
                        $depositRefunded = 0.00;
                        $depositDeducted = 0.00;
                    }
                }

                // Create Booking
                $booking = Booking::create([
                    'marquee_id' => $marquee->id,
                    'customer_id' => $customer->id,
                    'event_type_id' => $eventType->id,
                    'hall_id' => $hall->id,
                    'slot_id' => $slot->id,
                    'package_id' => $package->id,
                    'booking_date' => $bDate->format('Y-m-d'),
                    'start_time' => $bDate->copy()->setTimeFromTimeString($slot->start_time),
                    'end_time' => $bDate->copy()->setTimeFromTimeString($slot->end_time),
                    'guest_count' => $guests,
                    'per_plate_price' => $perPlate,
                    'package_amount' => $packageAmount,
                    'hall_charges' => $hallCharges,
                    'extra_charges' => $extraCharges,
                    'discount_amount' => $discount,
                    'security_deposit' => $securityDeposit,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal,
                    'grand_total' => $grandTotal,
                    'booking_status' => $bookingStatus,
                    'payment_status' => $paymentStatus,
                    'deposit_status' => $depositStatus,
                    'deposit_refunded_amount' => $depositRefunded ?? 0.00,
                    'deposit_deducted_amount' => $depositDeducted ?? 0.00,
                    'deposit_notes' => $isPast ? 'Events completed. Full security deposit refunded.' : null,
                    'special_instructions' => 'Seeded test event. Please arrange stage lighting early.',
                    'created_by' => $adminId,
                ]);

                // Sync booking_halls pivot
                $booking->halls()->sync([$hall->id]);

                // Attach custom menu items (from the package's menu items)
                $menuItems = $package->menuItems;
                $pivotData = [];
                foreach ($menuItems as $mItem) {
                    $pivotData[$mItem->id] = [
                        'custom_note' => $index % 3 === 0 ? 'Extra spicy handi' : null,
                        'managed_by_host' => ($index % 2 === 0),
                    ];
                }
                $booking->menuItems()->sync($pivotData);

                // Attach customized extra services
                if ($extraServices->isNotEmpty()) {
                    // Pick 2 random extra services
                    $shuffled = $extraServices->shuffle();
                    for ($k = 0; $k < min(2, $shuffled->count()); $k++) {
                        $srv = $shuffled[$k];
                        BookingExtraService::create([
                            'booking_id' => $booking->id,
                            'extra_service_id' => $srv->id,
                            'service_name' => $srv->service_name,
                            'unit_price' => $srv->default_price,
                            'quantity' => 1,
                            'total_price' => $srv->default_price,
                        ]);
                    }
                }

                // Add payments
                $totalToPay = $grandTotal + $securityDeposit;
                if ($paymentStatus === 'Paid') {
                    // Seed full payment
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'amount' => $totalToPay,
                        'payment_date' => $bDate->copy()->subDays(10)->format('Y-m-d'),
                        'payment_method' => $index % 2 === 0 ? 'Cash' : 'Bank Transfer',
                        'transaction_reference' => $index % 2 === 0 ? null : 'TXN-' . strtoupper(Str::random(10)),
                        'recorded_by' => $adminId,
                        'notes' => 'Received full payment including security deposit.',
                    ]);
                } elseif ($paymentStatus === 'Partially Paid') {
                    // Seed advance deposit (e.g. 50,000)
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'amount' => 50000.00,
                        'payment_date' => $bDate->copy()->subDays(12)->format('Y-m-d'),
                        'payment_method' => 'Cheque',
                        'transaction_reference' => 'CHQ-509121',
                        'recorded_by' => $adminId,
                        'notes' => 'Received advance booking token amount.',
                    ]);
                } elseif ($paymentStatus === 'Refunded') {
                    // Seed original payment
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'amount' => 50000.00,
                        'payment_date' => $bDate->copy()->subDays(15)->format('Y-m-d'),
                        'payment_method' => 'Cash',
                        'recorded_by' => $adminId,
                        'notes' => 'Original booking deposit.',
                    ]);
                    // Seed negative refund payment
                    BookingPayment::create([
                        'booking_id' => $booking->id,
                        'amount' => -50000.00,
                        'payment_date' => $bDate->copy()->subDays(1)->format('Y-m-d'),
                        'payment_method' => 'Cash',
                        'recorded_by' => $adminId,
                        'notes' => 'Refunded client due to event cancellation.',
                    ]);
                }

                // Add Booking Audit Log Histories
                BookingHistory::create([
                    'booking_id' => $booking->id,
                    'user_id' => $adminId,
                    'status_from' => null,
                    'status_to' => 'Draft',
                    'payment_status_from' => null,
                    'payment_status_to' => 'Unpaid',
                    'notes' => 'Booking generated from testing data seeder.',
                ]);

                if ($bookingStatus !== 'Draft') {
                    BookingHistory::create([
                        'booking_id' => $booking->id,
                        'user_id' => $adminId,
                        'status_from' => 'Draft',
                        'status_to' => $bookingStatus,
                        'payment_status_from' => 'Unpaid',
                        'payment_status_to' => $paymentStatus,
                        'notes' => "Status transitioned to {$bookingStatus} with payment state {$paymentStatus}.",
                    ]);
                }
            }
            // 5. Seed SaaS Invoices & Payments for this Marquee
            $billingCycles = \App\Models\BillingCycle::all();
            if ($billingCycles->isNotEmpty()) {
                $cycle = $billingCycles->first(); // Default to Monthly
                $plan = $marquee->subscriptionPlan ?: \App\Models\SubscriptionPlan::first();
                if ($plan) {
                    // Seed a past Paid invoice and payment
                    $invoice1 = \App\Models\SaasInvoice::create([
                        'marquee_id' => $marquee->id,
                        'subscription_plan_id' => $plan->id,
                        'billing_cycle_id' => $cycle->id,
                        'amount' => $plan->monthly_price ?: $plan->price,
                        'tax' => 0.00,
                        'discount' => 0.00,
                        'total_amount' => $plan->monthly_price ?: $plan->price,
                        'payment_status' => 'Paid',
                        'invoice_status' => 'Paid',
                        'due_date' => Carbon::now()->subMonths(1),
                        'paid_date' => Carbon::now()->subMonths(1)->addDays(2),
                        'notes' => 'Initial subscription invoice.',
                    ]);

                    \App\Models\SaasPayment::create([
                        'payment_reference' => '',
                        'invoice_id' => $invoice1->id,
                        'marquee_id' => $marquee->id,
                        'amount' => $invoice1->total_amount,
                        'payment_method' => 'Bank Transfer',
                        'transaction_id' => 'TXN-PAID-' . rand(100000, 999999),
                        'payment_date' => Carbon::now()->subMonths(1)->addDays(2),
                        'notes' => 'Received bank transfer.',
                    ]);

                    // Seed a future Pending invoice
                    \App\Models\SaasInvoice::create([
                        'marquee_id' => $marquee->id,
                        'subscription_plan_id' => $plan->id,
                        'billing_cycle_id' => $cycle->id,
                        'amount' => $plan->monthly_price ?: $plan->price,
                        'tax' => 0.00,
                        'discount' => 0.00,
                        'total_amount' => $plan->monthly_price ?: $plan->price,
                        'payment_status' => 'Unpaid',
                        'invoice_status' => 'Pending',
                        'due_date' => Carbon::now()->addDays(14),
                        'notes' => 'Upcoming renewal invoice.',
                    ]);
                }
            }
        }

        $this->command->info('Dummy testing data seeded successfully!');
    }
}
