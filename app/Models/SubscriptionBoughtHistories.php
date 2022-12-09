<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

//use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class SubscriptionBoughtHistories extends BaseModel
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'entity_id'
    ];
}
