<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionJobs extends Model
{
    use HasFactory;

    protected $table = 'subscription_jobs';

    protected $fillable = ['settlements', 'subscription_users', 'subscription_users_entities', 'subscription_users_entities_shares'];

    protected $casts = [
        'settlements' => 'array',
        'subscription_users' => 'array',
        'subscription_users_entities' => 'array',
        'subscription_users_entities_shares' => 'array'
    ];
}
