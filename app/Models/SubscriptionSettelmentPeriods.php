<?php

namespace App\Models;

use App\Constants\Tables;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SubscriptionSettelmentPeriods extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'subscription_user_id',
        'settlement_duration',
        'settelment_date',
        'is_settled',
        'had_entity'
    ];

    public function subscription()
    {
        return $this->hasOne('App\Models\SubscriptionUsers');
    }

    public static function getLatestSettlements($startedAt)
    {
        $settlementsQuery = SubscriptionSettelmentPeriods::query()
            ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.subscription_user_id')
            ->whereRaw("settelment_date <= DATE_FORMAT('".$startedAt."', '%Y-%m-%d %T')")
            //->whereRaw('start_date <= CONVERT_TZ(NOW(), "UTC", "Asia/Tehran")')
            // ->whereRaw('subscription_users.start_date <= NOW()')
            //->whereRaw('DATE_ADD( start_date, INTERVAL duration DAY) >= CONVERT_TZ(NOW(), "UTC", "Asia/Tehran")')
            // ->whereRaw('DATE_ADD( subscription_users.start_date, INTERVAL subscription_users.duration DAY) >= NOW()')
            ->where('is_settled', '=', 0)
            ->select([
                Tables::SUBSCRIPTION_USERS.'.*',
                Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.*',
            ]);
        // dd($settlementsQuery->get());
        return [
            'settlements' => $settlementsQuery->get(),
            'settlement_user_IDs' => $settlementsQuery->pluck("subscription_user_id")
        ];
    }

    public static function calculate_settlement_start_date($settlement) {
        $totalEnd = new \DateTime($settlement['settelment_date'], new \DateTimeZone('Asia/Tehran'));
        $totalStart = clone($totalEnd);
        $totalStart->sub( new \DateInterval( sprintf("P%dD", $settlement['settlement_duration']) ) );
        return [$totalStart, $totalEnd];
    }
}
