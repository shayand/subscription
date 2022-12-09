<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Helpers\Helper;
use App\Http\Requests\SubscriptionPlanEntitiesBulkRequest;
use App\Http\Requests\SubscriptionPlanEntitiesRequest;
use App\Models\SubscriptionBoughtHistories;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class SubscriptionPlanEntitiesController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * Instantiate a new SubscriptionPlanEntitiesController instance.
     */
    public function __construct()
    {
        $this->middleware('shuttle_auth', ['only' => ['store']]);
        parent::__construct();
//        $this->middleware('subscribed', ['except' => ['fooAction', 'barAction']]);
    }

    protected array $y = [];

    /**
     * list resources
     *
     * @param SubscriptionPlanEntitiesRequest $request
     * @param int $planId
     * @return JsonResponse
     * Test ?
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function index(SubscriptionPlanEntitiesRequest $request, int $planId): JsonResponse
    {
        $filter = $request->get('filter', null);
        if ($filter != null) {
            $filter = json_decode($filter, true);
        }
        $queryResource = SubscriptionPlanEntities::query()
            ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
            ->where('plan_id', '=', $planId);
        $queryResource = SubscriptionPlanEntities::getFilteredPlanEntities($queryResource, $filter)
            ->select([
                Tables::SUBSCRIPTION_ENTITIES.'.id as subscription_entity_id',
                Tables::SUBSCRIPTION_ENTITIES.'.*',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.created_at',
            ]);
        $total = $queryResource->count();
        $resourceOptions = $this->parseResourceOptions();
        $this->applyResourceOptions($queryResource, $resourceOptions);
        $entitiesQuery = clone($queryResource);

        $guzzle = new Client();
        $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
            'json' => [ 'book_ids' => $entitiesQuery->pluck(Tables::SUBSCRIPTION_ENTITIES.".entity_id") ]
        ]);
        $entityResult = json_decode($response->getBody()->getContents(),true);
        if ($entityResult['output']['result'] == false) {
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => []];
            return new JsonResponse( $response );
        }

        foreach ($entityResult['output']['books'] as $singleEntity) {
            $this->y[$singleEntity['id']] = [
                'title' => $singleEntity['title'],
                'publisher_title' => $singleEntity['publisher_title'],
                'publisher_marketshare' => $singleEntity['publisher_marketshare'],
                'price' => $singleEntity['price']
            ];
        }

        $list = $queryResource->get()->each(function ($item) {
            $item['entity_name'] = $this->y[$item['entity_id']]['title'];
            $item['publisher_title'] = $this->y[$item['entity_id']]['publisher_title'];
            $item['publisher_marketshare'] = $this->y[$item['entity_id']]['publisher_marketshare'];
            $item['price'] = $this->y[$item['entity_id']]['price'];
        });
        $parsedData = $this->parseData($list, $resourceOptions);

        Log::info('[SubscriptionPlanEntitiesController][index] the subscription plan entity has been listed');

        $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
        return new JsonResponse( $response );
    }

    /**
     * store resources
     *
     * @param  int $planId
     * @param  SubscriptionPlanEntitiesRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionPlanEntitiesRequest $request, int $planId)
    {
        $operator_id = $request->get('operator_id', null);
        try {
            SubscriptionPlans::query()->findOrFail($planId);
            $planIDs = SubscriptionPlans::all()->pluck('id');

            $inputs = $request->all();
            $entitiesStr = $inputs['entity_id'];
            $entitiesArr = explode(",", $entitiesStr);

            $insertedPlanEntities = [];
            $insertedIDs = [];
            $alreadyExistIDs = [];
            $failedIDs = [];
            $fakeIDs = [];
            foreach ($entitiesArr as $entityId) {
                try {
                    $entity = null;
                    try {
                        $entity = SubscriptionEntities::query()->where(['entity_id' => $entityId])->firstOrFail();
                    } catch (\Exception $exception) {
                        array_push($fakeIDs, $entityId);
                        continue;
                    }

//                    $planEntity = SubscriptionPlanEntities::query()->where([
//                        'entity_id' => $entity['id'],
//                        'plan_id' => $planId
//                    ])->first();


//                    if (/*$planEntity == null*/) {
//                        dd('$here', $entity['id']);
                    $planEntities = [];
                    $cnt = 0;
                    foreach ($planIDs as $planID) {
                        $now = Carbon::now()->toDateTimeLocalString();
                        $exist = SubscriptionPlanEntities::query()->where([
                            'entity_id' => $entity['id'],
                            'plan_id' => $planID
                        ])->exists();
                        if (! $exist ) {
                            $planEntities[] =[
                                'entity_id' => $entity['id'] ,
                                'plan_id' => $planID ,
                                'operator_id' => $operator_id ,
                                'created_at' => $now ,
                                'updated_at' => $now
                            ];
                            if ($cnt == 0) {
                                $insertedIDs[] = $entityId;
                            }
                        } else {
                            if ($cnt == 0) {
                                $alreadyExistIDs[] =  ['entity_id' => $entityId, 'plan_id' => $planID];
                            }
                        }
                        $cnt+= 1;
                    }
                    $planEntity = SubscriptionPlanEntities::insert($planEntities);
                    array_push($insertedPlanEntities, $planEntity);

                } catch (\Exception $err) {
                    $failed = ['id' => $entityId, 'err' => $err->getMessage()];
                    array_push($failedIDs, $failed);
                }
            }

            /**
             * send data to rabbitMQ
             */
            $sendToQueue = [];
            foreach ($insertedIDs as $single) {
                $entityTitle = 'book_'. $single;
                $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $planIDs]];
            }
            foreach ($alreadyExistIDs as $singleExists){
                $entityTitle = 'book_'. $singleExists['entity_id'];
                $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $planIDs]];
            }
            Helper::send_to_elastic($sendToQueue);
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlanEntitiesController][store] throw exception');
            return new JsonResponse( [ 'data' => [
                    'status' => 'failed',
                    'message' => $err->getMessage()
                ]
            ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = [
            'inserted_entities' => $insertedPlanEntities,
            'inserted_ids' => $insertedIDs,
            'failed_ids' => $failedIDs,
            'fake_ids' => $fakeIDs,
            'already_exist' => $alreadyExistIDs
        ];
        Log::info('[SubscriptionPlanEntitiesController][store] the subscription plan entity has been stored');
        return new JsonResponse(['data'=> $data ],ResponseCode::HTTP_CREATED );
    }

    /**
     * get specific subscription plan entity by $id
     * @param int $planId
     * @param int $planEntityId
     * @return JsonResponse
     * Test ?
     */
    public function show(int $planId, int $planEntityId)
    {
        try{
            $planEntity = SubscriptionPlanEntities::where('plan_id', '=', $planId)->findOrFail($planEntityId);
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlanEntitiesController][show] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed', 'message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionPlanEntitiesController][show] the subscription plan entity has been showed');
        return new JsonResponse( ['data' => $planEntity] ,ResponseCode::HTTP_OK);
    }

    /**
     * update specific subscription plan entity by id
     *
     * @param SubscriptionPlanEntitiesRequest $request
     * @param int $planId
     * @param int $planEntityId
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionPlanEntitiesRequest $request, int $planId, int $planEntityId)
    {
        try {
            $planEntity = SubscriptionPlanEntities::where('plan_id', '=', $planId)->findOrFail($planEntityId);
            $planEntity->update($request->all());
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlanEntitiesController][update] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionPlanEntitiesController][update] the subscription plan entity has been updated');
        return new JsonResponse(['data' => $planEntity],ResponseCode::HTTP_OK );
    }

    /**
     * remove specific subscription plan entity by id
     * @param int $planId
     * @param int $planEntityId
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $planId, int $planEntityId)
    {
        try{
            $planEntity = SubscriptionPlanEntities::query()->findOrFail($planEntityId);

//            /**
//             * send data to rabbitMQ
//             */
//            $sendToQueue = [];
//            $planEntities = SubscriptionPlanEntities::query()
//                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
//                ->where(Tables::SUBSCRIPTION_ENTITIES.'.id' , '=' , $planEntity->entity_id)
//                ->select([
//                    Tables::SUBSCRIPTION_ENTITIES.'.entity_id as real_entity_id',
//                    Tables::SUBSCRIPTION_PLAN_ENTITIES.'.*'
//                ])
//                ->get();

            $deleteRes = $planEntity->delete();
            // Everything is handled inside observer for the phase one.
