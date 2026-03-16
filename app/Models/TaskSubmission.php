<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSubmission extends Model
{
    protected $fillable = [
        'task_id', 'student_id', 'status', 'file_path',
        'text_content', 'score', 'teacher_comment',
        'graded_at', 'graded_by', 'submitted_at',
    ];

    protected $casts = [
        'graded_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function task() { return $this->belongsTo(Task::class); }
    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function gradedBy() { return $this->belongsTo(User::class, 'graded_by'); }
}
