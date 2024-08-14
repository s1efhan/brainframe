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
            $table->string('idea_title')->nullable()->after('round');
            $table->string('idea_description')->nullable()->after('idea_title');
            $table->string('tag')->nullable()->after('idea_description');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->dropColumn('idea_title');
            $table->dropColumn('idea_description');
            $table->dropColumn('tag');
            
        });
    }
};
