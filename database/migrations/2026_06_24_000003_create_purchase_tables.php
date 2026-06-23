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
        // 1. Purchase Orders (PO)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('po_number');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Draft'); // Draft, Approved, Partially Received, Completed, Cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'po_number']);
        });

        // 2. Purchase Order Details
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('received_quantity', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 3. Goods Receiving Note (GRN)
        Schema::create('goods_receiving_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            $table->string('grn_number');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'grn_number']);
        });

        // 4. Goods Receiving Note Details
        Schema::create('goods_receiving_note_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receiving_note_id')->constrained('goods_receiving_notes')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('ordered_qty', 15, 2);
            $table->decimal('received_qty', 15, 2);
            $table->timestamps();
        });

        // 5. Purchase Invoices
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->string('invoice_number');
            $table->date('purchase_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('gross_amount', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2)->default(0.00);
            $table->string('status')->default('Draft'); // Draft, Approved, Posted, Cancelled
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'invoice_number']);
        });

        // 6. Purchase Invoice Details
        Schema::create('purchase_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        // 7. Purchase Returns
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('set null');
            $table->string('return_number');
            $table->date('return_date');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('gross_amount', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2)->default(0.00);
            $table->string('status')->default('Draft'); // Draft, Approved, Posted, Cancelled
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'return_number']);
        });

        // 8. Purchase Return Details
        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_details');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_invoice_details');
        Schema::dropIfExists('purchase_invoices');
        Schema::dropIfExists('goods_receiving_note_details');
        Schema::dropIfExists('goods_receiving_notes');
        Schema::dropIfExists('purchase_order_details');
        Schema::dropIfExists('purchase_orders');
    }
};
