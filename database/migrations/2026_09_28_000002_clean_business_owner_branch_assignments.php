<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $ownerRoleIds = Role::whereIn('name', ['business_owner', 'owner', 'super_admin'])->pluck('id');

        if ($ownerRoleIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('role_id', $ownerRoleIds)
                ->update(['branch_id' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank as business owners have tenant-wide scope
    }
};
