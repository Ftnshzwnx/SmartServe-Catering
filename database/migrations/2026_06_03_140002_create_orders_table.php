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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('package_name'); // Summarized text of selected packages
            $table->text('delivery_address');
            $table->decimal('total_price', 10, 2);
            $table->string('package_image')->nullable();
            $table->string('payment_proof')->nullable(); // Path to receipt
            $table->enum('status', ['Pending', 'Payment Submitted', 'Confirmed', 'Delivered', 'Completed', 'Cancelled', 'Deposit Rejected', 'Balance Rejected'])->default('Pending');
            $table->date('delivery_date');
            $table->time('delivery_time');
            $table->string('qr_code_path')->nullable();
            $table->enum('cancelled_by', ['user', 'admin'])->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
