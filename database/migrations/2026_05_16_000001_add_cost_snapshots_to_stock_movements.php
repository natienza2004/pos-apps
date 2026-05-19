<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->boolean('include_in_costing')->nullable()->after('reason');
            $table->decimal('unit_price', 12, 2)->nullable()->after('include_in_costing');
            $table->decimal('total_cost', 12, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropColumn(['include_in_costing', 'unit_price', 'total_cost']);
        });
    }
};
