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
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade'); // who made the payment
            $table->foreignId('charge_id')->nullable()->constrained('charges')->nullOnDelete(); // optional - in case it's tied to a due/levy/fine
            $table->string('reference')->unique(); // e.g. paystack ref or internal reference
            $table->decimal('amount', 12, 2); // how much was paid
            $table->enum('status', ['pending', 'successful', 'failed', 'refunded'])->default('pending');
            $table->enum('method', ['cash', 'bank_transfer', 'paystack', 'wallet', 'manual'])->default('manual');
            $table->string('narration')->nullable(); // optional note or purpose
            $table->timestamp('paid_at')->nullable(); // when it was paid
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // admin or officer who recorded it
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete(); // payment method used
            $table->string('receipt_file')->nullable(); // optional URL to the transaction receipt
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
