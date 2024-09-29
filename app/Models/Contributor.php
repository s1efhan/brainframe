<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contributor extends Model
{
    use HasFactory;

    protected $table = 'bf_contributors';
    protected $orderBy = ['created_at' => 'desc'];
    protected $fillable = [
        'session_id', 'role_id', 'user_id', 'is_active', 'last_ping'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'last_ping' => 'datetime',
    ];

    // Bestehende Beziehungen

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'bf_contributor_roles', 'contributor_id', 'role_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Neue Beziehungen

    public function ideas()
    {
        return $this->hasMany(Idea::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}