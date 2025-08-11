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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Paystack, Flutterwave, Bank Transfer
            $table->string('slug')->unique(); // paystack, flutterwave, bank-transfer
            $table->string('logo')->nullable(); // Logo of the payment method
            $table->enum('mode', ['test', 'live'])->default('live'); // Payment mode

            $table->string('test_public_key')->nullable(); // Public key for API-based payment methods
            $table->string('test_secret_key')->nullable(); // Secret key for API-based payment methods

            $table->string('live_public_key')->nullable(); // Public key for API-based payment methods
            $table->string('live_secret_key')->nullable(); // Secret key for API-based payment methods

            $table->string('account_name')->nullable(); // Account name for bank transfers
            $table->string('account_number')->nullable(); // Account number for bank transfers
            $table->string('bank_name')->nullable(); // Bank name for bank transfers
            $table->string('currency')->default('NGN'); // Currency for the payment method,

            $table->boolean('is_active')->default(true); // Enable or disable payment method
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
