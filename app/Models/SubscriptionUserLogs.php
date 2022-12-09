<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionUserLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_user_id',
        'device_id',
    ];

    function subscriptionUserHistory(){
        return $this->belongsTo('\App\Models\SubscriptionUserHistories','id','subscription_user_id');
    }
}
