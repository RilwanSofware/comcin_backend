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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_uid')->unique();

            $table->foreignId('member_id')->constrained('users')->onDelete('cascade'); // Foreign key to the user who owns the certificate
            $table->string('name'); // Name of the certificate
            $table->enum('type', ['membership', 'training', 'achievement', 'other'])->default('other'); // Type of certificate
            $table->text('description')->nullable(); // Description of the certificate
            $table->string('file')->nullable(); // File path for the certificate document
            $table->enum('status', ['processing', 'published'])->default('processing'); // Status of the certificate
           
            $table->date('issue_date')->nullable(); // Date when the certificate was issued
            $table->date('expiry_date')->nullable(); // Expiry date of the certificate
          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
