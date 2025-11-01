<?php
// app/Models/AppointmentStatus.php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentStatus extends Model
{
    use HasFactory;

    protected $table = 'appointment_status';

    public $timestamps = false;

    // Constantes para os status
    const PENDING = 1;
    const CONFIRMED = 2;
    const COMPLETED = 3;
    const CANCELLED_BY_CLIENT = 4;
    const CANCELLED_BY_OWNER = 5;
    const NO_SHOW = 6;

    protected $fillable = [
        'status_key',
        'description',
    ];

    // Métodos estáticos corrigidos
    public static function getKeyById(int $id): ?string
    {
        $status = self::find($id);
        return $status ? $status->status_key : null;
    }

    public static function getIdByKey(string $key): ?int
    {
        $status = self::where('status_key', $key)->first();
        return $status ? $status->id : null;
    }

    // Métodos de instância
    public function isCancelled(): bool
    {
        return in_array($this->id, [
            self::CANCELLED_BY_CLIENT,
            self::CANCELLED_BY_OWNER
        ]);
    }

    public function isActive(): bool
    {
        return in_array($this->id, [
            self::PENDING,
            self::CONFIRMED
        ]);
    }

    public function isFinished(): bool
    {
        return in_array($this->id, [
            self::COMPLETED,
            self::NO_SHOW
        ]);
    }

    // Helper para obter descrição amigável
    public function getFriendlyDescription(): string
    {
        return match($this->id) {
            self::PENDING => 'Aguardando confirmação',
            self::CONFIRMED => 'Confirmado',
            self::COMPLETED => 'Concluído',
            self::CANCELLED_BY_CLIENT => 'Cancelado por você',
            self::CANCELLED_BY_OWNER => 'Cancelado pela barbearia',
            self::NO_SHOW => 'Você não compareceu',
            default => $this->description,
        };
    }

    // Helper para obter cor (útil para frontend)
    public function getColorClass(): string
    {
        return match($this->id) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::COMPLETED => 'info',
            self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_OWNER => 'danger',
            self::NO_SHOW => 'secondary',
            default => 'default',
        };
    }
}
