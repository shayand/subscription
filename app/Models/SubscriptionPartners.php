<?php

namespace App\Models;

use App\Constants\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPartners extends Model
{
    use HasFactory,SoftDeletes;

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'endpoint_path',
        'client_id',
        'client_secret',
        'provision_key',
        'scope',
    ];

    public function plans() {
        return $this->belongsToMany(
            SubscriptionPlans::class,
            Tables::SUBSCRIPTION_PARTNERS_PLANS,
            'partner_id',
            'plan_id')
            ->wherePivot('deleted_at', '=', null);
    }
}
