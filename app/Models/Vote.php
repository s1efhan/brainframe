<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;
    protected $table = 'bf_votes';

    protected $fillable = [
        'session_id',
        'idea_id',
        'contributor_id',
        'vote_type',
        'vote_value',
        'vote_boolean'
    ];

    // Definiert die Beziehung zu BfSession (session_id)
    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    // Definiert die Beziehung zu BfIdea (idea_id)
    public function idea()
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }

    // Definiert die Beziehung zu BfContributor (contributor_id)
    public function contributor()
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }
}
