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
        // 1. Suppliers Table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('supplier_code');
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('mobile_number');
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'supplier_code']);
            $table->unique(['marquee_id', 'name']);
        });

        // 2. Supplier Ledgers
        Schema::create('supplier_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->date('transaction_date');
            $table->string('reference_type')->nullable(); // Invoice, Return, Payment, Opening Balance
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('voucher_no')->nullable(); // Associated PV/JV number
            $table->decimal('debit', 15, 2)->default(0.00);  // Reduces payable (e.g. Payments, Returns)
            $table->decimal('credit', 15, 2)->default(0.00); // Increases payable (e.g. Invoices, Opening Bal)
            $table->decimal('running_balance', 15, 2)->default(0.00);
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ledgers');
        Schema::dropIfExists('suppliers');
    }
};
