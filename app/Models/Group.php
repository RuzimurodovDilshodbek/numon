<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'name', 'description', 'level', 'lesson_type',
        'zoom_link', 'monthly_fee', 'max_students',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(GroupScheduleTemplate::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_students', 'group_id', 'student_id')
            ->withPivot(['joined_at', 'left_at', 'discount_percent', 'is_active'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('is_active', true);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
