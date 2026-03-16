<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotRegistration extends Model
{
    protected $fillable = ['telegram_id', 'student_id', 'step', 'temp_data'];
    protected $casts = ['temp_data' => 'array'];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
}
