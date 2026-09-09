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
        // 1. Create / Ensure post_final_bill_pra permission exists
        $praPermission = Permission::firstOrCreate(
            ['name' => 'post_final_bill_pra'],
            ['label' => 'Post Final Bill to PRA / FBR']
        );

        // Booking module permissions
        $bookingManagerPermissions = [
            'view_bookings',
            'create_bookings',
            'edit_bookings',
            'cancel_bookings',
            'view_halls',
            'view_menus',
            'view_packages',
            'event-types.view',
            'view_payments',
            'create_payments',
            'view_reports',
            'post_final_bill_pra',
        ];

        $permissionIds = Permission::whereIn('name', $bookingManagerPermissions)->pluck('id')->toArray();

        // 2. Sync permissions for booking_manager and booking_manager_pra roles
        $bookingManagerRoles = Role::whereIn('name', ['booking_manager', 'booking_manager_pra'])->get();

        if ($bookingManagerRoles->isEmpty()) {
            $createdRole = Role::create([
                'name' => 'booking_manager',
                'label' => 'Booking Manager',
                'description' => 'Manages event bookings, customer contracts, and posts final invoices to PRA/FBR.',
            ]);
            $bookingManagerRoles = collect([$createdRole]);
        }

        foreach ($bookingManagerRoles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Also assign post_final_bill_pra to business_owner, owner, and branch_manager
        $managementRoles = Role::whereIn('name', ['business_owner', 'owner', 'branch_manager'])->get();
        foreach ($managementRoles as $role) {
            $role->permissions()->syncWithoutDetaching([$praPermission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $praPermission = Permission::where('name', 'post_final_bill_pra')->first();
        if ($praPermission) {
            $praPermission->roles()->detach();
            $praPermission->delete();
        }
    }
};
