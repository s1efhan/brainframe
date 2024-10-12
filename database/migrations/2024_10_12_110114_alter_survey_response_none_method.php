<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bf_survey_responses', function (Blueprint $table) {
            $table->boolean('known_method_none')->after('known_method_6_thinking_hats')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_survey_responses', function (Blueprint $table) {
            $table->dropColumn('known_method_none');
        });
    }
};
