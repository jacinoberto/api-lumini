<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;

class AppointmentStatus extends Model
{
    protected $table = 'appointment_status';
    public $timestamps = false;
}
