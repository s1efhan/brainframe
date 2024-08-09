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
        Schema::create('bf_ideas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('text_input')->nullable();
            $table->string('image_file_url')->nullable();
            $table->foreignId('session_id')->constrained('bf_sessions');
            $table->foreignId('contributor_id')->constrained('bf_contributors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bf_ideas');
    }
};
