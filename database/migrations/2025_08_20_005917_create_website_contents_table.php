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
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section')->index(); // Section (hero, about, contact, footer, settings etc.)
            $table->string('key')->nullable(); // Key for identifying field inside the section (title, subtitle, body, image, etc.)
            $table->text('value')->nullable(); // Store the content itself (can be text, JSON, etc.
            $table->string('media')->nullable(); // Optional: image/file attachment if needed
            $table->unsignedBigInteger('updated_by')->nullable(); // Track who updated it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_contents');
    }
};
