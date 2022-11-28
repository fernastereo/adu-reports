<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'customFieldId',
        'value',
        'contact_id',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
