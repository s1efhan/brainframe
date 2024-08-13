<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use HasFactory;
    protected $table = 'bf_ideas';
    protected $fillable = ['text_input', 'session_id', 'contributor_id', 'image_file_url', 'round'];

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function contributor()
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }
}

