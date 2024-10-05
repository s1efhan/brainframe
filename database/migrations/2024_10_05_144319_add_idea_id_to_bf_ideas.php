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
            $table->unsignedBigInteger('original_idea_id')->nullable()->after('id');
            $table->foreign('original_idea_id')
                ->references('id')
                ->on('bf_ideas')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->dropForeign(['original_idea_id']);
            $table->dropColumn('original_idea_id');
        });
    }
};