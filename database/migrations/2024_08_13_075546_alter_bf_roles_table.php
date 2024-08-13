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
        Schema::table('bf_roles', function (Blueprint $table) {
            $table->integer('icon')->nullable()->after('description'); // Hinzufügen der "icon" Spalte nach der "description" Spalte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_roles', function (Blueprint $table) {
            $table->dropColumn('icon'); // Entfernen der "icon" Spalte, falls die Migration zurückgesetzt wird
        });
    }
};
