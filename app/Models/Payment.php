<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id', 'group_id', 'amount', 'discount_percent',
        'final_amount', 'period_month', 'status', 'paid_at',
        'payment_method', 'note', 'recorded_by',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'period_month' => 'date',
        'amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function group() { return $this->belongsTo(Group::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public static function calculateFinal(float $amount, int $discount): float
    {
        return round($amount * (1 - $discount / 100), 2);
    }
}
