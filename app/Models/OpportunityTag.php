<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunityTag extends Model
{
    use HasFactory;
    protected $fillable = ['value', 'opportunity_id'];

    public function Opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
