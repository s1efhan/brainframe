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
        Schema::create('bf_methods_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('method_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('method_id')->references('id')->on('bf_methods')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('bf_roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bf_methods_roles');
    }
};
