<?php

namespace App\Models;

use App\Constants\Tables;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class SubscriptionShares extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'subscription_user_id',
        'subscription_entity_id',
        'on_the_table',
        'total_duration',
        'publisher_share_amount',
        'total_calculated_amount',
        'publisher_previous_paid_share_amount',
        'valid_to_pay',
        'read_percent',
        'subscription_settlement_id',
        'book_share',
        'fidibo_static_share',
        'fidibo_dynamic_share',
        'publisher_market_share'
    ];

    protected $appends = [
        'shamsi_created_at',
    ];

    public function getShamsiCreatedAtAttribute() {
        return Jalalian::forge($this->created_at)->format('Y-m-d H:i:s');
    }

    public static function CalculatePreviousPaidPublisherShareAmount(int $subscriptionUserID , int $planID, int $entityID)
    {
        $previousPaid = 0;
        $previousPaidShares = SubscriptionShareItems::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id')
//            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
            ->where([
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id' => $entityID,
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id' => $planID,
                Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_user_id' => $subscriptionUserID
            ])->get();//->orderBy(Tables::SUBSCRIPTION_SHARES.'.created_at', 'DESC')->get();

        if (count($previousPaidShares) > 0) {
//            return $previousPaidShares[0]->publisher_previous_paid_share_amount;
            $previousPaidSharesArr = $previousPaidShares->toArray();
            foreach ($previousPaidSharesArr as $previousPaidShare) {
                try {
                    $subscriptionShare = SubscriptionShares::query()->findOrFail($previousPaidShare['subscription_share_id']);
                    $previousPaid += $subscriptionShare['publisher_share_amount'];
                } catch (\Exception $err) {
                    print_r("\n".$err->getMessage()."\n");
                    // TODO: Test if it happens or not.
                    continue;
                }
            }

        }
        return $previousPaid;
    }

    public static function total_report (Builder $query) {
        $lastMonthQuery = clone($query);

        $entityQuery = clone($lastMonthQuery);
        $entityQuery = $entityQuery//->groupBy(Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->select([DB::raw("COUNT(DISTINCT ".Tables::SUBSCRIPTION_ENTITIES.'.id'.") as entity_numbers")])
            ->get()->toArray();

        $userQuery = clone($lastMonthQuery);
        $userQuery = $userQuery//->groupBy(Tables::SUBSCRIPTION_USERS.'.id')
            ->select([DB::raw("COUNT(DISTINCT ".Tables::SUBSCRIPTION_USERS.'.id'.") as user_numbers")])
            ->get()->toArray();
        $earning = clone($lastMonthQuery);
        $earning = $earning->selectRaw("SUM(".Tables::SUBSCRIPTION_SHARES.".publisher_share_amount) as earning")->get()->toArray();

        return [
            'user_numbers' => $userQuery[0]['user_numbers'],
            'entity_numbers' => $entityQuery[0]['entity_numbers'],
            'publisher_earning' => (int)$earning[0]['earning'],
        ];
    }

    public static function getPublisherThisMonthEarning($thisMonth, $publisherPlanEntityIds) {
        $earning = SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
            ->whereDate(Tables::SUBSCRIPTION_SHARES.'.created_at', '>', $thisMonth)
            ->selectRaw("SUM(".Tables::SUBSCRIPTION_SHARES.".publisher_share_amount) as earning")
            ->get()->toArray()[0]['earning'];
        return  $earning != null ? $earning: 0;
    }

    public static function buildPublisherChartQuery($publisherPlanEntityIds, $filter) {

        if ($filter != null and isset($filter['dateMin'])) {
            $thisMonth = Carbon::parse($filter['dateMin']);

        } else {
            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon();
        }

        if ($filter != null and isset($filter['dateMax'])) {
            $endDay = Carbon::parse($filter['dateMax'])->addDay();//Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::forge(Carbon::parse($filter['dateMax'])))->toCarbon();
        } else {
            $endDay = Carbon::now();
        }

        // dd($thisMonth->toDateTimeLocalString(), $endDay->toDateTimeLocalString());
        $query = SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
            ->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '>=', $thisMonth->toDateTimeLocalString())
            ->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $endDay->toDateTimeLocalString())
