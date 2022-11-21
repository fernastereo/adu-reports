<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointmentid',
        'selectedTimezone',
        'notes',
        'contactId',
        'locationId',
        'isFree',
        'title',
        'isRecurring',
        'address',
        'assignedUserId',
        'calendarId',
        'appoinmentStatus',
        'calendarProviderId',
        'userCalendarId',
        'status',
        'appointmentStatus',
        'appointmentstartTime',
        'appointmentendTime',
        'appointmentcreatedAt',
        'appointmentupdatedAt',
        'contactfirstName',
        'contactemail',
        'contactfingerprint',
        'contactfirstNameLowerCase',
        'contactfullNameLowerCase',
        'contacttimezone',
        'contactemailLowerCase',
        'contactlastName',
        'contactlocationId',
        'contactcountry',
        'contactphone',
        'contactlastNameLowerCase',
        'contacttype',
        'contactdateAdded',
        'contactpostalCode',
        'contactsource',
    ];

    public function AppointmentCustomFields()
    {
        return $this->hasMany(AppointmentCustomField::class);
    }

    public function AppointmentTags()
    {
        return $this->hasMany(AppointmentTag::class);
    }

    public function AppointmentAttachments()
    {
        return $this->hasMany(AppointmentAttachment::class);
    }
}
