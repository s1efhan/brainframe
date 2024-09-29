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
        Schema::create('bf_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('target')->nullable();
            $table->unsignedBigInteger('method_id');
            $table->unsignedBigInteger('host_id');
            $table->string('phase')->default("collecting");
            $table->integer('collecting_round')->default(0);
            $table->integer('vote_round')->default(0);
            $table->boolean('is_stopped')->default(true);
            $table->timestamps();

            // Definiere den Fremdschlüssel für `host_id`
            $table->foreign('host_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Definiere den Fremdschlüssel für `method_id`
            $table->foreign('method_id')
                  ->references('id')
                  ->on('bf_methods')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bf_sessions');
    }
};
