<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class SubscriptionUserPlanAssignments extends Model
{
    use HasFactory;

    protected $casts = [
        'inserted_ids' => 'array',
        'invalid_ids' => 'array',
        'all_ids' => 'array',
    ];

    protected $fillable = [
        'operator_id',
        'number_of_ids',
        'invalid_ids',
        'all_ids',
        'assignment_reason',
        'assignment_title',
        'inserted_ids',
        'subscription_plan_id'
    ];

    protected $appends = [
        'shamsi_created_at',
    ];

    public function getShamsiCreatedAtAttribute() {
        return Jalalian::forge($this->created_at)->format('H:i:s Y-m-d');
    }
}
