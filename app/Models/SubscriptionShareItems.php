<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionShareItems extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'subscription_user_id',
        'subscription_plan_entity_id',
        'subscription_share_id',
    ];
}
