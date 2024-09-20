<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    /**
     * Die Attribute, die massenweise zugewiesen werden können.
     *
     * @var array
     */
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

    /**
     * Die Attribute, die als native Typen behandelt werden sollen.
     *
     * @var array
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
    ];

    /**
     * Holt die zugehörige Session.
     */
    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Holt den zugehörigen Contributor.
     */
    public function contributor()
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }
}