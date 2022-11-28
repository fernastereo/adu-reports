<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['value', 'appointment_id'];

    public function Appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
