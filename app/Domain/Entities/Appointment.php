<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
        'barbershop_id',
        'barber_id',
        'service_id',
        'status_id',
        'start_time',
        'end_time',
        'price',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    protected $with = ['status']; // Sempre carrega o status

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AppointmentStatus::class, 'status_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // Accessors
    public function getStatusKeyAttribute(): string
    {
        return $this->status->status_key ?? 'UNKNOWN';
    }

    public function getStatusDescriptionAttribute(): string
    {
        return $this->status->description ?? 'Desconhecido';
    }

    // Helpers
    public function hasReview(): bool
    {
        return $this->review()->exists();
    }

    public function isPast(): bool
    {
        return $this->start_time->isPast();
    }

    public function isFuture(): bool
    {
        return $this->start_time->isFuture();
    }

    public function isToday(): bool
    {
        return $this->start_time->isToday();
    }

    public function isPending(): bool
    {
        return $this->status_id === AppointmentStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status_id === AppointmentStatus::CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->status_id === AppointmentStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return in_array($this->status_id, [
            AppointmentStatus::CANCELLED_BY_CLIENT,
            AppointmentStatus::CANCELLED_BY_OWNER
        ]);
    }

    public function canBeCancelled(): bool
    {
        // Pode cancelar se estiver pendente ou confirmado e ainda não passou
        return in_array($this->status_id, [
                AppointmentStatus::PENDING,
                AppointmentStatus::CONFIRMED
            ]) && $this->isFuture();
    }

    public function canBeReviewed(): bool
    {
        // Pode avaliar se estiver concluído e ainda não tiver avaliação
        return $this->isCompleted() && !$this->hasReview();
    }

    // Métodos de ação
    public function confirm(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status_id = AppointmentStatus::CONFIRMED;
        return $this->save();
    }

    public function complete(): bool
    {
        if (!$this->isConfirmed()) {
            return false;
        }

        $this->status_id = AppointmentStatus::COMPLETED;
        return $this->save();
    }

    public function cancel(string $reason = null, bool $byClient = true): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->status_id = $byClient
            ? AppointmentStatus::CANCELLED_BY_CLIENT
            : AppointmentStatus::CANCELLED_BY_OWNER;

        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;

        return $this->save();
    }

    public function markAsNoShow(): bool
    {
        if (!$this->isConfirmed() || !$this->isPast()) {
            return false;
        }

        $this->status_id = AppointmentStatus::NO_SHOW;
        return $this->save();
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
            ->whereIn('status_id', [
                AppointmentStatus::PENDING,
                AppointmentStatus::CONFIRMED
            ])
            ->orderBy('start_time', 'asc');
    }

    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('start_time', '<', now())
                ->orWhereIn('status_id', [
                    AppointmentStatus::COMPLETED,
                    AppointmentStatus::CANCELLED_BY_CLIENT,
                    AppointmentStatus::CANCELLED_BY_OWNER,
                    AppointmentStatus::NO_SHOW
                ]);
        })->orderBy('start_time', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status_id', [
            AppointmentStatus::PENDING,
            AppointmentStatus::CONFIRMED
        ]);
    }

    public function scopeForClient($query, string $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForBarbershop($query, string $barbershopId)
    {
        return $query->where('barbershop_id', $barbershopId);
    }

    public function scopeForBarber($query, string $barberId)
    {
        return $query->where('barber_id', $barberId);
    }

    public function scopeOnDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    // Observer/Events
    protected static function boot()
    {
        parent::boot();

        // Ao criar, se não tiver preço, pega do serviço
        static::creating(function ($appointment) {
            // Define status padrão
            if (!$appointment->status_id) {
                $appointment->status_id = AppointmentStatus::PENDING;
            }

            // Pega o preço do serviço
            if (!$appointment->price && $appointment->service) {
                $appointment->price = $appointment->service->price;
            }

            // Calcula end_time baseado na duração do serviço
            if (!$appointment->end_time && $appointment->service && $appointment->start_time) {
                $appointment->end_time = Carbon::parse($appointment->start_time)
                    ->addMinutes($appointment->service->duration_minutes);
            }
        });
    }
}
