<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\PhoneNumberService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'marquees' => ['phone'],
            'branches' => ['phone'],
            'users' => ['phone'],
            'employees' => ['mobile_number'],
            'suppliers' => ['mobile_number', 'whatsapp_number'],
            'vendors' => ['phone', 'alternate_phone'],
            'customers' => ['phone_number', 'alternate_phone']
        ];

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                // Get records that have non-empty phone values
                $records = DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->get();

                foreach ($records as $record) {
                    $original = $record->{$column};
                    $normalized = PhoneNumberService::normalize($original);
                    
                    if ($normalized !== $original) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update([
                                $column => $normalized
                            ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Standardization is permanent and clean. Down migration is not required.
    }
};
