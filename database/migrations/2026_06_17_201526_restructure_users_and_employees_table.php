<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add employee_id and username to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('id')->constrained('employees')->onDelete('cascade');
            $table->string('username')->nullable()->unique()->after('email');
        });

        // 2. Migrate existing user-employee relationships
        // If an employee has user_id, set users.employee_id = employees.id and populate username
        $employees = DB::table('employees')->whereNotNull('user_id')->get();
        foreach ($employees as $emp) {
            $user = DB::table('users')->where('id', $emp->user_id)->first();
            if ($user) {
                // Generate a unique username from email
                $username = strstr($user->email, '@', true);
                if (!$username) {
                    $username = 'user_' . $user->id;
                }
                $baseUsername = $username;
                $counter = 1;
                while (DB::table('users')->where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                DB::table('users')->where('id', $emp->user_id)->update([
                    'employee_id' => $emp->id,
                    'username' => $username,
                ]);
            }
        }

        // 3. Drop user_id foreign key and column from employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add user_id to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        });

        // 2. Restore employee.user_id from users.employee_id
        $users = DB::table('users')->whereNotNull('employee_id')->get();
        foreach ($users as $usr) {
            DB::table('employees')->where('id', $usr->employee_id)->update([
                'user_id' => $usr->id,
            ]);
        }

        // 3. Drop employee_id and username from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['employee_id', 'username']);
        });
    }
};
