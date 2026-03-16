<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'group_id', 'title', 'description', 'type',
        'lesson_date', 'start_time', 'end_time',
        'zoom_link', 'secret_code', 'secret_code_expires_at',
        'status', 'notes', 'is_auto_generated',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'secret_code_expires_at' => 'datetime',
        'is_auto_generated' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LessonAttendance::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isSecretCodeValid(string $code): bool
    {
        return $this->secret_code === $code
            && $this->secret_code_expires_at
            && now()->lt($this->secret_code_expires_at);
    }
}