//            if (!$deleteRes) {
//                throw new \Exception("Something unexpected happened. Please Try Again!");
//            }
//
//            $entityTitle = 'book_'. $planEntities[0]->real_entity_id;
//            if (count($planEntities) == 1) {
//                $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => []]];
//            } else {
//                $plansArray = [];
//                foreach ($planEntities as $singlePlanEntity) {
//                    $plansArray[] = $singlePlanEntity->plan_id;
//                }
//                $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $plansArray]];
//            }
//
//            $message = json_encode(['index' => 'fidibo-content-v1.0','cols' => $sendToQueue]);
//            Amqp::publish('/', $message, ['queue' => env('AMQP_QUEUE_SUBSCRIPTION')]);
//            /**
//             * end send data to rabbitMQ
//             */
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlanEntitiesController][destroy] throw exception');
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY );
        }

        Log::info('[SubscriptionPlanEntitiesController][destroy] the subscription plan entity has been removed');
        return $this->response( ['data' => ['status' => 'success' , 'message' => 'The plan entity has been removed.']],ResponseCode::HTTP_OK);
    }

    /**
     * @param int $userId
     * @param int $contentId
     * @return JsonResponse|\Optimus\Bruno\Illuminate\Http\JsonResponse
     */
    public function checkIsContentAvailableForUser(int $userId,int $contentId)
    {
        try{
            $boughtEntities = [];
            $boughtContents = SubscriptionBoughtHistories::query()
                ->where(['user_id' => $userId])->get();
            foreach ($boughtContents as $singleContent){
                $boughtEntities[] = $singleContent->entity_id;
            }
            if(in_array($contentId,$boughtEntities)){
                return $this->response( ['data' => ['status' => 'failed' , 'message' => 'this content is not available for user' , 'is_available' => false,'is_book_downloaded' => false] ],ResponseCode::HTTP_OK);
            }

            $userActivePlans = SubscriptionUsers::getActivePlan($userId);
            if($userActivePlans->count() == 0){
                throw new \Exception('this user has not active plan');
            }
            $activePlanId = $userActivePlans[0]->plan_id;
            $subscriptionUserId = $userActivePlans[0]->id;
            $userSubscriptionDetails = SubscriptionUsers::find($subscriptionUserId);
            $plan = SubscriptionPlans::find($activePlanId);

            $planDetails = ['id' => $plan->id, 'start_date' => $plan->start_date, 'end_date' => $plan->end_date, 'title' => $plan->title,
                'max_books' => $plan->max_books, 'max_audios' => $plan->max_audios,
                'duration' => $plan->duration, 'price' => $plan->price,
                'discount_price' => 0, 'remain_days' => $userSubscriptionDetails->remain_days, 'remain_books' => $userSubscriptionDetails->remain_books,
                'remain_audios' => $userSubscriptionDetails->remain_audios];


            $startDateTime = strtotime($plan->start_date);
            $startDate = $plan->start_date;
            $duration = $plan->duration;
            $endDateTime = strtotime($startDate . ' +' . $duration . 'days');
            $shamsiStartDate = Jalalian::forge($startDateTime)->format('%Y %B %d');
            $shamsiEndDate = Jalalian::forge($endDateTime)->format('%Y %B %d');
            $planDetails['shamsi_start_date'] = $shamsiStartDate;
            $planDetails['shamsi_end_date'] = $shamsiEndDate;

            $ret = SubscriptionUsers::checkIsContentAvailableForUser($userId,$contentId);
            $allBooks = [];

            if ($ret->count() > 0){
                $userContents = SubscriptionUsers::getUserActivePlanContents($userId);
                $idDownloaded = false;
                if($userContents != null){
                    foreach ($userContents as $values){
                        if($values->entity_id == $contentId){
                            $idDownloaded = true;
                            break;
                        }
                    }
                }

                $wholeHistory = SubscriptionUserHistories::query()
                    ->where('subscription_user_id','=',$subscriptionUserId)
                    ->get();
                foreach ($wholeHistory as $singleBook){
                    $allBooks[] = $singleBook->entity_id;
                }

                $data = [
                    'status' => 'success' ,
                    'message' => 'this content is available for user' ,
                    // in my plans
                    'is_available' => in_array($contentId,$allBooks) ,
                    'subscription_plan_entity_id' => $ret[0]->plan_id,
                    'subscription_user_id' => $ret[0]->id,
                    // is primary
                    'is_book_downloaded' => $idDownloaded,
                    'subscription' => $planDetails
                ];
            } else {
                $data = ['status' => 'failed' , 'message' => 'this content is not available for user' , 'is_available' => false,'is_book_downloaded' => false];
            }
            Log::info('[SubscriptionPlanEntitiesController][checkIsContentAvailableForUser] check whether content available for user id: '. $userId .' & content id: ' . $contentId );
            return $this->response( ['data' => $data ],ResponseCode::HTTP_OK);

        } catch (\Exception $exception){

            $data = ['status' => 'failed' , 'message' => $exception->getMessage() , 'is_available' => false,'is_book_downloaded' => false];
            return $this->response( ['data' => $data ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param SubscriptionPlanEntitiesBulkRequest $request
     * @param int $userId
     * @return JsonResponse|\Optimus\Bruno\Illuminate\Http\JsonResponse
     */
    public function syncContents(SubscriptionPlanEntitiesBulkRequest $request,int $userId)
    {
        $boughtEntities = [];
        $boughtContents = SubscriptionBoughtHistories::query()
            ->where(['user_id' => $userId])->get();
        foreach ($boughtContents as $singleContent){
            $boughtEntities[] = $singleContent->entity_id;
        }

        $input = $request->all();
        $contents = SubscriptionPlanEntities::getPlanEntitiesNEntityID($userId,$input['entity_id']);
        $lastEntities = SubscriptionUsers::getUserLastEntities($userId);

        $final = [];
        foreach ($input['entity_id'] as $singleEntity){
            $loop = ['is_on_table' => false, 'is_in_subscription' => true,'is_downloaded' => false];
            if(!in_array($singleEntity,$boughtEntities)){
                if($lastEntities != null){
                    if($singleEntity == $lastEntities['audios']){
                        $loop['is_on_table'] = true;
                    }
                    if($singleEntity == $lastEntities['books']){
                        $loop['is_on_table'] = true;
                    }
                }
                foreach ($contents as $singleContent){
                    if ($singleContent->entity_id == $singleEntity) {
                        $loop['is_downloaded'] = true;
                        break;
                    }
                }
            }

            $final[$singleEntity] = $loop;
        }

        Log::info('[SubscriptionPlanEntitiesController][syncContents] Sync data from app with subscriptions for user id: '. $userId);
        return $this->response( ['data' => $final ],ResponseCode::HTTP_OK);
    }

    /**
     * @param int $userId
     * @return JsonResponse|\Optimus\Bruno\Illuminate\Http\JsonResponse
     */
    public function syncUserContents(int $userId)
    {
        $contents = SubscriptionUsers::getUserActivePlanContents($userId);
        $lastEntities = SubscriptionUsers::getUserLastEntities($userId);

        $final = [];
        foreach ($contents as $singleContent){
            $loop = ['is_on_table' => false, 'is_in_subscription' => false,'is_downloaded' => false];
            if($singleContent->entity_id == $lastEntities['audios']){
                $loop['is_on_table'] = true;
            }
            if($singleContent->entity_id == $lastEntities['books']){
                $loop['is_on_table'] = true;
            }

            $loop['is_downloaded'] = true;

            $loop['is_in_subscription'] = true;

            $final[$singleContent->entity_id] = $loop;
        }

        Log::info('[SubscriptionPlanEntitiesController][syncContents] Sync data from app with subscriptions for user id: '. $userId);
        return $this->response( ['data' => $final ],ResponseCode::HTTP_OK);
    }

    public function book_is_fidiplus(SubscriptionPlanEntitiesRequest $request) {
        try {
            $inputs = $request->all();
            $entitiesStr = $inputs['entity_id'];
            $entitiesArr = explode(",", $entitiesStr);
            $entities = SubscriptionEntities::with('plan_entities');

            $result = [];
            foreach ($entitiesArr as $entity_id) {
                $entities_tmp = clone($entities);
                $res = $entities_tmp->where(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', '=', $entity_id)->first();
                if ($res == []) {
                    $result[$entity_id] = false;
                    continue;
                }

                if ($res->plan_entities == []) {
                    $result[$entity_id] = false;
                    continue;
                }

                if (count($res->plan_entities) > 0) {
                    $result[$entity_id] = true;
                } else {
                    $result[$entity_id] = false;
                }
            }
            Log::channel('gelf')->info('[SubscriptionPlanEntitiesController][book_is_fidiplus] the subscription plan entity has been listed');

            $response = ['data' => $result];
            return new JsonResponse( $response );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPlanEntitiesController][book_is_fidiplus] throw exception');
            return new JsonResponse( [ 'data' => [
                'status' => 'failed',
                'message' => $err->getMessage()
            ]
            ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

}
