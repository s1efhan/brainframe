<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $orderBy = ['created_at' => 'desc'];
    protected $fillable = [
        'session_id',
        'contributor_id',
        'request_data',
        'response_data',
        'icebreaker_msg',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }
    public function contributor()
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }


    // Der folgende Codeabschnitt wurde mit Unterstützung von Claude 3.5 Sonnet erstellt
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];
}