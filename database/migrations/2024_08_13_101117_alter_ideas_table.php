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
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->string('round')->nullable()->after('contributor_id'); // Hinzufügen der "idea" Spalte nach der "description" Spalte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->dropColumn('round'); // Entfernen der "idea" Spalte, falls die Migration zurückgesetzt wird
        });
    }
};
