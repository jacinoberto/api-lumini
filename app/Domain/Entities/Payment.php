<?php

namespace App\Domain\Entities;

use App\Domain\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'amount',
        'status',
        'mp_preference_id',
        'mp_payment_id',
        'payment_method',
        'payment_type',
        'init_point',
        'sandbox_init_point',
        'gateway_response',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'status'           => PaymentStatus::class,
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
        'refunded_at'      => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === PaymentStatus::Approved;
    }

    public function canBeRefunded(): bool
    {
        return $this->status === PaymentStatus::Approved && $this->mp_payment_id !== null;
    }
}
