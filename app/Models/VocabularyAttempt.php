<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabularyAttempt extends Model
{
    protected $fillable = [
        'vocabulary_task_id', 'student_id', 'started_at', 'finished_at',
        'total_words', 'correct_words', 'score_percent', 'is_passed',
        'attempt_number', 'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'is_passed' => 'boolean',
        'metadata' => 'array',
    ];

    public function vocabularyTask() { return $this->belongsTo(VocabularyTask::class); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function answers() { return $this->hasMany(VocabularyAttemptAnswer::class); }
}
