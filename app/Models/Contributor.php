<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contributor extends Model
{
    use HasFactory;
    protected $table = 'bf_contributors';

    protected $fillable = [
        'session_id', 'role_id', 'user_id', 'is_active', 'last_ping'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_ping' => 'datetime',
    ];
    // Beziehungen

    /**
     * Die viele-to-viele Beziehung zu Roles durch die bf_contributor_roles Tabelle.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'bf_contributor_roles', 'contributor_id', 'role_id');
    }
        public function role()
        {
            return $this->belongsTo(Role::class, 'role_id');
        }
        public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    
}
