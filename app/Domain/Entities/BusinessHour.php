<?php
namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHour extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'business_hours';
    public $timestamps = false;

    protected $fillable = [
        'barbershop_id', 'day_of_week', 'start_time', 'end_time', 'is_active'
    ];

    protected function casts(): array {
        return ['is_active' => 'boolean'];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }
}
