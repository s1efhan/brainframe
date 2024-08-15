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
        Schema::table('bf_sessions', function (Blueprint $table) {
            $table->string('active_phase')->nullable()->after('target');
            $table->integer('active_round')->nullable()->after('active_phase');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_sessions', function (Blueprint $table) {
            $table->dropColumn('active_phase');
            $table->dropColumn('active_round');
            
        });
    }
};
