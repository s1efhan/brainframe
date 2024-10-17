<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;
    protected $table = 'bf_survey_responses';
    protected $fillable = [
        'user_id',
        'session_id',
        'ideas_novelty_relevance',
        'ideas_quantity_diversity',
        'tool_ease_of_use',
        'tool_thought_organization',
        'anonymous_input_openness',
        'ai_support_helpfulness',
        'ai_suggestions_relevance',
        'ai_inspiration',
        'structure_method_facilitation',
        'tool_effectiveness',
        'idea_evaluation_transparency',
        'rating_methods_understandability',
        'result_pdf_usefulness',
        'result_pdf_clarity',
        'tool_future_use',
        'tool_recommendation',
        'session_expectations',
        'known_method_635',
        'known_method_walt_disney',
        'known_method_crazy_8',
        'known_method_brainstorming',
        'known_method_6_thinking_hats',
        'valuable_aspects',
        'desired_improvements',
        'unexpected_benefits_challenges',
        'additional_comments',
        'age',
        'occupation',
        'industry',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
    protected $casts = [
        'session_id' => 'integer',
        'known_method_635' => 'boolean',
        'known_method_walt_disney' => 'boolean',
        'known_method_crazy_8' => 'boolean',
        'known_method_brainstorming' => 'boolean',
        'known_method_6_thinking_hats' => 'boolean',
        'known_method_none' => 'boolean'
    ];
}
