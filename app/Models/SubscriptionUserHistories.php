<?php

namespace App\Models;

use App\Constants\Tables;
use App\Helpers\Helper;
use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\SoftDeletes;
use phpDocumentor\Reflection\Types\This;

class SubscriptionUserHistories extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = Tables::SUBSCRIPTION_USER_HISTORIES;

    protected $casts = [
        'subscription_entity_details' => 'array',
        'read_percent_start' => 'int',
        'read_percent_end' => 'int'
    ];

    protected $appends = [
        'shamsi_created_at',
        'shamsi_deleted_at',
        'time_on_the_table'
    ];

    protected $fillable = [
        'subscription_user_id',
        'subscription_plan_entity_id',
        'entity_id',
        'start_date',
        'end_date',
        'read_percent_start',
        'read_percent_end',
        'is_hide_from_list',
        'is_logged',
        'subscription_entity_details',
        'operator_id'
    ];

    public function getShamsiCreatedAtAttribute()
    {
        if ($this->start_date == '' || $this->start_date == null) {
            $this->start_date = $this->created_at;
        }
        return Jalalian::forge($this->start_date)->format('H:i:s Y-m-d');
    }

    public function getTimeOnTheTableAttribute() {
        $start_time = Carbon::parse($this->start_date);
        if ($this->end_date != null) {
            $finish_time = Carbon::parse($this->end_date);
        } else {
            $finish_time = Carbon::now();
        }
        return date_diff($start_time, $finish_time);
    }

    public function getShamsiDeletedAtAttribute()
    {
        return Jalalian::forge($this->end_date)->format('H:i:s Y-m-d');
    }

    /**
     * @param int $userId
     * @param int $planEntityId
     * @return array
     */
    public static function countUserPlanCapacities(int $userId, int $planEntityId)
    {
        return SubscriptionUserHistories::where(['user_id' => $userId,'plan_id' => $planEntityId])
            ->get();
    }

    public static function getUserPlanHistory($userId, $planId) {
        return SubscriptionUserHistories::query()->where([
                Tables::SUBSCRIPTION_USERS.'.id' => $userId,
                Tables::SUBSCRIPTION_USERS.'.plan_id' => $planId
        ])
        ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id')
        ->get();
    }

    public static function getUserPlanEntitiesHistory(int $userId ,int $planId, $settlementStart, $settlementEnd)
    {
        $histories =  DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES,Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id','=',Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
            ->select([
                Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id',
                Tables::SUBSCRIPTION_USER_HISTORIES.'.start_date',
                Tables::SUBSCRIPTION_USER_HISTORIES.'.end_date'
            ])
            ->where([Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userId, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id'=>$planId])
            ->where([Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userId, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id'=>$planId])
            ->distinct('subscription_plan_entity_id')->get()->toArray();//->pluck("subscription_plan_entity_id");

        $results = [];
        foreach ($histories as $history) {
            if (strtotime($history->start_date) >= strtotime($settlementEnd) || strtotime($history->end_date) <= strtotime($settlementStart)) {
//                dd($userId, $history->start_date, $history->end_date, $settlementStart, $settlementEnd);
                continue;
            }
            $results[] = $history->subscription_plan_entity_id;
        }
        return array_unique($results);
    }

    public static function getUserEntitiesHistory(int $userPlanId)
    {
        return DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
            ->select(Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
            ->where([Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userPlanId])
            ->distinct('entity_id')
            ->pluck('entity_id')
            ->all();
    }

    public static function getEntityAndEntityHistories(int $userId , int $planEntityId)
    {
        $entity_histories = SubscriptionUserHistories::query()->where([
            Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userId,
            Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id' => $planEntityId
        ])->get()->toArray();

        if(count($entity_histories) > 0){
            return ['entity' => $entity_histories[0]['subscription_entity_details'], 'entity_histories' => $entity_histories];
        }else{
            return ['entity' => [], 'entity_histories' => []];
        }
    }

    public static function getUserPlanEntityHistories(int $userId , int $planId, int $entityId)
    {
        $entity_histories = SubscriptionUserHistories::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id')
            ->where([
                Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userId,
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id' => $planId,
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id' => $entityId
            ])->get()->toArray();

        return ['entity_histories' => $entity_histories];
    }

    public static function isEntityValidForPublisherShare($histories, $entity) {
        $starts = [];
        $ends = [];
        foreach ($histories as $history) {
//            dd($history, $entity);
            if ($history['entity_id'] == $entity['entity']['entity_id']) {
                continue;
            }

            $starts[] =  $history['read_percent_start'];
            $ends[] = $history['read_percent_end'];
        }
        if (count($starts) > 1) {
            $start = max($starts);
        } else if (count($starts) == 1){
            $start = $starts[0];
        } else {
            $start = 0;
        }

        if (count($ends) > 1) {
            $end = max($ends);
        } else if (count($ends) == 1) {
            $end = $ends[0];
        } else {
            $end = 0;
        }
        $read_percentage = max($end, $start);
        $read_percentage_sum = abs($end - $start);
        if (/*$read_percentage_sum*$entity->page_count > 20 ||*/ $read_percentage >= 10) {
            return [1, $read_percentage];
        }
        return [0, $read_percentage];
    }

    public static function calculateTimeEntityWasOnTheTable($histories, $currentSettlement, $now)
    {
        $openTime = 0;
//        print_r($currentSettlement);
        foreach ($histories as $history) {
            try {
                $result = Helper::calculate_valid_start_end_datetime($currentSettlement, $now, $history['start_date'], $history['end_date']);
                $minute = Helper::datetime_diff_in_minute($result['start'], $result['end']);
//                dd($history['entity_id'], $now, $history['start_date'], $history['end_date'], $result, $minute);
//                print_r($history['entity_id']);
//                print_r("\n");
//                print_r($now);
//                print_r("\n");
//                print_r($history['start_date']);
//                print_r("\n");
//                print_r($history['end_date']);
//                print_r("\n");
//                print_r($result);
//                print_r("\n");
//                print_r($minute);
//                dd($result['start'], $result['end']);
                //print_r( sprintf("\n%s::%s::%d\n", $result['start']->format('Y-m-d H:i:s'), $result['end']->format('Y-m-d H:i:s'), $history['entity_id']));
                if ($minute == -1) {
                    return -1;
                }
                $openTime+= $minute;

            } catch (\Exception $e) {
                print_r($e->getMessage());
                return -1;
            }
        }
        // if($history['entity_id'] == 92139) {
        //     dd($history['entity_id'], $openTime);
        // }
//        dd($openTime);
//        print_r("*************");
        return $openTime;
    }

    public static function getEntityNamesByPlanEntities($planEntityIds, $entityName = null) {
        try {
            $histories = SubscriptionUserHistories::query()->whereIn('subscription_plan_entity_id', $planEntityIds)
                ->select(DB::raw('DISTINCT subscription_plan_entity_id, subscription_entity_details'));
            if (isset($entityName)) {
                $histories = $histories->whereRaw("JSON_EXTRACT(subscription_entity_details, '$.title') LIKE CONCAT('%','$entityName','%')");
            }

            $histories = $histories->get();
            $mapPlanEntity_EntityName = [];
            $has_entity = false;
            foreach ($histories as $history) {
                $mapPlanEntity_EntityName[$history->subscription_plan_entity_id] = [
                    'title' => $history->subscription_entity_details['title'],
                    'price' => $history->subscription_entity_details['price']
                ];
                $has_entity = true;
            }

            if (!$has_entity) {
                throw new \Exception('Something went wrong');
            } else {
                return $mapPlanEntity_EntityName;
            }
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public static function get_crm_user_plan_contents($plan) {
        $query = SubscriptionUserHistories::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id')
            ->where([
                Tables::SUBSCRIPTION_USER_HISTORIES. '.subscription_user_id' => $plan->id,
                Tables::SUBSCRIPTION_PLAN_ENTITIES. '.plan_id' => $plan->plan_id
            ])->groupBy(Tables::SUBSCRIPTION_USER_HISTORIES. '.entity_id');

        $totalQuery = clone($query);
        $total = count($totalQuery->select([Tables::SUBSCRIPTION_USER_HISTORIES. '.entity_id'])->get());

        $planContentsQuery = $query
            ->select([
                Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id',
                DB::raw("JSON_EXTRACT(".Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_entity_details'.", '$.title') as title"),
                DB::raw('MAX(read_percent_start) as max_read_start, MAX(read_percent_end) as max_read_end'),
//                DB::raw('MIN(read_percent_start) as min_read_start, MIN(read_percent_end) as min_read_end'),
                DB::raw('MIN('.Tables::SUBSCRIPTION_USER_HISTORIES.'.created_at) as created_at'),
//                DB::raw('MAX('.Tables::SUBSCRIPTION_USER_HISTORIES.'.deleted_at) as deleted_at'), // TODO add safe delete inside the table
            ])
            ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES.'.entity_id','=',Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
            ->groupBy(DB::raw("JSON_EXTRACT(".Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_entity_details'.", '$.title')")) // TODO , Tables::SUBSCRIPTION_USER_HISTORIES.'.deleted_at');
            ->orderBy('created_at','DESC');

        return ['total' => $total, 'contents_query' => $planContentsQuery];
    }

    public static function get_crm_user_plan_histories($plan) {
        $entitiesHistoriesQuery = SubscriptionUserHistories::query()
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id', '=', $plan->id)
            ->select([
                Tables::SUBSCRIPTION_USER_HISTORIES.'.*'
            ])->orderBy(Tables::SUBSCRIPTION_USER_HISTORIES. '.created_at','DESC');
//            ->selectRaw("Abs(read_percent_start - read_percent_end) as read_percent")

        $totalQuery = clone($entitiesHistoriesQuery);
        $total = $totalQuery->count();
        return ['total' => $total, 'history_query' => $entitiesHistoriesQuery];
    }

    public static function getThisMonthDownloaded($thisMonth, $publisherPlanEntityIds) {
        return SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id', '=', Tables::SUBSCRIPTION_SHARES.'.id')
            ->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '>=', $thisMonth)
            ->where(Tables::SUBSCRIPTION_SHARES.'.publisher_share_amount', '!=', 0)
            ->selectRaw("COUNT(DISTINCT ".Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id'.") as entity_numbers")
            ->get()->toArray()[0]['entity_numbers'];
    }

    public static function buildThisMonthDownloadedChartData($publisherPlanEntityIds) {
        $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon();
        $startDay = clone($thisMonth);
        $daysNum = Jalalian::now()->getMonthDays();
        $endDay = Carbon::now();
        $downloadedBooks = SubscriptionUserHistories::query()->whereIn(Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id', $publisherPlanEntityIds)
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.start_date', '>=', $thisMonth)
//            ->whereBetween(Tables::SUBSCRIPTION_USER_HISTORIES.'.start_date', [$startDay, $endDay])
            ->selectRaw("Date(".Tables::SUBSCRIPTION_USER_HISTORIES.".start_date) as date, COUNT(DISTINCT ".Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id'.") as user_numbers")
            ->groupByRaw("Date(".Tables::SUBSCRIPTION_USER_HISTORIES.".start_date)")->get()->toArray();

        $key = "دریافت شده از فیدی پلاس این ماه";
        $monthName = Jalalian::now()->format("%B");
        $today = Carbon::now()->format("Y-m-d 00:00:00");

        $result = [];
        for($i = 1; $i <= $daysNum; $i++) {
            $day = clone($startDay);

            if ($day->format("Y-m-d 00:00:00") != $today) {
                $todayData = ['key' => $key, 'x' => sprintf("%d %s", $i, $monthName), 'y' => 0];
                foreach ($downloadedBooks as $downloadedBook) {
                    $theDay = Carbon::parse($downloadedBook['date'])->format('Y-m-d 00:00:00');
                    if ($day == $theDay) {
                        $todayData['y'] = $downloadedBook['user_numbers'] ;
                    }
                }
                array_push($result, $todayData);
            }
            else{
                break;
            }
            $startDay->addDay();
        }
        return $result;
    }

    /**
     * @param int $subscriptionUserId
     * @param string $contentType
     * @param int $entityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function updateUserEndDates(int $subscriptionUserId, string $contentType, int $entityId)
    {
        $subscriptionUserHistory = SubscriptionUserHistories::query()
            ->where('subscription_user_id','=',$subscriptionUserId);

        if($contentType == 'audio'){
            $subscriptionUserHistory->whereRaw("JSON_EXTRACT(subscription_entity_details, '$.format') = 'AUDIO' AND entity_id != $entityId AND end_date IS NULL");
        }else{
            $subscriptionUserHistory->whereRaw("JSON_EXTRACT(subscription_entity_details, '$.format') != 'AUDIO'AND entity_id != $entityId AND end_date IS NULL");
        }

        $subscriptionUserHistory->update([
            'end_date' => new DateTime()
        ]);

        return $subscriptionUserHistory;
    }

    public static function getUserOnTheTableEntities(int $subscriptionUserID) {
        return SubscriptionUserHistories::query()
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id', '=', $subscriptionUserID)
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.end_date', '=',  null)
            ->get();
    }

    public static function createTestUserHistory($entities, $subscription_user, $settlement, $entities_time_on_table, $set_end_date = true) {
        $end_date = $settlement['settelment_date'];
        $counter = 0;

        $entity_ids = [];
        foreach ($entities as $entity) {
            $entity_ids[] = $entity['entity_id'];
        }

        $response = [];
        if (count($entity_ids) == 0) {
            $settlement_user = clone($subscription_user);
            $settlement_user['subscription_settlement_id'] = $settlement['id'];
            $settlement_user['settlement_duration'] = $settlement['settlement_duration'];
            $settlement_user['had_entity'] = 0;
            $response[] = $settlement_user;
            return $response;
        }
        $papi_entities = Helper::getBooksFromPapi($entity_ids);

        $histories = [];
        foreach ($entities as $entity) {
            $time_diff = $entities_time_on_table[$counter];
            $settlement_user = clone($subscription_user);

            $totalEnd = new \DateTime($end_date, new \DateTimeZone('Asia/Tehran'));
            $totalStart = clone($totalEnd);
            $totalStart->sub( new \DateInterval( sprintf("PT%dM", $time_diff) ) );


            $entity_info = $papi_entities[$entity['entity_id']];
            $entityType = ($entity_info['format'] == 'AUDIO') ? 'audio' : 'book';
            $entity_info['price_factor'] = $entity['price_factor'];
            $entity_info['price'] = intval($entity_info['price']);
            $entity_info['publisher_share'] = floatval($entity_info['publisher_marketshare']);
            $entity_info['entity_id'] = $entity['entity_id'];
            $entity_info['id'] = $entity['id'];

            $arr = [ ['valid_to_pay', 15], ['invalid_to_pay', 5] ];
            $isValid = $arr[array_rand($arr)];

            $histories[] = SubscriptionUserHistories::create([
                'subscription_user_id' => $subscription_user['id'],
                'entity_id' => $entity['entity_id'],
                'subscription_plan_entity_id' => $entity['plan_entity_id'],
                'start_date' => $totalStart,
                'end_date' => $totalEnd,
                'read_percent_start' => 0,
                'read_percent_end' => $isValid[1],
                'subscription_entity_details' => $entity_info,
            ]);

            $settlement_user['subscription_user_id'] = $subscription_user['id'];
            $settlement_user['subscription_entity_id'] = $entity['id'];
            $settlement_user['subscription_settlement_id'] = $settlement['id'];
            $settlement_user['on_the_table_time'] = $time_diff;
            $settlement_user['valid_to_pay'] = $isValid[0];
            $settlement_user['settlement_duration'] = $settlement['settlement_duration'];
            $settlement_user['had_entity'] = 1;
            $response[] = $settlement_user;

            $end_date = $totalStart->format("Y-m-d H:i:s");
            $counter++;
        }

        return $response;
    }
}
