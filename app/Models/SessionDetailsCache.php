<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionDetailsCache extends Model
{
    use HasFactory;

    // Definiere den Namen der Tabelle, falls es nicht der Standardname ist
    protected $table = 'bf_session_details_cache';

    // Definiere die primäre Schlüssel-Spalte
    protected $primaryKey = 'session_id';

    // Setze den Primärschlüssel als UUID (falls UUID verwendet wird)
    public $incrementing = false;
    protected $keyType = 'string';

    // Definiere die Attribute, die massenweise zuweisbar sind
    protected $fillable = [
        'session_id',
        'target',
        'top_ideas',
        'ideas',
        'contributors_count',
        'ideas_count',
        'duration',
        'date',
        'method',
        'input_token',
        'output_token',
        'word_cloud_data',
        'tag_list',
        'next_steps',
        'data'
    ];

    // Definiere, welche Attribute als JSON konvertiert werden sollen
    protected $casts = [
        'top_ideas' => 'array',
        'ideas' => 'array',
        'word_cloud_data' => 'array',
        'tag_list' => 'array',
        'next_steps' => 'array',
        'data' => 'array',
        'date' => 'date',
    ];

    // Standard-Timestamps werden nicht benötigt, da wir diese manuell setzen
    public $timestamps = false;
}
