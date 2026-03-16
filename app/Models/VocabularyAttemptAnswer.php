<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabularyAttemptAnswer extends Model
{
    protected $fillable = [
        'vocabulary_attempt_id', 'vocabulary_word_id',
        'student_answer', 'is_correct', 'time_taken_seconds',
    ];

    protected $casts = ['is_correct' => 'boolean'];

    public function attempt() { return $this->belongsTo(VocabularyAttempt::class, 'vocabulary_attempt_id'); }
    public function word() { return $this->belongsTo(VocabularyWord::class, 'vocabulary_word_id'); }
}
