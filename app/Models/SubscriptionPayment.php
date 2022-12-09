<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

//use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPayment extends BaseModel
{
    use HasFactory,SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'store_id',
        'price',
        'currency',
        'payment_type',
        'payment_id',
        'credit_id',
        'device_id',
        'device_type',
        'app_version',
        'campaign_id',
        'discount_code',
        'discount_price',
        'is_processed',
        'amount'
    ];
}
