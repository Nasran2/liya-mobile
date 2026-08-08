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
            // Check if column exists to prevent issues if it was partially added on live server
            if (!Schema::hasColumn('products', 'investor_id')) {
                $table->unsignedBigInteger('investor_id')->nullable()->after('unit_id');
                // Removed explicit database foreign key constraint to prevent error 1824 on live server
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'investor_id')) {
                $table->dropColumn('investor_id');
            }
        });
    }
};
