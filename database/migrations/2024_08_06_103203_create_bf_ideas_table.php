<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
            $table->unsignedBigInteger('session_id'); // Fügen Sie diese Zeile hinzu
            $table->foreign('session_id')
                ->references('id')
                ->on('bf_sessions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('round');
            $table->foreignId('contributor_id')->constrained('bf_contributors');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('tag')->nullable();
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
