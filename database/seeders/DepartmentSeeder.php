<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Permissions
        $permissions = [
            ['name' => 'department.view', 'label' => 'View Departments'],
            ['name' => 'department.create', 'label' => 'Create Departments'],
            ['name' => 'department.edit', 'label' => 'Edit Departments'],
            ['name' => 'department.delete', 'label' => 'Delete Departments'],
            ['name' => 'department.attendance.manage', 'label' => 'Manage Department Attendance'],
            ['name' => 'department.inventory.issue', 'label' => 'Issue Department Stock'],
            ['name' => 'department.inventory.approve', 'label' => 'Approve Stock Requests'],
            ['name' => 'department.request.manage', 'label' => 'Manage Department Requests'],
            ['name' => 'department.report.view', 'label' => 'View Department Reports'],
        ];

        $permissionInstances = [];
        foreach ($permissions as $perm) {
            $permissionInstances[$perm['name']] = Permission::updateOrCreate(
                ['name' => $perm['name']],
                ['label' => $perm['label']]
            );
        }

        // Sync to roles (Owner, Branch Manager)
        $owner = Role::where('name', 'owner')->first();
        if ($owner) {
            $ownerPerms = $owner->permissions()->pluck('id')->toArray();
            foreach ($permissionInstances as $permInstance) {
                if (!in_array($permInstance->id, $ownerPerms)) {
                    $ownerPerms[] = $permInstance->id;
                }
            }
            $owner->permissions()->sync($ownerPerms);
        }

        $manager = Role::where('name', 'branch_manager')->first();
        if ($manager) {
            $managerPerms = $manager->permissions()->pluck('id')->toArray();
            foreach ($permissionInstances as $permInstance) {
                if (!in_array($permInstance->id, $managerPerms)) {
                    $managerPerms[] = $permInstance->id;
                }
            }
            $manager->permissions()->sync($managerPerms);
        }

        // 2. Seed Default Departments for each Marquee & Branch
        $marquees = Marquee::all();
        
        foreach ($marquees as $marquee) {
            $branches = Branch::where('marquee_id', $marquee->id)->get();
            
            foreach ($branches as $branch) {
                $this->seedDepartmentsForBranch($marquee->id, $branch->id);
            }
        }
    }

    /**
     * Seed departments list for a specific branch.
     */
    private function seedDepartmentsForBranch(int $marqueeId, int $branchId): void
    {
        $defaultDepts = [
            // Kitchen Production
            ['name' => 'Pakistani Kitchen', 'type' => 'Kitchen Production'],
            ['name' => 'BBQ Kitchen', 'type' => 'Kitchen Production'],
            ['name' => 'Chinese Kitchen', 'type' => 'Kitchen Production'],
            ['name' => 'Continental Kitchen', 'type' => 'Kitchen Production'],
            ['name' => 'Fast Food Kitchen', 'type' => 'Kitchen Production'],
            ['name' => 'Live Cooking Station', 'type' => 'Kitchen Production'],
            ['name' => 'Tandoor', 'type' => 'Kitchen Production'],
            ['name' => 'Bakery', 'type' => 'Kitchen Production'],
            ['name' => 'Sweet Production', 'type' => 'Kitchen Production'],
            ['name' => 'Dessert Production', 'type' => 'Kitchen Production'],
            ['name' => 'Beverage & Drinks', 'type' => 'Kitchen Production'],
            ['name' => 'Salad Section', 'type' => 'Kitchen Production'],

            // Operations
            ['name' => 'Banquet Operations', 'type' => 'Operations'],
            ['name' => 'Hall Management', 'type' => 'Operations'],
            ['name' => 'Decoration', 'type' => 'Operations'],
            ['name' => 'Housekeeping', 'type' => 'Operations'],
            ['name' => 'Cleaning', 'type' => 'Operations'],
            ['name' => 'Maintenance', 'type' => 'Operations'],
            ['name' => 'Electrical', 'type' => 'Operations'],
            ['name' => 'Plumbing', 'type' => 'Operations'],
            ['name' => 'Generator Operations', 'type' => 'Operations'],
            ['name' => 'Security', 'type' => 'Operations'],
            ['name' => 'Parking & Valet', 'type' => 'Operations'],

            // Administration
            ['name' => 'Reception', 'type' => 'Administration'],
            ['name' => 'Customer Service', 'type' => 'Administration'],
            ['name' => 'Accounts & Finance', 'type' => 'Administration'],
            ['name' => 'Human Resources', 'type' => 'Administration'],
            ['name' => 'Purchase Department', 'type' => 'Administration'],
            ['name' => 'Inventory & Store', 'type' => 'Administration'],
            ['name' => 'IT Support', 'type' => 'Administration'],
            ['name' => 'Administration', 'type' => 'Administration'],
        ];

        foreach ($defaultDepts as $idx => $dept) {
            $code = 'DEPT-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

            // Avoid double seeding
            Department::updateOrCreate(
                [
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_code' => $code,
                ],
                [
                    'name' => $dept['name'],
                    'department_type' => $dept['type'],
                    'display_order' => $idx + 1,
                    'status' => 'Active',
                ]
            );
        }
    }
}
