<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'student_id', 'phone',
        'telegram_id', 'telegram_username', 'telegram_photo_url',
        'photo_path', 'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (!$user->student_id) {
                do {
                    $id = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                } while (static::where('student_id', $id)->exists());
                $user->student_id = $id;
            }
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'student_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_students', 'student_id', 'group_id')
            ->withPivot(['joined_at', 'left_at', 'discount_percent', 'is_active'])
            ->withTimestamps();
    }

    public function activeGroups(): BelongsToMany
    {
        return $this->groups()->wherePivot('is_active', true);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(LessonAttendance::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'student_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(['super_admin', 'admin']) && $this->is_active;
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
