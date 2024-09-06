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
        Schema::table('bf_session_details_cache', function (Blueprint $table) {
            $table->json('contributor_emails')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_session_details_cache', function (Blueprint $table) {
            $table->dropColumn('contributor_emails');
        });
    }
};
