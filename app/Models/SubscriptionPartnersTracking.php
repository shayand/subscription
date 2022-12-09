<?php

namespace App\Models;

use App\Constants\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPartnersTracking extends Model
{
    use HasFactory,SoftDeletes;

    const unTrackedState = 0;
    protected $table = Tables::SUBSCRIPTION_PARTNERS_TRACKING;

    protected $fillable = [
        'partner_id',
        'tracking_uid',
        'phone',
        'is_delivered_fdb',
        'is_fdb_processed',
        'is_check_status',
    ];

    public static function get_unprocessed_entities () {
        return SubscriptionPartnersTracking::query()
            ->where("is_delivered_fdb", '=', self::unTrackedState)
            ->orWhere("is_fdb_processed", '=', self::unTrackedState)
            ->get();
    }

    public static function get_group_by_phone()
    {
        return SubscriptionPartnersTracking::query()
            ->selectRaw('COUNT(id) as total,phone')
            ->groupByRaw('phone')
            ->havingRaw('total > 1')
            ->orderBy('total','desc')
            ->get();
    }

    /**
     * @param int $partner_plan_id
     * @param int $user_id
     * @return bool
     */
    public static function check_whether_same_user_plan_on_a_minute($partner_plan_id,$phone)
    {
        $total =  SubscriptionPartnersTracking::query()
            ->where('partner_plan_id','=',$partner_plan_id)
            ->where('phone','=',$phone)
            ->whereRaw('created_at >= DATE_SUB(NOW(),INTERVAL 1 MINUTE)')
            ->get();
        if (count($total) > 0){
            return true;
        }else{
            return false;
        }
    }
}
