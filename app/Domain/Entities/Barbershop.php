<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barbershop extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'barbershops';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_id',
        'address_id',
        'name',
        'company_code',
        'profile_image_url',
        'cover_image_url',
        'biography',
        'requires_prepayment',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_prepayment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Defines the relationship: a Barbershop belongs to an Owner (User).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Defines the relationship: a Barbershop has one Address.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function barbers(): HasMany
    {
        return $this->hasMany(Barber::class, 'barbershop_id');
    }

    /**
     * Defines the relationship: a Barbershop has many Services.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'barbershop_id');
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class, 'barbershop_id');
    }
}
