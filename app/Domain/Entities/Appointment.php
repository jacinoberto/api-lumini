<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'appointments';

    protected $fillable = [
        'client_id',
        'barbershop_id',
        'barber_id',
        'service_id',
        'status_id',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class, 'barbershop_id');
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class, 'barber_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AppointmentStatus::class, 'status_id');
    }
}
