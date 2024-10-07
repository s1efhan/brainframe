<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{ 
    public $incrementing = false;
    protected $orderBy = ['created_at' => 'desc'];
        protected $table = 'users';
    protected $fillable = ['id', 'email', 'password', 'token', 'survey_activated', 'survey_email'];

    public function sessions()
    {
        return $this->hasMany(Session::class, 'host_id');
    }
}
