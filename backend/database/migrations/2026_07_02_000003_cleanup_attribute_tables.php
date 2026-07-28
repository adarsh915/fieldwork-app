<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing tables if they exist (cleanup from failed migrations)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        if (Schema::hasTable('product_has_attributes')) {
            Schema::drop('product_has_attributes');
        }
        if (Schema::hasTable('variant_attributes')) {
            Schema::drop('variant_attributes');
        }
        if (Schema::hasTable('product_variant_attributes')) {
            Schema::drop('product_variant_attributes');
        }
        if (Schema::hasTable('product_variants')) {
            Schema::drop('product_variants');
        }
        if (Schema::hasTable('product_attribute_values')) {
            Schema::drop('product_attribute_values');
        }
        if (Schema::hasTable('product_attributes')) {
            Schema::drop('product_attributes');
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Now create the tables fresh
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('input_type', ['text', 'select', 'multiselect', 'color', 'date'])->default('select');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->string('slug')->nullable();
            $table->string('color_code')->nullable()->comment('For color attributes');
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['product_attribute_id', 'value']);
            $table->index('slug');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->string('image')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku');
            $table->index('status');
        });

        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_variant_id', 'product_attribute_id'], 'idx_variant_attr_uniq');
            $table->index('product_attribute_id');
        });

        Schema::create('product_has_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'product_attribute_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_has_attributes');
        Schema::dropIfExists('variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
