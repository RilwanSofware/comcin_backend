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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade'); // member being charged
            $table->string('title'); // e.g., Monthly Dues, Welfare Levy
            $table->text('description')->nullable(); // optional explanation
            $table->enum('type', ['due', 'levy', 'fine']); // categorize charges
            $table->decimal('amount', 12, 2); // amount of the charge
            $table->enum('status', ['pending','unpaid', 'paid'])->default('unpaid');
            $table->date('due_date')->nullable(); // optional due date
            $table->timestamp('paid_at')->nullable(); // when it was paid
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // admin or officer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
