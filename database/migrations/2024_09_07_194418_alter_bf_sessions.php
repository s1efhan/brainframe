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
        Schema::table('bf_sessions', function (Blueprint $table) {
        $table->string('previous_phase')->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_sessions', function (Blueprint $table) {
            $table->dropColumn('previous_phase');
        });
    }
};
