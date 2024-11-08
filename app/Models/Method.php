<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Method extends Model
{
    protected $table = 'bf_methods';
    protected $fillable = ['name'];
    protected $orderBy = ['created_at' => 'desc'];
    public function sessions()
    {
        return $this->hasMany(Session::class, 'method_id');
    }
}
