<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPerson extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignedUserId',
        'name',
        'firstName',
        'lastName',
        'email',
        'phone',
        'extension'
    ];
}
