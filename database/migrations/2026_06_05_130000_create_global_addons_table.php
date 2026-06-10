<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates a new global 'addons' table (not tied to any specific package).
     * Drops and recreates 'package_addons' without the package_id FK.
     */
    public function up(): void
    {
        // 1. Create global addons table
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('addon_name');
            $table->decimal('price_per_pax', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
