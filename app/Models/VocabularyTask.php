<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VocabularyTask extends Model
{
    protected $fillable = [
        'task_id', 'vocabulary_list_id', 'pass_percent', 'time_limit_minutes', 'random_order',
    ];

    protected $casts = ['random_order' => 'boolean'];

    public function task() { return $this->belongsTo(Task::class); }
    public function vocabularyList() { return $this->belongsTo(VocabularyList::class); }
    public function attempts() { return $this->hasMany(VocabularyAttempt::class); }
}
