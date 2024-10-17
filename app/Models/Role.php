<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'bf_roles';
    protected $orderBy = ['created_at' => 'desc'];
    protected $fillable = [
        'name',
        'description',
        'icon'
    ];

    public function methods()
    {
        return $this->belongsToMany(Method::class, 'bf_methods_roles', 'role_id', 'method_id');
    }

    public function contributors()
    {
        return $this->belongsToMany(Contributor::class, 'bf_contributor_roles', 'role_id', 'contributor_id');
    }
}
