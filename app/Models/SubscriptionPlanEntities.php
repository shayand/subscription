<?php

namespace App\Models;

use App\Constants\Tables;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanEntities extends Model
{
    use HasFactory,SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'entity_id',
        'plan_id',
        'operator_id'
    ];

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity()
    {
        return $this->hasOne('App\Models\SubscriptionEntities');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function plan()
    {
        return $this->hasOne('App\Models\SubscriptionPlans');
    }

    public static function findByPlanNEntity(int $planId,int $entityId){
        return DB::table('subscription_entities')
            ->join('subscription_plan_entities','subscription_plan_entities.entity_id','=','subscription_entities.id')
            ->select('subscription_plan_entities.*')
            ->where(['subscription_entities.entity_id' => $entityId,'subscription_plan_entities.plan_id'=>$planId])
            ->get();
    }

    /**
     * @param int $userId
     * @return \Illuminate\Support\Collection | array
     */
    public static function getPlanEntities(int $userId){
        $userPlans = SubscriptionUsers::withTrashed()
            ->select(['plan_id'])
            ->where('user_id','=',$userId)
            ->get();
        if($userPlans->count() > 0){
            $plansArray = [];
            foreach ($userPlans as $userPlan){
                $plansArray[] = $userPlan->plan_id;
            }
            return DB::table('subscription_entities')
                ->join('subscription_plan_entities','subscription_plan_entities.entity_id','=','subscription_entities.id')
                ->select('subscription_entities.entity_id')
                ->whereIn('subscription_plan_entities.plan_id',$plansArray)
                ->get();
        }else{
            return [];
        }
    }

    /**
     * @param int $userId
     * @return \Illuminate\Support\Collection | array
     */
    public static function getPlanEntitiesNEntityID(int $userId, array $entities){
        $userPlans = SubscriptionUsers::withTrashed()
            ->select(['plan_id'])
            ->where('user_id','=',$userId)
            ->get();
        if($userPlans->count() > 0){
            $plansArray = [];
            foreach ($userPlans as $userPlan){
                $plansArray[] = $userPlan->plan_id;
            }
            return DB::table('subscription_entities')
                ->join('subscription_plan_entities','subscription_plan_entities.entity_id','=','subscription_entities.id')
                ->select('subscription_entities.entity_id')
                ->whereIn('subscription_entities.entity_id',$entities)
                ->whereIn('subscription_plan_entities.plan_id',$plansArray)
                ->get();
        }else{
            return [];
        }
    }



    public static function getPublisherPlanEntityIds(int $publisherId) {
        return SubscriptionPlanEntities::query()->join(Tables::SUBSCRIPTION_ENTITIES,
            Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
            ->where(Tables::SUBSCRIPTION_ENTITIES.'.publisher_id', '=', $publisherId)
            ->pluck(Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id');
    }

    public static function getFilteredPlanEntities($query, $filter)
    {
        $entityIdFilter = ( isset($filter['entity_id_filter']) ) ? $filter['entity_id_filter'] : null;
        $entityNameFilter = ( isset($filter['entity_name_filter']) ) ? $filter['entity_name_filter'] : null;

        if (isset($entityIdFilter)) {
            $query->where(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', '=', $entityIdFilter);
        }

        if (isset($entityNameFilter)) {
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/book/by/name',[
                'json' => [ 'book_title' => $entityNameFilter, 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);

            if (count( $entityResult ) != 0) {
                $IDs = [];
                foreach ($entityResult as $item) {
                    array_push($IDs, $item['book_id']);
                }
                $query = $query->whereIn(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', $IDs);
            }
        }
        return $query;
    }
}
