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
        Schema::create('investor_profit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->date('date')->index();
            $table->decimal('sales_subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('tax', 18, 2)->default(0);
            $table->decimal('net_sales_amount', 18, 2)->default(0);
            $table->decimal('cost_of_goods', 18, 2)->default(0);
            $table->decimal('gross_profit', 18, 2)->default(0);
            $table->decimal('deducted_expenses', 18, 2)->default(0);
            $table->decimal('eligible_profit', 18, 2)->default(0);
            $table->decimal('investor_percentage', 8, 4);
            $table->decimal('investor_profit_amount', 18, 2);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid, reversed
            $table->string('calculation_method')->default('gross'); // gross, net
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_profit_transactions');
    }
};