//            ->whereBetween(Tables::SUBSCRIPTION_SHARES.'.created_at', [$thisMonth->toDateTimeLocalString(), $endDay->toDateTimeLocalString()])
            ->selectRaw(
                "Date(".Tables::SUBSCRIPTION_SHARES.".created_at) as date, SUM(".Tables::SUBSCRIPTION_SHARES.".publisher_share_amount) as earning",
            )
            ->groupByRaw("Date(".Tables::SUBSCRIPTION_SHARES.".created_at)");

        if (isset($filter['title'])) {
            try {
                $guzzle = new Client();
                $response = $guzzle->post('https://papi.fidibo.com/get/book/by/name',[
                    'json' => [ 'book_title' =>  $filter['title'], 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);

                if ($response->getStatusCode() == 404) {
                    $query->whereIn(Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id', []);
                } else {
                    $entityResult = json_decode($response->getBody()->getContents(),true);
                    $IDs = [];
                    foreach ($entityResult as $item) {
                        array_push($IDs, $item['book_id']);
                    }
                    $query->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id')
                        ->where(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', '=', $IDs);
                }
            } catch (\Exception $err) {
                $query->whereIn(Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id', []);
            }
        }
        if (isset($filter['plan_name_filter'])) {
            $query->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id')
                ->join(Tables::SUBSCRIPTION_PLANS, Tables::SUBSCRIPTION_PLANS.'id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
                ->where(Tables::SUBSCRIPTION_PLANS.'.title', '=', $filter['plan_name_filter']);
        }
        return $query;
    }

    public static function buildThisMonthPublisherDashboardChartData($publisherPlanEntityIds, $filter = null) {

        if ($filter != null and isset($filter['dateMin'])) {
            $thisMonth = Carbon::parse($filter['dateMin']);

        } else {
            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon();
        }
        if ($filter != null and isset($filter['dateMax'])) {
            $endDay = Carbon::parse($filter['dateMax']);//Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::forge(Carbon::parse($filter['dateMax'])))->toCarbon();
        } else {
            $endDay = Carbon::now();
        }

        $startDay = clone($thisMonth);
        if ($filter != null) {
            $daysNum = $thisMonth->diffInDays($endDay)+1;
        } else {
            $daysNum = Jalalian::now()->getMonthDays();
        }

        $itemsPublisherShares = self::buildPublisherChartQuery($publisherPlanEntityIds, $filter)->get()->toArray();

        $key = "سهم ناشر در این ماه (فیدی پلاس)";
        $today = Carbon::now();

        $result = [];
        for($i = 1; $i <= $daysNum; $i++) {
            $day = clone($startDay);
            $monthName = Jalalian::forge($day)->format("%B %d");
            if ($day->diffInMonths($today) == 0 and $day->diffInDays($today) == 0 and $day->day == $today->day) {
                $todayData = ['key' => $key, 'x' => sprintf("%s", $monthName), 'y' => 0];
                foreach ($itemsPublisherShares as $itemsPublisherShare) {
                    $theDay = Carbon::parse($itemsPublisherShare['date']);
                    if ( $theDay->day == $day->day && $theDay->month == $day->month) {
                        $todayData['y'] = (int)$itemsPublisherShare['earning'] ;
                    }
                }
                array_push($result, $todayData);
                break;
            }
            else{
                $todayData = ['key' => $key, 'x' => sprintf("%s", $monthName), 'y' => 0];
//                if ($monthName == "دی 10") {
//                    dd($day, $itemsPublisherShares);
//                }
                foreach ($itemsPublisherShares as $itemsPublisherShare) {
                    $theDay = Carbon::parse($itemsPublisherShare['date']);
//                    if ($monthName == "دی 10") {
//                        dd($day, $theDay, $itemsPublisherShare['date'], $theDay->day, $day->day, $theDay->month, $day->month);
//                    }
                    if ( $theDay->day == $day->day && $theDay->month == $day->month) {
                        $todayData['y'] = (int)$itemsPublisherShare['earning'] ;
                    }
                }
                array_push($result, $todayData);
            }
            $startDay->addDay();
        }
        return $result;
    }

    public static function buildPublisherDashboardChartData($publisherPlanEntityIds) {
        $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon();//->format("Y-m-d 00:00:00");
        $startDay = clone($thisMonth);
        $daysNum = Jalalian::now()->getMonthDays();
        $endDay = Carbon::now();
        $itemsPublisherShares = SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
            ->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '>=', $thisMonth->toDateTimeLocalString())
            ->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $endDay->toDateTimeLocalString())
//            ->whereBetween(Tables::SUBSCRIPTION_SHARES.'.created_at', [$thisMonth->toDateTimeLocalString(), $endDay->toDateTimeLocalString()])
            ->selectRaw(
                "Date(".Tables::SUBSCRIPTION_SHARES.".created_at) as date, SUM(".Tables::SUBSCRIPTION_SHARES.".publisher_share_amount) as earning",
            )
            ->groupByRaw("Date(".Tables::SUBSCRIPTION_SHARES.".created_at)")->get()->toArray();

        $key = "فیدی پلاس";
        $monthName = Jalalian::now()->format("%B");
        $today = Carbon::now()->format("Y-m-d 00:00:00");

        $result = [];
        for($i = 1; $i <= $daysNum; $i++) {
            $day = clone($startDay);

            if ($day->format("Y-m-d 00:00:00") != $today) {
                foreach ($itemsPublisherShares as $itemsPublisherShare) {
                    $theDay = Carbon::parse($itemsPublisherShare['date'])->format('Y-m-d 00:00:00');
                    if ($day == $theDay) {
                        $todayData = ['key' => $key, 'x' => sprintf("%d %s", $i, $monthName), 'y' => 0];
                        $todayData['y'] = (int)$itemsPublisherShare['earning'] ;
                        array_push($result, $todayData);
                    }
                }
            }
            else{
                foreach ($itemsPublisherShares as $itemsPublisherShare) {
                    $theDay = Carbon::parse($itemsPublisherShare['date'])->format('Y-m-d 00:00:00');
                    if ($day == $theDay) {
                        $todayData = ['key' => $key, 'x' => sprintf("%d %s", $i, $monthName), 'y' => 0];
                        $todayData['y'] = (int)$itemsPublisherShare['earning'] ;
                        array_push($result, $todayData);
                    }
                }
                break;
            }
            $startDay->addDay();
        }
        return $result;
    }

}
