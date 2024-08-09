<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'bf_roles'; // Name der Tabelle in der Datenbank

    protected $fillable = [
        'name',
        'description',
    ];

    // Beziehungen

    /**
     * Die vielen-to-viele Beziehung zu Sessions durch die bf_sessions_roles Tabelle.
     */
    public function methods()
    {
        return $this->belongsToMany(Method::class, 'bf_methods_roles', 'role_id', 'method_id');
    }

    /**
     * Die vielen-to-viele Beziehung zu Contributors durch die bf_contributor_roles Tabelle.
     */
    public function contributors()
    {
        return $this->belongsToMany(Contributor::class, 'bf_contributor_roles', 'role_id', 'contributor_id');
    }
}
