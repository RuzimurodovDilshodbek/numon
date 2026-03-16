<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupScheduleTemplate extends Model
{
    protected $fillable = ['group_id', 'day_of_week', 'start_time', 'end_time', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function getDayNameAttribute(): string
    {
        $days = ['Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba', 'Yakshanba'];
        return $days[$this->day_of_week] ?? '';
    }
}
