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
        Schema::create('investor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->string('payment_no')->unique()->index();
            $table->date('date')->index();
            $table->string('payment_type')->index(); // profit_payment, purchase_repayment, combined, adjustment, opening_balance
            $table->decimal('profit_payment_amount', 18, 2)->default(0);
            $table->decimal('purchase_repayment_amount', 18, 2)->default(0);
            $table->decimal('total_payment', 18, 2)->default(0);
            $table->string('payment_method');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_payments');
    }
};
