<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bf_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('session_id');
            $table->timestamp('timestamp')->useCurrent();

            // Quantitative Bewertungen (1-5 Skala)
            $table->integer('ideas_novelty_relevance')->nullable();
            $table->integer('ideas_quantity_diversity')->nullable();
            $table->integer('tool_ease_of_use')->nullable();
            $table->integer('tool_thought_organization')->nullable();
            $table->integer('anonymous_input_openness')->nullable();
            $table->integer('ai_support_helpfulness')->nullable();
            $table->integer('ai_suggestions_relevance')->nullable();
            $table->integer('ai_inspiration')->nullable();
            $table->integer('structure_method_facilitation')->nullable();
            $table->integer('tool_effectiveness')->nullable();
            $table->integer('idea_evaluation_transparency')->nullable();
            $table->integer('rating_methods_understandability')->nullable();
            $table->integer('result_pdf_usefulness')->nullable();
            $table->integer('result_pdf_clarity')->nullable();
            $table->integer('tool_future_use')->nullable();
            $table->integer('tool_recommendation')->nullable();
            $table->integer('session_expectations')->nullable();

            // Vorkenntnis von Methoden (Boolean-Flags)
            $table->boolean('known_method_635')->default(false);
            $table->boolean('known_method_walt_disney')->default(false);
            $table->boolean('known_method_crazy_8')->default(false);
            $table->boolean('known_method_brainstorming')->default(false);
            $table->boolean('known_method_6_thinking_hats')->default(false);

            // Qualitative Antworten
            $table->text('valuable_aspects')->nullable();
            $table->text('desired_improvements')->nullable();
            $table->text('unexpected_benefits_challenges')->nullable();
            $table->text('additional_comments')->nullable();

            // Demografische Informationen
            $table->integer('age')->nullable();
            $table->string('occupation')->nullable();
            $table->string('industry')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bf_survey_responses');
    }
};