<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up()
    {
        Schema::create('bf_session_details_cache', function (Blueprint $table) {
            $table->integer('session_id')->primary(); // Primärschlüssel als UUID
            $table->string('target')->nullable();
            $table->json('top_ideas')->nullable();
            $table->json('ideas')->nullable();
            $table->integer('contributors_count')->nullable();
            $table->integer('ideas_count')->nullable();
            $table->decimal('duration', 8, 2)->nullable();
            $table->date('date')->nullable();
            $table->string('method')->nullable();
            $table->integer('input_token')->default(0);
            $table->integer('output_token')->default(0);
            $table->json('word_cloud_data')->nullable();
            $table->json('tag_list')->nullable();
            $table->json('next_steps')->nullable();
            $table->timestamps(); // Für created_at und updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('bf_session_details_cache');
    }
};
