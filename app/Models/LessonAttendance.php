<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    protected $fillable = [
        'lesson_id', 'student_id', 'status', 'checked_in_at', 'check_in_method',
    ];

    protected $casts = ['checked_in_at' => 'datetime'];

    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
}
