<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = ['name', 'label', 'description', 'minutes_before', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
