<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Hall;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultHallsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $admin = User::where('email', 'superadmin@marquee.cms')->first();
        $adminId = $admin ? $admin->id : null;

        foreach ($branches as $branch) {
            $marqueeId = $branch->marquee_id;

            // Fetch active slot shifts for this marquee
            $slots = Slot::where('marquee_id', $marqueeId)->where('status', 'active')->get();

            // Seed standard halls for this branch
            $hallsData = [
                [
                    'hall_name' => 'Royal Banquet Hall',
                    'hall_code' => 'ROYAL-HL',
                    'capacity' => 600,
                    'hall_type' => 'Banquet',
                    'default_booking_price' => 120000.00,
                    'description' => 'Elegant banquet hall with luxury decor, perfect for wedding receptions.',
                ],
                [
                    'hall_name' => 'Grand Executive Marquee',
                    'hall_code' => 'EXEC-MQ',
                    'capacity' => 1000,
                    'hall_type' => 'Marquee',
                    'default_booking_price' => 180000.00,
                    'description' => 'Spacious and premium outdoor-feel marquee with centralized temperature control.',
                ],
                [
                    'hall_name' => 'Crystal Ballroom',
                    'hall_code' => 'CRYST-BR',
                    'capacity' => 400,
                    'hall_type' => 'Ballroom',
                    'default_booking_price' => 90000.00,
                    'description' => 'Medium-sized classic ballroom adorned with crystal chandeliers.',
                ]
            ];

            foreach ($hallsData as $hallDef) {
                $hall = Hall::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'hall_code' => $hallDef['hall_code'],
                    ],
                    [
                        'marquee_id' => $marqueeId,
                        'hall_name' => $hallDef['hall_name'],
                        'capacity' => $hallDef['capacity'],
                        'hall_type' => $hallDef['hall_type'],
                        'default_booking_price' => $hallDef['default_booking_price'],
                        'description' => $hallDef['description'],
                        'status' => 'active',
                        'created_by' => $adminId,
                    ]
                );

                // Assign slots to this hall
                foreach ($slots as $slot) {
                    $hall->slots()->syncWithoutDetaching([
                        $slot->id => [
                            'marquee_id' => $marqueeId,
                            'status' => 'active',
                            'created_by' => $adminId,
                        ]
                    ]);
                }
            }
        }
    }
}
