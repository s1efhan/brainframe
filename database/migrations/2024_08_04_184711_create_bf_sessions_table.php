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
            $table->id(); // Primärschlüssel für `bf_sessions`
            $table->unsignedBigInteger('host_id'); // Die Fremdschlüssel-Spalte für `users`
            $table->unsignedBigInteger('method_id'); // Die Fremdschlüssel-Spalte für `bf_methods`
            $table->integer('input_token')->nullable();
            $table->integer('output_token')->nullable();
            $table->string('contributors_amount')->after('active_phase')->default('0');
            $table->string('active_phase')->nullable()->after('target');
            $table->integer('active_round')->nullable()->after('active_phase');
            $table->string('target')->nullable();
            $table->timestamps(); // `created_at` und `updated_at`

            // Definiere den Fremdschlüssel für `host_id`
            $table->foreign('host_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade'); // Optional: Bei Löschen eines Benutzers werden auch die zugehörigen Sessions gelöscht

            // Definiere den Fremdschlüssel für `method_id`
            $table->foreign('method_id')
                  ->references('id')
                  ->on('bf_methods')
                  ->onDelete('cascade'); // Optional: Bei Löschen eines Methods werden auch die zugehörigen Sessions gelöscht
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
