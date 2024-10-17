<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;
    protected $table = 'bf_votes';
    protected $orderBy = ['created_at' => 'desc'];
    protected $fillable = [
        'session_id',
        'idea_id',
        'contributor_id',
        'type',
        'value',
        'round',
        'vote_type'
    ];
    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function idea()
    {
        return $this->belongsTo(Idea::class, 'idea_id', 'id');
    }
    
    public function contributor()
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }
}
