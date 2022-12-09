<?php

namespace App\Models;

use App\Constants\Tables;
use Bschmitt\Amqp\Table;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class SubscriptionUsers extends Model
{
    use HasFactory,SoftDeletes, HasEvents;

    protected const USER_PLAN_ACTIVE_STATUS = 1;
    protected const USER_PLAN_PASSED_STATUS = 0;

    protected $casts = [
        'plan_details' => 'array'
    ];

    protected $fillable = [
        'user_id',
        'start_date',
        'duration',
        'plan_details',
        'plan_id',
        'operator_id',
        'update_reason',
        'subscription_assignment_id',
        'is_crm',
        'subscription_payment_id',
        'init_duration',
        'init_start_date'
    ];

    protected $appends = [
        'remain_audios',
        'remain_books',
        'remain_entities',
        'remain_days',
        'remain_days_active',
        'remain_days_reserve',
        'remain_entities_reserve',
        'shamsi_start_date',
        'shamsi_end_date',
        'user_plan_status'
    ];

    public $trackingId = null;

    public function getRemainBooksAttribute()
    {
        $usedBookCapacity = SubscriptionUserHistories::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id')
            ->whereRaw("JSON_EXTRACT(subscription_entity_details, '$.format') != 'AUDIO'")
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id', '=', $this->id)
            ->where(Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id', '=', $this->plan_id)
            ->distinct(Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')->count();

        if (gettype($this->plan_details) == 'string') {
            $planDetails = json_decode($this->plan_details);
        } else if (gettype($this->plan_details) == 'array') {
            $planDetails = $this['plan_details'];
            return $planDetails['max_books'] - $usedBookCapacity;
        } else {
            $planDetails = $this->plan_details;
        }
        $bookCapacity = $planDetails->max_books - $usedBookCapacity;
        if ($bookCapacity < 0) {
            throw new \Exception("Book remain capacity could not be less than 0!");
        }

        return $bookCapacity;
    }

    public function getRemainAudiosAttribute()
    {
        $usedAudioCapacity = SubscriptionUserHistories::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_plan_entity_id')
            ->whereRaw("JSON_EXTRACT(subscription_entity_details, '$.format') = 'AUDIO'")
            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id', '=', $this->id)
            ->where(Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id', '=', $this->plan_id)
            ->distinct(Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')->count();

        if (gettype($this->plan_details) == 'string') {
            $planDetails = json_decode($this->plan_details);
        } else if (gettype($this->plan_details) == 'array') {
            $planDetails = $this['plan_details'];
            return $planDetails['max_audios'] - $usedAudioCapacity;
        } else {
            $planDetails = $this->plan_details;
        }

        $audioCapacity = $planDetails->max_audios - $usedAudioCapacity;
        if ($audioCapacity < 0) {
            throw new \Exception("Audio remain capacity could not be less than 0!");
        }

        return $audioCapacity;
    }

    public function getRemainEntitiesAttribute()
    {
        return $this->getRemainAudiosAttribute() + $this->getRemainBooksAttribute();
    }

    public function getRemainDaysAttribute($onlyFuture = false)
    {
        $now = Carbon::now();
        $start_date = Carbon::parse($this->start_date);
        $end_date = Carbon::parse($this->start_date)->addMinutes($this->duration);

        if ($start_date > $now) {
            $duration = date_diff($end_date, $start_date);
        }
        else {
            $duration = date_diff($end_date, $now);
        }

        if ($end_date < $now) {
            return 0;
        }
        $remainDays = $duration->days;

        if ($onlyFuture){
            $futureDays = 0;
            $tmp_date = clone($end_date);
            $futurePlans = self::getFuturePlans($this->user_id, $tmp_date->subDay());
            foreach ($futurePlans as $futurePlan) {
                $futureDays+=$futurePlan->duration / (24 * 60);
            }

            return $futureDays;
        }

        if ($duration->h != 0 || $duration->i) {
            $remainDays+=1;
        }

        return $remainDays;
    }

    public function getRemainDaysActiveAttribute()
    {
        $now = Carbon::now();
        $start_date = Carbon::parse($this->start_date);
        $end_date = Carbon::parse($this->start_date)->addMinutes($this->duration);

        if ($start_date > $now) {
            $duration = date_diff($end_date, $start_date);
        }
        else {
            $duration = date_diff($end_date, $now);
        }

        if ($end_date < $now) {
            return 0;
        }
        $remainDays = $duration->days;

        if ($duration->h != 0 || $duration->i) {
            $remainDays+=1;
        }

        return $remainDays;
    }

    public function getRemainDaysReserveAttribute()
    {
        $start_date = Carbon::parse($this->start_date);
        $end_date = Carbon::parse($start_date)->addMinutes($this->duration-1);
        $futurePlans = self::getFuturePlans($this->user_id, $end_date);

        $remainDays = 0;
        foreach ($futurePlans as $futurePlan) {
            $remainDays+=$futurePlan->duration;
        }

        return $remainDays;
    }

    public function getShamsiStartDateAttribute() {
        return Jalalian::forge($this->start_date)->format('H:i:s Y-m-d');
    }

    public function getShamsiEndDateAttribute() {
        $end_date = Carbon::parse($this->start_date)->addMinutes($this->duration);
        return Jalalian::forge($end_date)->format('H:i:s Y-m-d ');
    }

    public function getUserPlanStatusAttribute() {
        $startDate = Carbon::parse($this->start_date);
        $now = Carbon::now();
        $endDate = Carbon::parse($this->start_date)->addMinutes($this->duration);
        if ($endDate < $now) {
            return 'غیرفعال';
        } else if ($now->lt($startDate)) {
            return 'رزرو';
        }
        return 'فعال';
    }

    public function plan()
    {
        return $this->hasOne('App\Models\SubscriptionPlans');
    }

    /**
     * The plans that belong to the entities
     * @return HasOne
     */
    public function active_plan()
    {
        return $this->hasOne(SubscriptionPlans::class, 'id', 'plan_id');
    }

    public static function getActivePlan (int $userId) {
        //@TODO: attention this query is runs on mysql (master | production) and mariadb (stage | develop | pilot)
        return SubscriptionUsers::query()
            ->where('user_id', '=', $userId)
            ->where('start_date','<=',Carbon::now()->toDateTimeString())
            // ->whereRaw('start_date <= NOW()')
            ->whereRaw(sprintf('DATE_ADD( start_date, INTERVAL duration MINUTE) >= "%s"',Carbon::now()->toDateTimeString()))
            // ->whereRaw('DATE_ADD( start_date, INTERVAL duration DAY) >= NOW()')
            ->get();
    }

    public static function getLastPlan (int $userId) {
        return SubscriptionUsers::query()
            ->where('user_id', '=', $userId)
            ->orderBy("start_date", "DESC")
            ->first();
    }

    public static function getPassedPlans (int $userId) {
        return SubscriptionUsers::query()
            ->where('user_id', '=', $userId)
            ->whereRaw(sprintf('"%s" > DATE_ADD( start_date, INTERVAL duration MINUTE)',Carbon::now()->toDateTimeString()))
            ->get();
    }

    public static function getPassedPlan (int $userId, int $planId) {
        return SubscriptionUsers::query()
            ->where('user_id', '=', $userId)
            ->where('plan_id', '=', $planId)
            ->whereRaw(sprintf('"%s" > DATE_ADD( start_date, INTERVAL duration MINUTE)',Carbon::now()->toDateTimeString()))
            ->get();
    }

    public static function getFuturePlans (int $userId, $date = null) {
        $query = SubscriptionUsers::query()->where('user_id', '=', $userId)
            ->whereRaw(sprintf('"%s" < start_date',Carbon::now()->toDateTimeString()));
        $query->whereRaw(sprintf('"%s" < DATE_ADD( start_date, INTERVAL duration MINUTE)',Carbon::now()->toDateTimeString()));
        return $query->orderBy('start_date', 'asc')->get();
    }

    public static function getSubscriptionUsersByID($subscription_users_IDs)
    {
        return SubscriptionUsers::query()
            ->join(Tables::SUBSCRIPTION_PAYMENTS, Tables::SUBSCRIPTION_PAYMENTS.'.id', '=', Tables::SUBSCRIPTION_USERS.'.subscription_payment_id')
            ->join(Tables::SUBSCRIPTION_SETTELMENT_PERIODS, Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.subscription_user_id', '=', Tables::SUBSCRIPTION_USERS.'.id')
            ->select([
                Tables::SUBSCRIPTION_USERS.'.*',
                Tables::SUBSCRIPTION_PAYMENTS.'.currency',
                Tables::SUBSCRIPTION_PAYMENTS.'.price',
                Tables::SUBSCRIPTION_PAYMENTS.'.amount',
                Tables::SUBSCRIPTION_PAYMENTS.'.id as payment_id',
                Tables::SUBSCRIPTION_PAYMENTS.'.payment_type',
                Tables::SUBSCRIPTION_PAYMENTS.'.credit_id',
                Tables::SUBSCRIPTION_PAYMENTS.'.discount_price',
                Tables::SUBSCRIPTION_PAYMENTS.'.discount_code',
                Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.id as settlement_id',
                Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.settelment_date',
                Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.settlement_duration',
                Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.created_at',
            ])->whereIn(Tables::SUBSCRIPTION_USERS . '.id', $subscription_users_IDs)
            ->orderBy(Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.created_at')
            ->distinct(Tables::SUBSCRIPTION_USERS.'.id', Tables::SUBSCRIPTION_SETTELMENT_PERIODS.'.id')
            ->get()->toArray();
    }

    /**
     * @param int $userId
     */
    public static function getUserActivePlan(int $userId)
    {
        $text = sprintf('SELECT id,user_id,plan_id,start_date,duration,plan_details,DATE_ADD(start_date,INTERVAL duration DAY) as end_date FROM subscription_users WHERE CONVERT_TZ(UTC_TIMESTAMP(), "UTC", "Asia/Tehran") between start_date and DATE_ADD(start_date,INTERVAL duration MINUTE) AND user_id = %s',$userId);
        return DB::select($text);
    }

    /**
     * @param int $userId
     * @param int $contentId
     * @return \Illuminate\Support\Collection
     */
    public static function checkIsContentAvailableForUser(int $userId,int $contentId)
    {
        return DB::table(Tables::SUBSCRIPTION_USERS)
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES,Tables::SUBSCRIPTION_USERS . '.plan_id','=',Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id')
            ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES . '.id','=',Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
            ->select('subscription_users.*')
            ->where([Tables::SUBSCRIPTION_USERS.'.user_id' => $userId , Tables::SUBSCRIPTION_ENTITIES.'.entity_id'=>$contentId])
            ->get();
    }

    /**
     * @param $userId
     */
    public static function calculateUserCapacity(int $userId){
        $activePlan = self::getUserActivePlan($userId);
        foreach ($activePlan as $singlePlan){
            $activePlanId = $singlePlan->plan_id;
            $json = json_decode($singlePlan->plan_details,true);
            $maxBooks = $json['max_books'];
            $maxAudios = $json['max_audios'];
            $subscriptionUserId = $singlePlan->id;
            $ret = DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
                ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES.'.entity_id','=',Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->select([DB::raw('count(subscription_users_histories.id) as total'),'subscription_entities.entity_type'])
                ->groupBy('subscription_entities.entity_type')
                ->where(Tables::SUBSCRIPTION_USER_HISTORIES. '.subscription_user_id','=', $subscriptionUserId)
                ->get()
            ;
            foreach ($ret as $singleItem){
                if($singleItem->entity_type == 'audio'){
                    $maxAudios -= $singleItem->total;
                }else{
                    $maxBooks -= $singleItem->total;
                }
            }
            return ['books' => $maxBooks,'audios' => $maxAudios];
        }
    }

    /**
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public static function getUserActivePlanContents(int $userId)
    {
        $activePlan = self::getUserActivePlan($userId);
        foreach ($activePlan as $singlePlan) {
            $subscriptionUserId = $singlePlan->id;

            return DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
                ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES.'.entity_id','=',Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->select([DB::raw('subscription_users_histories.entity_id,COUNT(subscription_users_histories.entity_id) AS total')])
                ->where(Tables::SUBSCRIPTION_USER_HISTORIES. '.subscription_user_id','=', $subscriptionUserId)
                ->groupBy(Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->get();
        }
    }

    /**
     * @param int $userId
     * @return \Illuminate\Support\Collection
     */
    public static function checkIfUserHasA(int $userId)
    {
        $activePlan = self::getUserActivePlan($userId);
        foreach ($activePlan as $singlePlan) {
            $subscriptionUserId = $singlePlan->id;

            return DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
                ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES.'.entity_id','=',Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->select([DB::raw('subscription_users_histories.entity_id,COUNT(subscription_users_histories.entity_id) AS total')])
                ->where(Tables::SUBSCRIPTION_USER_HISTORIES. '.subscription_user_id','=', $subscriptionUserId)
                ->groupBy(Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->get();
        }
    }

    /**
     * @param int $userId
     * @return null[]
     */
    public static function getUserLastEntities(int $userId)
    {
        $activePlan = self::getActivePlan($userId);
        foreach ($activePlan as $singlePlan) {
            $subscriptionUserId = $singlePlan->id;

            $final = ['audios' => null,'books' => null];

            $ret = DB::table(Tables::SUBSCRIPTION_USER_HISTORIES)
                ->join(Tables::SUBSCRIPTION_ENTITIES,Tables::SUBSCRIPTION_ENTITIES.'.entity_id','=',Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id')
                ->select([DB::raw('subscription_users_histories.*,subscription_users_histories.entity_id,subscription_entities.entity_type')])
                ->where(Tables::SUBSCRIPTION_USER_HISTORIES. '.subscription_user_id','=', $subscriptionUserId)
                ->orderBy('subscription_users_histories.created_at','desc')
                ->get();

            $allEntities = [];
            foreach ($ret as $singleRet) {
                $allEntities[] = $singleRet->entity_id;
                if ($singleRet->entity_type == 'audio' & $final['audios'] == null){
                    $final['audios'] = $singleRet->entity_id;
                }
                if ($singleRet->entity_type == 'book' & $final['books'] == null){
                    $final['books'] = $singleRet->entity_id;
                }
            }

            $final['all'] = $allEntities;

            return $final;
        }
    }

    public static function applyCRMUserFilters($query, $filters) {
        if ( isset($filters['plan_title_filter']) ) {
            $query = $query->whereRaw("JSON_EXTRACT(plan_details, '$.title') = ".$filters['plan_title_filter']);
        }

        if ( isset($filters['plan_status_filter']) ) {
            if ($filters['plan_status_filter'] == self::USER_PLAN_PASSED_STATUS) {
                $query = $query->whereRaw('CONVERT_TZ(UTC_TIMESTAMP(), "UTC", "Asia/Tehran") > DATE_ADD( start_date, INTERVAL duration MINUTE)');
            } else if ($filters['plan_status_filter'] == self::USER_PLAN_ACTIVE_STATUS) {
                $query = $query->whereRaw('CONVERT_TZ(UTC_TIMESTAMP(), "UTC", "Asia/Tehran") <= DATE_ADD( start_date, INTERVAL duration MINUTE)');
            }
        }

        if ( isset($filters['plan_started_filter']) ) {
            $query = $query->where('start_date', '>=', $filters['plan_started_filter']);
        }

        if ( isset($filters['plan_ends_filter']) ) {
            $query = $query->whereRaw(sprintf("\"%s\"", $filters['plan_ends_filter'])." >= DATE_ADD(start_date, INTERVAL duration MINUTE)");
        }

        if ( isset($filters['user_id_filter']) ) {
            $query = $query->where('user_id', '=', $filters['user_id_filter']);
        }

        if ( isset($filters['user_info_filter']) ) {
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/info',[
                'json' => [ 'user_info' => [ $filters['user_info_filter'] ], 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);

            $entityResult = json_decode($response->getBody()->getContents(),true);
            if (count( $entityResult ) && isset( $entityResult[$filters['user_info_filter']]['user_id'] )) {
                $query = $query->where('user_id', '=', $entityResult[$filters['user_info_filter']]["user_id"]);
            }
        }

        return $query;
    }

    public static function applyPlanTitleFilter($query, $filters) {
        if ( isset($filters['plan_title_filter']) ) {
            $query->whereRaw("JSON_EXTRACT(plan_details, '$.title') LIKE CONCAT('%',".sprintf("'%s'", $filters['plan_title_filter']).", '%')");
        }
        return $query;
    }

    public static function applyUserInfoFilter($user_prefix, $operator_prefix, $query, $filters) {
        $filter = [];
        if ( array_key_exists('user_info_filter', $filters) ) {
            $filter[] = $filters['user_info_filter'];
        }
        if ( array_key_exists('operator_info_filter', $filters) ) {
            $filter[] = $filters['operator_info_filter'];
        }

        if ( count($filter) > 0 ) {
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/info',[
                'json' => [ 'user_info' => $filter, 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);

            $entityResult = json_decode($response->getBody()->getContents(),true);
            if ( array_key_exists('user_info_filter', $filters) ) {
                if (count( $entityResult ) && isset( $entityResult[$filters['user_info_filter']]['user_id'] )) {
                    $query->where($user_prefix.'.user_id', '=', $entityResult[$filters['user_info_filter']]["user_id"]);
                } else if ( !isset( $entityResult[$filters['user_info_filter']]) ) {
                    $query->where($user_prefix.'.user_id', '=', 0);
                }
            }

            if ( array_key_exists('operator_info_filter', $filters) ) {
                if (count( $entityResult ) && isset( $entityResult[$filters['operator_info_filter']]['user_id'] )) {
                    $query->where($operator_prefix.'.operator_id', '=', $entityResult[$filters['operator_info_filter']]["user_id"]);
                } else if ( !isset( $entityResult[$filters['operator_info_filter']]) ) {
                    $query->where($operator_prefix.'.operator_id', '=', -3);
                }
            }
        }
        return $query;
    }

    /**
     * @param int $userId
     * @return bool|\Illuminate\Database\Eloquent\Builder|mixed
     */
    public static function getLastActivePlan(int $userId)
    {
        $subscriptionDetails = SubscriptionUsers::query()
            ->selectRaw('*,DATE_ADD( start_date, INTERVAL duration MINUTE) as end_date')
            ->where('user_id', '=', $userId)
            ->whereRaw(sprintf('DATE_ADD( start_date, INTERVAL duration MINUTE) >= "%s"',Carbon::now()->toDateTimeString()))
            ->whereRaw('deleted_at is null')
            // ->whereRaw('NOW() <= DATE_ADD( start_date, INTERVAL duration DAY)')
            ->orderBy('start_date','desc')
            ->get();
        if($subscriptionDetails->count() > 0){
            return $subscriptionDetails[0];
        }else{
            return false;
        }
    }

    /**
     * @param int $userId
     * @return bool
     */
    public static function getIsSubscribedEver(int $userId)
    {
        $subscriptionUser = SubscriptionUsers::withTrashed()
            ->where('user_id','=',$userId)
            ->get();
        if($subscriptionUser->count() > 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getAllUsersPassedPlans()
    {
        return SubscriptionUsers::query()
            ->whereRaw('DATE_ADD( start_date, INTERVAL duration MINUTE) < CONVERT_TZ(UTC_TIMESTAMP(), "UTC", "Asia/Tehran")')
            ->get();
    }

    /**
     * @param int $userId
     * @return bool[]
     */
    public static function checkRenewalActions(int $userId)
    {
        $activePlan = self::getActivePlan($userId);

        $now = Carbon::now();

        $hasReserve = false;

        if ($activePlan->count() > 0){
            $activePlan = $activePlan[0];
            $activePlanFullDetail = SubscriptionUsers::find($activePlan->id);
            $futurePlans = self::getFuturePlans($userId);

            $remainEntities = $activePlanFullDetail->getRemainEntitiesAttribute();
            $remainDays = $activePlanFullDetail->getRemainDaysAttribute();

            if ($remainEntities <= 0 & $remainDays > 0 ) {
                if( $futurePlans->count() > 0 ) {
                    $hasReserve = true;
                    // user has future plan
                    // calculate active plan end date ( 1 mins before current date) and persist init data in start date and duration
                    $activePlanNewDuration = $now->diffInMinutes($activePlan->start_date);
                    $activePlanNewNormalizedDuration = $activePlanNewDuration - 1;
                    $activeEndDate = Carbon::parse($activePlan->start_date)->addMinutes($activePlanNewDuration);
                    $activePlan->init_start_date = $activePlan->start_date;
                    $activePlan->init_duration = $activePlan->duration;
                    $activePlan->duration = $activePlanNewNormalizedDuration;
                    $activePlan->save();

                    foreach ($futurePlans as $futurePlan){
                        $futurePlan->init_start_date = $futurePlan->start_date;
                        $futurePlan->init_duration = $futurePlan->duration;
                        $futurePlan->start_date = $activeEndDate->format('Y-m-d H:i:s');
                        $futurePlan->save();

                        $activeEndDate->addMinutes($futurePlan->duration);
                    }
                }
            }
        }
        return ['has_reserved' => $hasReserve];
    }

    // remain_entities_reserve
    public function getRemainEntitiesReserveAttribute(){
        $futurePlans = self::getFuturePlans($this->user_id);
        $total = 0;
        if ($futurePlans->count() > 0) {
            foreach ($futurePlans as $futurePlan){
                $total += $futurePlan->remain_entities;
            }
            return $total;
        } else {
            return 0;
        }
    }

    /**
     * @param $userId
     * @param $planId
     * @param $partnerPlanId
     * @param $isNewUser
     * @param null $trackingId
     * @return SubscriptionUsers
     * @throws \Throwable
     */
    public static function createSubscriptionUserSentFromPartners ($userId, $planId,$partnerPlanId, $isNewUser, $trackingId = null) {
        $currentPlans = SubscriptionUsers::getLastActivePlan($userId);

        if($currentPlans == false){
            $startDate = new \DateTime();
        } else {
            $startDate = date('Y-m-d H:i:s',strtotime($currentPlans['end_date'] . '+1 minutes'));
        }

        $subscriptionUsers = new SubscriptionUsers();
        $subscriptionUsers->user_id = $userId;
        $subscriptionUsers->plan_id = $planId;
        $subscriptionUsers->assignment_title = null;
        $subscriptionUsers->update_reason = null;
        $subscriptionUsers->operator_id = 0;
        $subscriptionUsers->is_crm = 0;
        $subscriptionUsers->start_date = $startDate;
        $subscriptionUsers->new_user = $isNewUser;
        $subscriptionUsers->partner_plan_id = $partnerPlanId;
        if ($trackingId != null){
            $subscriptionUsers->setTrackingId($trackingId);
        }
        $subscriptionUsers->saveOrFail();

        return $subscriptionUsers;
    }

    /**
     * @return int | null
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @param int $trackingId
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;
        return $this;
    }

}
