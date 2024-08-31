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
        Schema::table('bf_contributors', function (Blueprint $table) {
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_ping')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_contributors', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropColumn('last_ping');
        });
    }
};