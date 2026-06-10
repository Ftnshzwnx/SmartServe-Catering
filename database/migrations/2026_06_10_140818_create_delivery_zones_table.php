<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('fee', 10, 2);
            $table->timestamps();
        });

        // Seed initial default districts
        DB::table('delivery_zones')->insert([
            ['name' => 'Kuala Nerus', 'fee' => 30.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kuala Terengganu', 'fee' => 50.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Marang', 'fee' => 80.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hulu Terengganu', 'fee' => 100.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Setiu', 'fee' => 100.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
