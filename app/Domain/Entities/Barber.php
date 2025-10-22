<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'barbers';
    public $timestamps = false;

    protected $fillable = [
        'barbershop_id',
        'name',
        'profile_image_url',
        'specialties',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Defines the relationship: a Barber belongs to a Barbershop.
     */
    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class, 'barbershop_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Um barbeiro tem uma grade de horários (WorkingHour)
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }
}
