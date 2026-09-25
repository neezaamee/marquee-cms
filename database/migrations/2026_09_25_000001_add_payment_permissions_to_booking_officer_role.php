<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure create_payments and view_payments permissions exist
        $viewPayments = Permission::firstOrCreate(
            ['name' => 'view_payments'],
            ['label' => 'View Payments']
        );

        $createPayments = Permission::firstOrCreate(
            ['name' => 'create_payments'],
            ['label' => 'Record Payments']
        );

        // If booking_officer role exists, sync view_payments and create_payments
        $bookingOfficerRole = Role::where('name', 'booking_officer')->first();
        if ($bookingOfficerRole) {
            $bookingOfficerRole->permissions()->syncWithoutDetaching([
                $viewPayments->id,
                $createPayments->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $bookingOfficerRole = Role::where('name', 'booking_officer')->first();
        if ($bookingOfficerRole) {
            $permissionIds = Permission::whereIn('name', ['view_payments', 'create_payments'])->pluck('id')->toArray();
            $bookingOfficerRole->permissions()->detach($permissionIds);
        }
    }
};
