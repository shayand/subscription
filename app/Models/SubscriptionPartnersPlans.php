<?php

namespace App\Models;

use App\Constants\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPartnersPlans extends Model
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
        'partner_id',
        'plan_id'
    ];

    /**
     * @param int $partnerId
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getAllPlanDetails(int $partnerId)
    {
        return SubscriptionPartnersPlans::query()
            ->join(Tables::SUBSCRIPTION_PLANS,Tables::SUBSCRIPTION_PLANS.'.id', '=',Tables::SUBSCRIPTION_PARTNERS_PLANS.'.plan_id')
            ->where(Tables::SUBSCRIPTION_PARTNERS_PLANS.'.partner_id','=',$partnerId)
            ->get();
    }

    /**
     * @param int $partnerId
     * @param int $duration
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getPlanDetailsByDuration(int $partnerId,int $duration)
    {
        return SubscriptionPartnersPlans::query()
            ->selectRaw('subscription_partners_plans.*,subscription_plans.title,subscription_plans.price,subscription_plans.start_date,subscription_plans.duration')
            ->join(Tables::SUBSCRIPTION_PLANS,Tables::SUBSCRIPTION_PLANS.'.id', '=',Tables::SUBSCRIPTION_PARTNERS_PLANS.'.plan_id')
            ->where(Tables::SUBSCRIPTION_PARTNERS_PLANS.'.partner_id','=',$partnerId)
            ->where(Tables::SUBSCRIPTION_PLANS.'.duration','=',$duration)
            ->get();
    }

    /**
     * @param int $partnerId
     * @param int $planId
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getPlanDetailsByPlanId(int $partnerId,int $planId)
    {
        return SubscriptionPartnersPlans::query()
            ->selectRaw('subscription_partners_plans.*,subscription_plans.title,subscription_plans.price,subscription_plans.start_date,subscription_plans.duration')
            ->join(Tables::SUBSCRIPTION_PLANS,Tables::SUBSCRIPTION_PLANS.'.id', '=',Tables::SUBSCRIPTION_PARTNERS_PLANS.'.plan_id')
            ->where(Tables::SUBSCRIPTION_PARTNERS_PLANS.'.partner_id','=',$partnerId)
            ->where(Tables::SUBSCRIPTION_PARTNERS_PLANS.'.plan_id','=',$planId)
            ->get();
    }

    /**
     * @param int $partnerPlanId
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getPlanDetailsByPartnerPlanId(int $partnerPlanId)
    {
        return SubscriptionPartnersPlans::query()
            ->selectRaw('subscription_partners_plans.*,subscription_plans.title,subscription_plans.price,subscription_plans.start_date,subscription_plans.duration')
            ->join(Tables::SUBSCRIPTION_PLANS,Tables::SUBSCRIPTION_PLANS.'.id', '=',Tables::SUBSCRIPTION_PARTNERS_PLANS.'.plan_id')
            ->where(Tables::SUBSCRIPTION_PARTNERS_PLANS.'.id','=',$partnerPlanId)
            ->get();
    }
}
