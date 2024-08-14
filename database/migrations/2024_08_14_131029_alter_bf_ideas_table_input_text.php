<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->text('text_input')->change(); // Ändere den Datentyp von VARCHAR(255) zu TEXT
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->string('text_input', 255)->change(); // Ändere den Datentyp zurück auf VARCHAR(255)
        });
    }
};
