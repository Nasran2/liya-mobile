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
            $table->boolean('show_on_storefront')->default(true)->after('is_active');
            $table->boolean('show_storefront_price')->default(true)->after('show_on_storefront');
            $table->decimal('storefront_price', 12, 2)->nullable()->after('show_storefront_price');
            $table->index(['show_on_storefront', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['show_on_storefront', 'is_active']);
            $table->dropColumn([
                'show_on_storefront',
                'show_storefront_price',
                'storefront_price',
            ]);
        });
    }
};
