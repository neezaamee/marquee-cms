<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('marquee_id')->nullable()->after('password')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->after('marquee_id')->constrained('branches')->onDelete('set null');
            $table->foreignId('role_id')->nullable()->after('branch_id')->constrained('roles')->onDelete('set null');
            $table->string('phone')->nullable()->after('role_id');
            $table->string('status')->default('active')->after('phone'); // active, inactive
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['marquee_id']);
            $table->dropColumn(['marquee_id', 'branch_id', 'role_id', 'phone', 'status', 'deleted_at']);
        });
    }
};
