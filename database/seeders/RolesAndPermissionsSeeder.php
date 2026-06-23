<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // SaaS level
            ['name' => 'manage_saas', 'label' => 'Manage SaaS Platform'],

            // Bookings
            ['name' => 'view_bookings', 'label' => 'View Bookings'],
            ['name' => 'create_bookings', 'label' => 'Create Bookings'],
            ['name' => 'edit_bookings', 'label' => 'Edit Bookings'],
            ['name' => 'cancel_bookings', 'label' => 'Cancel Bookings'],

            // Halls/Venues
            ['name' => 'view_halls', 'label' => 'View Halls'],
            ['name' => 'create_halls', 'label' => 'Create Halls'],
            ['name' => 'edit_halls', 'label' => 'Edit Halls'],
            ['name' => 'delete_halls', 'label' => 'Delete Halls'],

            // Catering/Menus
            ['name' => 'view_menus', 'label' => 'View Menus'],
            ['name' => 'create_menus', 'label' => 'Create Menus'],
            ['name' => 'edit_menus', 'label' => 'Edit Menus'],
            ['name' => 'delete_menus', 'label' => 'Delete Menus'],

            // Finance/Payments
            ['name' => 'view_payments', 'label' => 'View Payments'],
            ['name' => 'create_payments', 'label' => 'Record Payments'],
            ['name' => 'refund_payments', 'label' => 'Refund Payments'],

            // Reports & Settings
            ['name' => 'view_reports', 'label' => 'View Reports'],
            ['name' => 'manage_staff', 'label' => 'Manage Staff Members'],
            ['name' => 'manage_settings', 'label' => 'Manage Branch/Marquee Settings'],

            // Inventory
            ['name' => 'view_inventory', 'label' => 'View Inventory'],
            ['name' => 'manage_inventory', 'label' => 'Manage Inventory Items'],

            // Event Types
            ['name' => 'event-types.view', 'label' => 'View Event Types'],
            ['name' => 'event-types.create', 'label' => 'Create Event Types'],
            ['name' => 'event-types.edit', 'label' => 'Edit Event Types'],
            ['name' => 'event-types.delete', 'label' => 'Delete Event Types'],

            // Packages
            ['name' => 'view_packages', 'label' => 'View Packages'],
            ['name' => 'create_packages', 'label' => 'Create Packages'],
            ['name' => 'edit_packages', 'label' => 'Edit Packages'],
            ['name' => 'delete_packages', 'label' => 'Delete Packages'],

            // Accounting
            ['name' => 'manage_accounting', 'label' => 'Manage Accounting & Financials'],
        ];

        $permissionInstances = [];
        foreach ($permissions as $perm) {
            $permissionInstances[$perm['name']] = Permission::updateOrCreate(
                ['name' => $perm['name']],
                ['label' => $perm['label']]
            );
        }

        // Define Roles and assign Permissions
        $roles = [
            'super_admin' => [
                'label' => 'Super Administrator',
                'description' => 'Global SaaS administrator. Manages all marquees and plans.',
                'permissions' => ['manage_saas'],
            ],
            'owner' => [
                'label' => 'Marquee Owner',
                'description' => 'Owner of the Marquee franchise. Full access to their company data.',
                'permissions' => [
                    'view_bookings', 'create_bookings', 'edit_bookings', 'cancel_bookings',
                    'view_halls', 'create_halls', 'edit_halls', 'delete_halls',
                    'view_menus', 'create_menus', 'edit_menus', 'delete_menus',
                    'view_payments', 'create_payments', 'refund_payments',
                    'view_reports', 'manage_staff', 'manage_settings',
                    'view_inventory', 'manage_inventory',
                    'event-types.view', 'event-types.create', 'event-types.edit', 'event-types.delete',
                    'view_packages', 'create_packages', 'edit_packages', 'delete_packages',
                    'manage_accounting'
                ],
            ],
            'branch_manager' => [
                'label' => 'Branch Manager',
                'description' => 'Manages day-to-day operations and bookings of a specific branch.',
                'permissions' => [
                    'view_bookings', 'create_bookings', 'edit_bookings', 'cancel_bookings',
                    'view_halls', 'view_menus',
                    'view_payments', 'create_payments',
                    'view_reports', 'manage_staff', 'manage_settings',
                    'view_inventory',
                    'event-types.view', 'event-types.create', 'event-types.edit',
                    'view_packages', 'create_packages', 'edit_packages'
                ],
            ],
            'accountant' => [
                'label' => 'Accountant / Cashier',
                'description' => 'Handles financial records, payments, and invoices.',
                'permissions' => [
                    'view_bookings',
                    'view_payments', 'create_payments', 'refund_payments',
                    'view_reports',
                    'manage_accounting'
                ],
            ],
            'booking_officer' => [
                'label' => 'Booking Officer',
                'description' => 'Handles enquiries and registers customer event bookings.',
                'permissions' => [
                    'view_bookings', 'create_bookings', 'edit_bookings',
                    'view_halls', 'view_menus',
                    'event-types.view',
                    'view_packages'
                ],
            ],
            'store_keeper' => [
                'label' => 'Store Keeper / Inventory Manager',
                'description' => 'Manages physical warehouse inventory, tables, chairs, and cutlery counts.',
                'permissions' => [
                    'view_inventory', 'manage_inventory'
                ],
            ],
            'staff' => [
                'label' => 'Staff Member',
                'description' => 'View-only access to schedules and menus.',
                'permissions' => [
                    'view_bookings', 'view_halls', 'view_menus',
                    'event-types.view',
                    'view_packages'
                ],
            ],
        ];

        foreach ($roles as $roleName => $details) {
            $role = Role::updateOrCreate(
                ['name' => $roleName],
                [
                    'label' => $details['label'],
                    'description' => $details['description']
                ]
            );

            // Sync permissions for this role
            $rolePermissions = [];
            foreach ($details['permissions'] as $permName) {
                if (isset($permissionInstances[$permName])) {
                    $rolePermissions[] = $permissionInstances[$permName]->id;
                }
            }
            $role->permissions()->sync($rolePermissions);
        }
    }
}
