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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // admin or officer who created the notification
            $table->enum('type', ['info', 'warning', 'error'])->default('info'); // type of notification
            $table->enum('category', ['system', 'user', 'transaction', 'application'])->default('system'); // category of notification
            $table->string('reference')->unique(); // unique reference for the notification
            $table->timestamp('read_at')->nullable(); // when the notification was read
            $table->text('content');
            $table->boolean('view_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
