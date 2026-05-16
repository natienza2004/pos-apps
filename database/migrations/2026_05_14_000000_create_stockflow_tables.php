<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->string('sku', 191)->unique();
            $table->string('name', 191);
            $table->string('category', 191);
            $table->string('unit', 30);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('starting_stock', 12, 3)->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->boolean('include_in_costing')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['In', 'Out']);
            $table->decimal('quantity', 12, 3);
            $table->string('department', 191);
            $table->string('reason', 191)->nullable();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index(['type', 'department']);
            $table->index('created_at');
        });

        Schema::create('inventory_records', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->date('inventory_date');
            $table->decimal('starting_stock', 12, 3)->default(0);
            $table->decimal('stock_in', 12, 3)->default(0);
            $table->decimal('kitchen_out', 12, 3)->default(0);
            $table->decimal('bakery_out', 12, 3)->default(0);
            $table->decimal('ending_stock', 12, 3)->default(0);
            $table->decimal('kitchen_cost', 12, 2)->default(0);
            $table->decimal('bakery_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'inventory_date']);
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('inventory_records');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};
