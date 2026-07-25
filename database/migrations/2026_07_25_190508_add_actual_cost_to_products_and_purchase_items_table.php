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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('actual_cost', 12, 2)->after('cost_price')->nullable();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('actual_cost', 12, 2)->after('cost_price')->nullable();
            $table->decimal('actual_subtotal', 12, 2)->after('subtotal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('actual_cost');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['actual_cost', 'actual_subtotal']);
        });
    }
};
