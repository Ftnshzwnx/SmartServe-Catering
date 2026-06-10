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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_blacklisted')->default(false);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            
            // Refund Details
            $table->string('refund_bank_name')->nullable();
            $table->string('refund_account_number')->nullable();
            $table->string('refund_account_name')->nullable();

            // Reschedule request
            $table->date('reschedule_date')->nullable();
            $table->string('reschedule_time')->nullable();
            $table->string('reschedule_status')->default('none'); // 'none', 'pending', 'approved', 'rejected'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_blacklisted');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn([
                'promo_code_id',
                'discount_amount',
                'refund_bank_name',
                'refund_account_number',
                'refund_account_name',
                'reschedule_date',
                'reschedule_time',
                'reschedule_status'
            ]);
        });
    }
};
