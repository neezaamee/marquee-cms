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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('account_code');
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->foreignId('account_type_id')->constrained('account_types')->onDelete('restrict');
            $table->string('nature'); // Asset, Liability, Equity, Income, Expense
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('system_generated')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Ensure unique account codes per tenant
            $table->unique(['marquee_id', 'account_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
