<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunityid',
        'name',
        'monetaryValue',
        'pipelineId',
        'pipelineStageId',
        'pipelineStageUId',
        'assignedTo',
        'status',
        'lastStatusChangeAt',
        'createdAt',
        'updatedAt',
        'contactid',
        'contactname',
        'contactemail',
        'contactphone',
    ];

    public function OpportunityTags()
    {
        return $this->hasMany(OpportunityTag::class);
    }
}
