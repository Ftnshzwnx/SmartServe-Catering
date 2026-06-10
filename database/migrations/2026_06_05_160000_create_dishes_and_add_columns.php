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
        // 1. Create dishes table
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // e.g. Nasi, Ayam, Daging, Sayuran, Pencuci Mulut, Minuman
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // 2. Create package_dish pivot table
        Schema::create('package_dish', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Add dish_limits to packages table
        Schema::table('packages', function (Blueprint $table) {
            $table->json('dish_limits')->nullable();
        });

        // 4. Add selected_dishes to carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->json('selected_dishes')->nullable();
        });

        // 5. Add selected_dishes to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->json('selected_dishes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('selected_dishes');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('selected_dishes');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('dish_limits');
        });

        Schema::dropIfExists('package_dish');
        Schema::dropIfExists('dishes');
    }
};
