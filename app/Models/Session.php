<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{ 
    public $incrementing = false;
    protected $table = 'bf_sessions';
    protected $fillable = ['id', 'host_id', 'method_id', 'target', 'active_phase', 'active_round'];
      // Definiert die Beziehung zu User (host_id)
      public function host()
      {
          return $this->belongsTo(User::class, 'host_id');
      }
  
      // Definiert die Beziehung zu Method (method_id)
      public function method()
      {
          return $this->belongsTo(Method::class, 'method_id');
      }
}
