<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'channel', 'sent_at', 'read_at', 'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
