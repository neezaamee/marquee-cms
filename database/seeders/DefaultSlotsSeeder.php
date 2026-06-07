<?php

namespace Database\Seeders;

use App\Models\Marquee;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultSlotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all marquees (or the first one, or loop through all to give them default slots)
        $marquees = Marquee::all();
        $admin = User::where('email', 'superadmin@marquee.cms')->first();

        foreach ($marquees as $marquee) {
            // Day Shift
            Slot::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'slot_name' => 'Day Shift',
                ],
                [
                    'start_time' => '13:00:00',
                    'end_time' => '16:00:00',
                    'description' => 'Default Day Shift (01:00 PM - 04:00 PM)',
                    'status' => 'active',
                    'created_by' => $admin ? $admin->id : null,
                ]
            );

            // Night Shift
            Slot::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'slot_name' => 'Night Shift',
                ],
                [
                    'start_time' => '18:00:00',
                    'end_time' => '21:00:00',
                    'description' => 'Default Night Shift (06:00 PM - 09:00 PM)',
                    'status' => 'active',
                    'created_by' => $admin ? $admin->id : null,
                ]
            );
        }
    }
}
