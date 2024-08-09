<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{ 
    public $incrementing = false;
    protected $table = 'bf_users';
    protected $fillable = ['id', 'email', 'password'];
    public function sessions()
    {
        return $this->hasMany(Session::class, 'host_id');
    }
}
