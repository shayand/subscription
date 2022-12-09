<?php

namespace App\Models;

use App\Constants\Tables;
use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SubscriptionPlans
 * @package App\Models
 */
class SubscriptionPlans extends Model
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
        'title',
        'start_date',
        'end_date',
        'price',
        'duration',
        'max_books',
        'max_audios',
        'store_id',
        'total_publisher_share',
        'status',
        'is_show',
        'max_devices',
        'max_offline_entities',
        'description',
        'operator_id',
        'usd_price',
        'color_hex_code',
        'description'
    ];

    protected $appends = [
        'discount_price',
        'remain_days',
        'remain_books',
        'max_entities',
        'remain_audios',
        'plan_text_description',
    ];

    public function partners() {
        return $this->belongsToMany(
            SubscriptionPartners::class,
            Tables::SUBSCRIPTION_PARTNERS_PLANS,
            'subscription_plan_id',
            'subscription_partner_id')->wherePivot('deleted_at', '=', null);
    }

    public static function getActivePlans() {
        return SubscriptionPlans::query()
            ->where('is_show', '=', 1)->where('status', '=', 1)
            ->whereRaw('Date(NOW()) >= '.Tables::SUBSCRIPTION_PLANS.'.start_date And (Date(NOW()) <= '.Tables::SUBSCRIPTION_PLANS.".end_date OR ".Tables::SUBSCRIPTION_PLANS.".end_date IS NULL)");
    }

    public static function getDeActivePlans() {
        return SubscriptionPlans::withTrashed()->query()
            ->whereRaw('Date(NOW()) < '.Tables::SUBSCRIPTION_PLANS.'.start_date OR (Date(NOW()) > '
                .Tables::SUBSCRIPTION_PLANS.".end_date OR ".Tables::SUBSCRIPTION_PLANS.".end_date IS NULL)");
    }

    public function getDiscountPriceAttribute()
    {
        return 0;
    }

    public function getMaxEntitiesAttribute()
    {
        return $this->attributes['max_books'] + $this->attributes['max_audios'];
    }

    public function getRemainDaysAttribute()
    {
        return $this->attributes['duration']; // TODO return correct duration
    }

    public function getRemainBooksAttribute()
    {
        return $this->attributes['max_books'];  // TODO return correct remain_book
    }

    public function getRemainAudiosAttribute()
    {
        return $this->attributes['max_audios'];  // TODO return correct remain_audio
    }

    public function getPlanTextDescriptionAttribute()
    {
        return $this->attributes['description'];
    }

    public static function getFilteredPlans($query, $filters)
    {
        if ( isset($filters['plan_title_filter']) ) {
            $query = $query->whereRaw("title LIKE CONCAT('%',".sprintf("'%s'", $filters['plan_title_filter']).", '%')");
        }

        if ( isset($filters['plan_id_filter']) ) {
            $query = $query->where('id', '=', $filters['plan_id_filter']);
        }

        if ( isset($filters['plan_status_filter']) ) {
            $query = $query->where('status', '=', $filters['plan_status_filter']);
        }

        if ( isset($filters['plan_isshow_filter']) ) {
            $query = $query->where('is_show', '=', $filters['plan_isshow_filter']);
        }

        if ( isset($filters['plan_start_filter']) ) {
            $query = $query->where('start_date', '=', $filters['plan_start_filter'])
                ->orwhere('created_at', '=', $filters['plan_start_filter']);
        }

        if ( isset($filters['plan_duration_filter']) ) {
            $query = $query->where('duration', '=', $filters['plan_duration_filter']);
        }
        return $query;
    }

    // public static function check_and_set_activation_status(SubscriptionPlans $plan) {
    //     $startDate = Carbon::parse($plan->start_date)->toDateString();
    //     $endDate = Carbon::parse($plan->start_date)->addDays((int)$plan->duration)->toDateString();
    //     try {
    //         if (Helper::compare_time_with_now($startDate) && !Helper::compare_time_with_now($endDate)) {
    //             $plan->status = 1;
    //         } else {
    //             $plan->status = 2;
    //         }
    //     } catch (\Exception $e) {
    //         dd($e->getMessage());
    //     }
    // }

    /**
     * Updates plan_IDs of all the entities in elastic.
     */
    public static function update_elastic() {
        $entityIDs = SubscriptionEntities::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id', '=', Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->pluck(Tables::SUBSCRIPTION_ENTITIES.'.entity_id');
        $planIDs = SubscriptionPlans::all()->pluck('id');
        $sendToQueue = [];
        foreach ($entityIDs as $entityID) {
            $entityTitle = 'book_'. $entityID;
            $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $planIDs]];
        }
        Helper::send_to_elastic($sendToQueue);
        return true;
    }

    public static function create_plan_entities(int $planID, int $operatorID = 0) {
        $entityIDs = SubscriptionEntities::query()
            ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id', '=', Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->distinct(Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->pluck(Tables::SUBSCRIPTION_ENTITIES.'.id');

        $now = Carbon::now()->toDateTimeLocalString();
        $planEntities = [];
        foreach ($entityIDs as $entityID) {
            $planEntities[] = [
                'entity_id' => $entityID,
                'plan_id' => $planID,
                'operator_id' => $operatorID,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        SubscriptionPlanEntities::insert($planEntities);
        // $cnt = count($planEntities);
        // if ($cnt <= 1000) {
        //     SubscriptionPlanEntities::insert($planEntities);
        // } else {
        //     $totalRecords = (int) $cnt / 1000;

        //     for($i=0;$i < $totalRecords;$i++){
        //         $arrSlice = array_slice($planEntities, $i, $i*1000);
        //         SubscriptionPlanEntities::insert($arrSlice);
        //     }

        //     $remains = fmod($cnt,100);

        //     if($remains > 0){
        //         $arrSlice = array_slice($planEntities, $totalRecords*1000);
        //         SubscriptionPlanEntities::insert($arrSlice);
        //     }
        // }

    }

    public static function remove_plan_entities(int $planID) {
        $planEntityIDs = SubscriptionPlanEntities::query()->whereIn('plan_id', $planID)->pluck('id');
        SubscriptionPlanEntities::destroy($planEntityIDs);
    }

    public static function applyPlanTitleFilter($query, $filters) {
        if ( isset($filters['plan_title_filter']) ) {
            $query->whereRaw(Tables::SUBSCRIPTION_PLANS.".title LIKE CONCAT('%',".sprintf("'%s'", $filters['plan_title_filter']).", '%')");
        }
        return $query;
    }
}
