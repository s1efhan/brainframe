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
        Schema::create('bf_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('idea_id');
            $table->unsignedBigInteger('contributor_id');
            $table->enum('vote_type', ['swipe', 'left_right', 'star', 'ranking']);
            $table->integer('value');
            $table->integer('round');
            $table->timestamps();
            $table->foreign('session_id')->references('id')->on('bf_sessions')->onDelete('cascade');
            $table->foreign('idea_id')->references('id')->on('bf_ideas')->onDelete('cascade');
            $table->foreign('contributor_id')->references('id')->on('bf_contributors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_bf_votes');
    }
};
