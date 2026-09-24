<?php

namespace App\Livewire\Administration;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Permission;
use Livewire\Component;

class AccessControl extends Component
{
    public function mount()
    {
        abort_unless(auth()->user() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff')), 403, 'Unauthorized access.');
    }

    public function togglePermission($roleId, $permissionId)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        // Security check: super_admin permissions are immutable to prevent lockout
        if ($role->name === 'super_admin') {
            session()->flash('error', 'Super Administrator permissions are system-locked and cannot be altered.');
            return;
        }

        $wasAttached = $role->permissions()->where('permissions.id', $permissionId)->exists();
        if ($wasAttached) {
            $role->permissions()->detach($permissionId);
            $actionDesc = "Revoked permission '{$permission->name}' from role '{$role->name}'";
        } else {
            $role->permissions()->attach($permissionId);
            $actionDesc = "Granted permission '{$permission->name}' to role '{$role->name}'";
        }

        try {
            ActivityLog::create([
                'marquee_id' => null,
                'user_id' => auth()->id(),
                'action' => 'role_permission_toggled',
                'model_type' => Role::class,
                'model_id' => $role->id,
                'description' => $actionDesc,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {}

        session()->flash('success', "Updated permissions for the '{$role->label}' role.");
    }

    private function getPermissionCategory($name)
    {
        $name = strtolower($name);
        if (str_contains($name, 'saas') || str_contains($name, 'accounting')) {
            return 'SaaS & General Ledger';
        }
        if (str_contains($name, 'booking')) {
            return 'Bookings Management';
        }
        if (str_contains($name, 'hall')) {
            return 'Halls & Venues';
        }
        if (str_contains($name, 'menu') || str_contains($name, 'recipe')) {
            return 'Menus & Catering';
        }
        if (str_contains($name, 'payment') || str_contains($name, 'refund')) {
            return 'Payments & Finance';
        }
        if (str_contains($name, 'inventory') || str_contains($name, 'stock')) {
            return 'Inventory Management';
        }
        if (str_contains($name, 'purchase') || str_contains($name, 'po') || str_contains($name, 'invoice')) {
            return 'Procurement & Purchases';
        }
        if (str_contains($name, 'event-type') || str_contains($name, 'event_type')) {
            return 'Event Types';
        }
        if (str_contains($name, 'package')) {
            return 'Packages Builder';
        }
        if (str_contains($name, 'staff') || str_contains($name, 'setting')) {
            return 'Staff & Settings Configuration';
        }
        return 'General Permissions';
    }

    public function render()
    {
        abort_unless(auth()->user() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff')), 403);

        $roles = Role::with('permissions')->orderBy('label', 'asc')->get();
        $permissionsList = Permission::orderBy('label', 'asc')->get();

        // Group permissions by category for a clean dashboard matrix
        $groupedPermissions = [];
        foreach ($permissionsList as $permission) {
            $category = $this->getPermissionCategory($permission->name);
            $groupedPermissions[$category][] = $permission;
        }

        // Sort categories to maintain consistent order
        ksort($groupedPermissions);

        return view('livewire.administration.access-control', [
            'roles' => $roles,
            'groupedPermissions' => $groupedPermissions,
        ])->layout('layouts.admin');
    }
}
