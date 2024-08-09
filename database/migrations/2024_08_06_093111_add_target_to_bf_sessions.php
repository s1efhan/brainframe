<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bf_sessions', function (Blueprint $table) {
            $table->string('target')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bf_sessions', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};