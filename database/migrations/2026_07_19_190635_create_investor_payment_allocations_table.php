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
        Schema::create('investor_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_payment_id')->constrained('investor_payments')->cascadeOnDelete();
            $table->nullableMorphs('allocatable', 'inv_pay_allocs_allocatable_index'); // can be InvestorProfitTransaction or InvestorPurchaseFunding
            $table->decimal('amount', 18, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_payment_allocations');
    }
};
