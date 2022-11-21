<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contactid',
        'locationId',
        'contactName',
        'firstName',
        'lastName',
        'companyName',
        'email',
        'phone',
        'dnd',
        'type',
        'source',
        'assignedTo',
        'city',
        'state',
        'postalCode',
        'address1',
        'dateAdded',
        'dateUpdated',
        'dateOfBirth',
        'lastActivity'
    ];

    public function tags()
    {
        return $this->hasMany(ContactTag::class);
    }

    public function customFields()
    {
        return $this->hasMany(ContactCustomField::class);
    }
}
