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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('institution_uid')->unique()->nullable();

            // Link to user (owner/creator)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Basic details
            $table->string('institution_name')->nullable();
            $table->enum('institution_type', ['Microfinance', 'Cooperative', 'Other'])->nullable();
            $table->enum('category_type', ['unit','state', 'federal'])->nullable(); // You can adjust types
            $table->date('date_of_establishment')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('regulatory_body')->nullable();
            $table->string('operating_state')->nullable();
            $table->string('institution_logo')->nullable();

            // Uploads
            $table->string('certificate_of_registration')->nullable();
            $table->string('operational_license')->nullable();
            $table->string('constitution')->nullable(); // or bye-laws
            $table->string('latest_annual_report')->nullable();
            $table->string('letter_of_intent')->nullable();
            $table->string('board_resolution')->nullable();
            $table->string('passport_photograph')->nullable();
            $table->string('other_supporting_document')->nullable();

            // Agreements
            $table->boolean('membership_agreement')->default(false);
            $table->boolean('terms_agreement')->default(false);

            // Contact & location
            $table->string('head_office')->nullable();
            $table->string('business_operation_address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('website_url')->nullable();
            $table->text('descriptions')->nullable();

            // Approval status
            $table->boolean('is_approved')->default(false);
            $table->string('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
