<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDescriptionColumnTypeInBfIdeasTable extends Migration
{
    public function up()
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('bf_ideas', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
        });
    }
}