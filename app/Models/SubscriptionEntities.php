<?php

namespace App\Models;

use App\Constants\Tables;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionEntities extends Model
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
        'entity_type',
        'entity_id',
        'price_factor',
        'publisher_id',
        'publisher_share',
        'operator_id'
    ];

    protected $appends = [
        'plans_number',
    ];

    protected array $plan_field_rel = [
        "title",
        "start_date",
        "end_date",
        "price",
        "duration",
        "status",
        "is_show",
        "max_books",
        "max_audios",
    ];

    /**
     * The plans that belong to the entities.
     * @return BelongsToMany
     */
    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlans::class, Tables::SUBSCRIPTION_PLAN_ENTITIES, 'entity_id', 'plan_id')
                ->withPivot('created_at', 'updated_at', 'deleted_at')->select($this->plan_field_rel);
    }

    public function plan_entities() {
        return $this->hasMany(SubscriptionPlanEntities::class, 'entity_id', 'id');
    }


    /**
     * The plans that belong to the entities.
     * @return BelongsToMany
     */
    public function active_plans()
    {
        return $this->belongsToMany(SubscriptionPlans::class, Tables::SUBSCRIPTION_PLAN_ENTITIES, 'entity_id', 'plan_id')
            ->where('status', '=', 1)
            ->withPivot('created_at', 'updated_at', 'deleted_at')->select($this->plan_field_rel);
    }

    /**
     * The plans that belong to the entities.
     * @return BelongsToMany
     */
    public function inactive_plans()
    {
        return $this->belongsToMany(SubscriptionPlans::class, Tables::SUBSCRIPTION_PLAN_ENTITIES, 'entity_id', 'plan_id')
            ->where('status', '=', 0)
            ->withPivot('created_at', 'updated_at', 'deleted_at')->select($this->plan_field_rel);
    }

    public function getPlansNumberAttribute()
    {
        return $this->active_plans()->count();
    }

    public static function getPublisherMostlySoldBookQuery(int $publisherID) {
        $lastYear = Carbon::now()->subYear()->toDateTimeLocalString();
        return SubscriptionEntities::query()
            ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id', '=', Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->join(Tables::SUBSCRIPTION_SHARE_ITEMS, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id', '=', Tables::SUBSCRIPTION_SHARES.'.id')
            ->where([
                Tables::SUBSCRIPTION_ENTITIES.'.publisher_id' => $publisherID,
            ])
            ->where(Tables::SUBSCRIPTION_SHARES.".created_at", ">=", $lastYear)
            ->groupBy(Tables::SUBSCRIPTION_ENTITIES.'.entity_id')
            ->selectRaw(
                "COUNT( DISTINCT( ".Tables::SUBSCRIPTION_SHARES.'.subscription_user_id'." ) ) as count, ".Tables::SUBSCRIPTION_ENTITIES.'.entity_id'
            )
            ->orderByRaw("count( distinct(subscription_user_id) ) DESC")->limit(5);

//        return SubscriptionEntities::query()
//            ->join(Tables::SUBSCRIPTION_USER_HISTORIES, Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id', '=', Tables::SUBSCRIPTION_ENTITIES.'.entity_id')
//            ->where([
//                Tables::SUBSCRIPTION_ENTITIES.'.publisher_id' => $publisherID,
//            ])
//            ->where(Tables::SUBSCRIPTION_USER_HISTORIES.".created_at", ">=", $lastYear)
//            ->groupBy(Tables::SUBSCRIPTION_ENTITIES.'.entity_id')
//            ->selectRaw(
//                "COUNT( DISTINCT( ".Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id'." ) ) as count, ".Tables::SUBSCRIPTION_ENTITIES.'.entity_id'
//            )
//            ->orderByRaw("count( distinct(subscription_user_id) ) DESC")->limit(5);
    }

    public static function getPublisherSubscriptionBooks($publisherPlanEntityIds){
        //TODO report sales boxes
        return SubscriptionEntities::query()->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id', '=', Tables::SUBSCRIPTION_ENTITIES.'.id')
            ->whereIn(Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', $publisherPlanEntityIds)->distinct(Tables::SUBSCRIPTION_ENTITIES.'.id')->count();
    }

    public static function applyEntitiesFilters($entityQuery, $filter) {
        $entityIdFilter = ( isset($filter['entity_id_filter']) ) ? $filter['entity_id_filter'] : null;
        $entityNameFilter = ( isset($filter['entity_name_filter']) ) ? $filter['entity_name_filter'] : null;

        if (isset($entityIdFilter)) {
            $entityQuery->where('entity_id', '=', $entityIdFilter);
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
                $entityQuery = $entityQuery->whereIn('entity_id', $IDs);
            }
        }
        return $entityQuery;
    }

    public static function set_types($operatorID, $price_factor) {
        $entityIDs = SubscriptionEntities::query()->where('entity_type', '=', "N/A")
            ->pluck('entity_id')->toArray();

        if (count($entityIDs) == 0) {
            return [
                "inserted_entities" => [],
                "inserted_ids" => []
            ];
        }
        $guzzle = new Client();
        $response = $guzzle->post('https://papi.fidibo.com/new/get/book/by/id',[
            'json' => [ 'book_ids' => $entityIDs, 'access_key' => env("PAPI_ACCESS_KEY") ]
        ]);
        $entityResult = json_decode($response->getBody()->getContents(),true);

        if ($entityResult['output']['result'] == false) {
            throw new \Exception("Non of the input IDs are valid.");
        }

        $insertedEntities = [];
        $insertedIDs = [];
        if( is_array($entityResult['output']) & is_array($entityResult['output']['books']) ) {
            foreach ($entityResult['output']['books'] as $singleEntity) {
                $type = "book";
                if ($singleEntity['format'] == "AUDIO" and $singleEntity['content_type'] == "book") {
                    $type = "audio";
                }
                try {
                    $entity = SubscriptionEntities::query()
                        ->where('entity_id', '=', $singleEntity['id'])
                        ->first();

                    if ($entity != null) {
                        $entity->entity_type = $type;
                        $entity->publisher_id = $singleEntity['publisher_id'];
                        $entity->publisher_name = $singleEntity['publisher_title'];
                        $entity->publisher_share = $singleEntity['publisher_marketshare'];
                        $entity->price_factor = $price_factor;
                        $entity->operator_id = $operatorID;
                        $entity->save();

                        $insertedEntities[] = $entity;
                        $insertedIDs[] = $entity->entity_id;
                    }
                } catch (\Exception $err) {
                    $failed = ['id' => $singleEntity->id, 'err' => $err->getMessage()];
                    array_push($failedIDs, $failed);
                }
            }
        }

        return [
            "inserted_entities" => $insertedEntities,
            "inserted_ids" => $insertedIDs
        ];
    }

    public static function random_specific_number_of_entities($planId, int $max = 10, int $min = 1) {
        $pE = SubscriptionPlanEntities::query()->where('plan_id', '=', $planId)
            ->inRandomOrder()->limit(rand($min, $max))->pluck('id');

        return SubscriptionPlanEntities::query()
            ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
            ->whereIn(Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id', $pE)
            ->select([Tables::SUBSCRIPTION_ENTITIES.'.*', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id as plan_entity_id'])->distinct()
            ->inRandomOrder()->limit(rand(1, $max))->get()->toArray();
    }
}
