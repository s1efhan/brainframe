<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{ 
    public $incrementing = false;
    protected $table = 'bf_sessions';
    protected $orderBy = ['created_at' => 'desc'];
    protected $fillable = ['id', 'host_id', 'method_id', 'target', 'phase','seconds_left', 'collecting_round','vote_round', 'is_paused'];
      public function host()
      {
          return $this->belongsTo(User::class, 'host_id');
      }
      public function contributors()
      {
          return $this->hasMany(Contributor::class, 'session_id');
      }
      public function method()
      {
          return $this->belongsTo(Method::class, 'method_id');
      }
}
