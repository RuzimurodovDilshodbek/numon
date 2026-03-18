<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'group_id', 'lesson_id', 'title', 'description',
        'type', 'due_date', 'max_score', 'is_active', 'created_by',
        'attachment_path',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function group() { return $this->belongsTo(Group::class); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(TaskSubmission::class); }
    public function vocabularyTask() { return $this->hasOne(VocabularyTask::class); }
}
