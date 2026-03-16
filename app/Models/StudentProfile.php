<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'student_id', 'age', 'gender', 'previous_language_experience',
        'learning_goal', 'preferred_time', 'photo_path', 'questionnaire_answers',
    ];

    protected $casts = ['questionnaire_answers' => 'array'];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
}
