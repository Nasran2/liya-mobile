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
        Schema::create('investor_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('transaction_no')->nullable()->index();
            $table->string('transaction_type')->index(); // opening_balance, sale_profit, purchase_funding, profit_payment, purchase_repayment, combined_payment, reversal, adjustment
            $table->nullableMorphs('source'); // Payment, Sale, Purchase, Adjustment
            $table->string('description')->nullable();
            
            // Running balances
            $table->decimal('profit_debit', 18, 2)->default(0);
            $table->decimal('profit_credit', 18, 2)->default(0);
            $table->decimal('profit_balance', 18, 2)->default(0);
            
            $table->decimal('purchase_debit', 18, 2)->default(0);
            $table->decimal('purchase_credit', 18, 2)->default(0);
            $table->decimal('purchase_balance', 18, 2)->default(0);
            
            $table->decimal('total_payable_balance', 18, 2)->default(0);
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_ledger_entries');
    }
};
