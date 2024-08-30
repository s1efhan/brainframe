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
            $table->text('text_input')->nullable();
            $table->string('image_file_url')->nullable();
            $table->foreignId('session_id')->constrained('bf_sessions');
            $table->foreignId('contributor_id')->constrained('bf_contributors');
            $table->string('idea_title')->nullable()->after('round');
            $table->string('idea_description')->nullable()->after('idea_title');
            $table->string('tag')->nullable()->after('idea_description');
            $table->string('round')->nullable()->after('contributor_id'); 
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
