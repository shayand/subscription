<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Constants\UpdateReasons;
use App\Exceptions\PlanIrrelevantEntityException;
use App\Exceptions\UserActivePlanException;
use App\Exceptions\UserPlanEntitiesLimitationsException;
use App\Models\SubscriptionBoughtHistories;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionUsers;
use Carbon\Carbon;
use App\Http\Requests\SubscriptionUserHistoriesRequest;
use App\Models\SubscriptionUserHistories;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionUserHistoriesController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * Instantiate a new SubscriptionUserHistoriesController instance.
     */
    public function __construct()
    {
        $this->middleware('shuttle_auth', ['only' => ['crm_assign_entity_to_user']]);
        parent::__construct();
//        $this->middleware('subscribed', ['except' => ['fooAction', 'barAction']]);
    }

    public function crm_get_user_history_changes(SubscriptionUserHistoriesRequest $request)
    {
        try {
            $filter = $request->only(['filter']);
            if($filter != null) {
                $filter = json_decode($filter["filter"], true);
            }

            $queryResource = SubscriptionUserHistories::query()->whereNotNull(Tables::SUBSCRIPTION_USER_HISTORIES.'.operator_id')
                ->where(Tables::SUBSCRIPTION_USER_HISTORIES.'.operator_id', '!=', '0')
                ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id')
                ->select([
                    Tables::SUBSCRIPTION_USER_HISTORIES.'.*',
                    Tables::SUBSCRIPTION_USERS.'.user_id'
                ]);
            $queryResource = SubscriptionUsers::applyUserInfoFilter(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USER_HISTORIES, $queryResource, $filter);
            if (array_key_exists("date_filter_start", $filter)) {
                $queryResource->where(Tables::SUBSCRIPTION_USERS.'.created_at', '>=', $filter["date_filter_start"]);
            }

            if (array_key_exists("date_filter_end", $filter)) {
                $queryResource->where(Tables::SUBSCRIPTION_USERS.'.created_at', '<=', $filter["date_filter_end"]);
            }

            $total = $queryResource->count();

            $entityResult = [];
            if ($total != 0) {
                $userIDsQuery = clone($queryResource);
                $userIDs = $userIDsQuery->pluck('user_id')->toArray();
                $operatorIDsQuery = clone($queryResource);
                $operatorIDs = $operatorIDsQuery->pluck('operator_id')->toArray();
                $IDs = array_merge($userIDs, $operatorIDs);

                $guzzle = new Client();
                $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                    'json' => [ 'user_id' =>  $IDs, 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);
                $entityResult = json_decode($response->getBody()->getContents(),true);
            }

            $userHistories = $queryResource->get()->each(function ($item) use ($entityResult) {
                if (array_key_exists($item->user_id, $entityResult)) {
                    $item->username = $entityResult[$item->user_id]["username"];
                } else {
                    $item->username = "";
                }

                if (array_key_exists($item->operator_id, $entityResult)) {
                    $item->operator_name = $entityResult[$item->operator_id]["username"];
                } else {
                    $item->operator_name = "";
                }

                if (array_key_exists($item->update_reason, UpdateReasons::UPDATE_REASONS)) {
                    $item->reason = UpdateReasons::UPDATE_REASONS[$item->update_reason];
                } else {
                    $item->reason = "Default 'Reason";
                }
            });

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $parsedData = $this->parseData($userHistories, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            Log::channel('gelf')->info('[SubscriptionUserHistoriesController][crm_user_history_changes] List of user histories created by operators:');
            return new JsonResponse( $response );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][crm_user_history_changes] List of user histories created by operators:');
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * list resources
     *
     * @param int $userId
     * @param int $planId
     * @return JsonResponse
     * Test ?
     */
    public function index(int $userId): JsonResponse
    {
        try {
            $userPlan = SubscriptionUsers::getActivePlan($userId);
            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][index]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $queryResource = SubscriptionUserHistories::query()
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.".id", '=', Tables::SUBSCRIPTION_USER_HISTORIES.".subscription_plan_entity_id")
                ->where([
                    Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userPlan[0]->id,
                    Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id' => $userPlan[0]->plan_id
                ])
            ->select( Tables::SUBSCRIPTION_USER_HISTORIES.'.*');

            $total = $queryResource->count();
            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $list = $queryResource->get();
            $parsedData = $this->parseData($list, $resourceOptions);

            Log::channel('gelf')->info('[SubscriptionUserHistoriesController][index] the subscription user histories has been listed for:', [
                'user_id' => $userId,
                'plan_id' => $userPlan[0]->plan_id
            ]);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response, Response::HTTP_OK );
        } catch (\Exception $err){

            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][index]: Something went wrong in deletion of following user history', [
                'error' => $err->getMessage()
            ]);
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * store resources
     *
     * @param SubscriptionUserHistoriesRequest $request
     * @param int $userId
     * @param int $entityId
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function store(SubscriptionUserHistoriesRequest $request, int $userId, int $entityId)
    {
        try {
            $hasReserved = SubscriptionUsers::checkRenewalActions($userId);
            $entityQuery = SubscriptionUsers::getActivePlan($userId);

            if(count($entityQuery) == 0){
                throw new UserActivePlanException('The user does not have active plan.');
            }
            $planUserId = $entityQuery[0]->plan_id;

            // check entity id in plan
            $planEntityQuery = SubscriptionPlanEntities::findByPlanNEntity($planUserId ,$entityId);
            if(count($planEntityQuery) == 0){
                throw new PlanIrrelevantEntityException('The entity with this plan id does not exists');
            }
            $planEntityId = $planEntityQuery[0]->id;

            $userSubscriptionDetails = SubscriptionUsers::find($entityQuery[0]->id);
            $usedEntities = SubscriptionUserHistories::getUserEntitiesHistory($userSubscriptionDetails->id);
            if($userSubscriptionDetails->getRemainEntitiesAttribute() <= 0 & !in_array($entityId,$usedEntities)){
                throw new UserPlanEntitiesLimitationsException('This user is ran out of plan entities' . $userId);
            }

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
                'json' => [ 'book_ids' => [ $entityId ] ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);

            if( is_array($entityResult['output']) & is_array($entityResult['output']['books']) ) {
                $entityInfo = $entityResult['output']['books'][0];
                $entityType = ($entityInfo['format'] == 'AUDIO') ? 'audio' : 'book';

                $entity = SubscriptionEntities::query()->where('entity_id', '=', $entityId)->firstOrFail()->toArray();
                $entityInfo['price_factor'] = $entity['price_factor'];
                // $entityInfo['publisher_share'] = $entity['publisher_share'];
                $entityInfo['publisher_share'] = $entityInfo['publisher_marketshare'];
                $entityInfo['entity_id'] = $entityId;
                $entityInfo['id'] = $entity['id'];

                $planEntityDetails = SubscriptionPlans::find($planUserId);

                $setLogged = SubscriptionUserHistories::query()
                    ->where('subscription_user_id','=',$entityQuery[0]->id)
                    ->where('entity_id','=',$entityId)
                    ->where('is_logged','=','1')
                    ->get();

                $userHistoryParameters = [
                    'subscription_user_id' => $entityQuery[0]->id,
                    'subscription_plan_entity_id' => $planEntityId,
                    'entity_id' => $entityId,
                    'start_date' => Carbon::now()->toDateTimeLocalString(),
                    'subscription_entity_details' => $entityInfo,
                    'operator_id' => $request->get('operator_id', null),
                    'update_reason' => $request->get('update_reason', 0)
                ];

                if ($setLogged->count() == 0) {
                    $userHistoryParameters['is_logged'] = 1;
                } else {
                    $userHistoryParameters['is_logged'] = 0;
                }
                // set enddate of other entities
                $updateOrFail = SubscriptionUserHistories::updateUserEndDates($entityQuery[0]->id,$entityType,$entityId);
                if($updateOrFail->count() > 0){
                    Log::channel('gelf')->error('[SubscriptionUserHistoriesController][store]: end date of user has been sets for content type .',['user_id' => $userId,'entity_type' => $entityType]);
                }

                $isAddedToHistoryBefore = SubscriptionUserHistories::query()
                    ->orderByDesc('start_date')
                    ->where('subscription_user_id','=',$entityQuery[0]->id)
                    ->where('entity_id','=',$entityId)
                    ->whereNull('end_date')
                    ->get();

                if ($isAddedToHistoryBefore->count() > 0) {
                    $subscriptionHistoryUserId = $isAddedToHistoryBefore[0]->id;
                    unset($userHistoryParameters['start_date']);
                    SubscriptionUserHistories::where('id','=',$subscriptionHistoryUserId)
                        ->update($userHistoryParameters);
                    $newUserHistory = SubscriptionUserHistories::find($subscriptionHistoryUserId);
                } else {
                    $newUserHistory = SubscriptionUserHistories::create($userHistoryParameters);
                }

                $startDate = $userSubscriptionDetails->start_date;
                $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $userSubscriptionDetails->duration . 'minutes'));
                $shamsiStartDate = Jalalian::forge($startDate)->format('%Y %B %d');
                $shamsiEndDate = Jalalian::forge($endDate)->format('%Y %B %d');

                $planDetails = [
                    'id' => $planEntityDetails->id,
                    'start_date' => $userSubscriptionDetails->start_date,
                    'end_date' => $endDate,
                    'title' => $planEntityDetails->title,
                    'duration' => $planEntityDetails->duration,
                    'price' => $planEntityDetails->price,
                    'discount_price' => 0,
                    'remain_days' => $userSubscriptionDetails->getRemainDaysAttribute(),
                    'reserve_days' => $userSubscriptionDetails->getRemainDaysAttribute(true),
                    'max_entities' => $userSubscriptionDetails['plan_details']['max_entities'],
                    'remain_entities' => $userSubscriptionDetails->getRemainEntitiesAttribute(),
                    'reserve_entities' => $userSubscriptionDetails->getRemainEntitiesReserveAttribute(),
                    'shamsi_start_date' => $shamsiStartDate,
                    'shamsi_end_date' => $shamsiEndDate
                ];

                $lastEntities = SubscriptionUsers::getUserLastEntities($userId);
                if(!in_array($entityId ,$lastEntities['all'])){
                    if($entityInfo['format'] == 'AUDIO'){
                        if($userSubscriptionDetails->remain_audios <= 0){
                            return new JsonResponse( ['data'=> ['status' => 'ranout','type' => 'audio','plan_details' => $planDetails]] ,ResponseCode::HTTP_OK);
                        }
                    } else {
                        if($userSubscriptionDetails->remain_books <= 0){
                            return new JsonResponse( ['data'=> ['status' => 'ranout','type' => 'book','plan_details' => $planDetails]] ,ResponseCode::HTTP_OK);
                        }
                    }
                }

                if(isset($planEntityDetails['price_usd'])){
                    $planDetails['price_usd'] = $planEntityDetails['price_usd'];
                }else{
                    $planDetails['price_usd'] = 0;
                }

            } else {
                throw new \Exception('book id does not exists in core service.');
            }

        } catch (UserPlanEntitiesLimitationsException $ranoutException) {
            $ranoutResponse = [
                'entity_id' => (int) $entityId,
                'status' => 'ranout',
                'plan_details' => [
                    'reserve_days' => $userSubscriptionDetails->getRemainDaysAttribute(true),
                    'remain_entities' => $userSubscriptionDetails->getRemainEntitiesAttribute(),
                ],
                'has_reserved' => $hasReserved['has_reserved'],
                'is_logged' => 1
            ];
            return new JsonResponse(['data'=> $ranoutResponse], ResponseCode::HTTP_OK);
        } catch (\Exception $err) {
            Log::error('[SubscriptionUserHistoriesController][store] ' . $err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newUserHistory->entity_id = (int) $entityId;
        $newUserHistory->status = 'success';
        $newUserHistory->plan_details = $planDetails;
        $newUserHistory->has_reserved = $hasReserved['has_reserved'];
        $newUserHistory->is_logged = $userHistoryParameters['is_logged'];

        return new JsonResponse( ['data'=> $newUserHistory->toArray()] ,ResponseCode::HTTP_CREATED);
    }

    /**
     * store resources
     *
     * @param SubscriptionUserHistoriesRequest $request
     * @param int $userId
     * @param int $entityId
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function crm_assign_entity_to_user(SubscriptionUserHistoriesRequest $request, int $userId, int $entityId)
    {
        try {
            $inputs = $request->all();
            $entityQuery = SubscriptionUsers::getActivePlan($userId);

            if(count($entityQuery) == 0){
                throw new \Exception('The user does not have active plan.');
            }
            $planUserId = $entityQuery[0]->plan_id;

            // check entity id in plan
            $planEntityQuery = SubscriptionPlanEntities::findByPlanNEntity($planUserId ,$entityId);
            if(count($planEntityQuery) == 0){
                throw new \Exception('The entity with this plan id does not exists');
            }
            $planEntityId = $planEntityQuery[0]->id;

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
                'json' => [ 'book_ids' => [ $entityId ] ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);
            if( is_array($entityResult['output']) & is_array($entityResult['output']['books']) ) {
                $entityInfo = $entityResult['output']['books'][0];
                $entityType = ($entityInfo['format'] == 'AUDIO') ? 'audio' : 'book';

                $entity = SubscriptionEntities::query()->where('entity_id', '=', $entityId)->firstOrFail()->toArray();
                $entityInfo['price_factor'] = $entity['price_factor'];
                // $entityInfo['publisher_share'] = $entity['publisher_share'];
                $entityInfo['publisher_share'] = $entityInfo['publisher_marketshare'];
                $entityInfo['entity_id'] = $entityId;
                $entityInfo['id'] = $entity['id'];

                $planEntityDetails = SubscriptionPlans::find($planUserId);

                $setLogged = SubscriptionUserHistories::query()
                    ->where('subscription_user_id','=',$entityQuery[0]->id)
                    ->where('entity_id','=',$entityId)
                    ->where('is_logged','=','1')
                    ->get();

                $userHistoryParameters = [
                    'subscription_user_id' => $entityQuery[0]->id,
                    'subscription_plan_entity_id' => $planEntityId,
                    'entity_id' => $entityId,
                    'start_date' => Carbon::now()->toDateTimeLocalString(),
                    'subscription_entity_details' => $entityInfo,
                    'operator_id' => $request->get('operator_id', 0),
                    'update_reason' => $request->get('update_reason', 0)
                ];

                if ($setLogged->count() == 0) {
                    $userHistoryParameters['is_logged'] = 1;
                } else {
                    $userHistoryParameters['is_logged'] = 0;
                }

                // TODO add entity to user bookshelf from shuttle panel
//                $guzzle = new Client();
//                $userBookResponse = $guzzle->post('https://papi.fidibo.com/insert/user/book',[
//                    'json' => [ 'user_id' => $userId, 'book_id' => $entityId, 'access_key' => env("PAPI_ACCESS_KEY")]
//                ]);
//                if ($userBookResponse->getStatusCode() != 200) {
//                    throw new \Exception('user book creation failed');
//                }

                // set enddate of other entities
                $updateOrFail = SubscriptionUserHistories::updateUserEndDates($entityQuery[0]->id,$entityType,$entityId);
                if($updateOrFail->count() > 0){
                    Log::channel('gelf')->error('[SubscriptionUserHistoriesController][store]: end date of user has been sets for content type .',['user_id' => $userId,'entity_type' => $entityType]);
                }

                $isAddedToHistoryBefore = SubscriptionUserHistories::query()
                    ->orderByDesc('start_date')
                    ->where('subscription_user_id','=',$entityQuery[0]->id)
                    ->where('entity_id','=',$entityId)
                    ->whereNull('end_date')
                    ->get();

                if ($isAddedToHistoryBefore->count() > 0) {
                    $subscriptionHistoryUserId = $isAddedToHistoryBefore[0]->id;
                    unset($userHistoryParameters['start_date']);
                    SubscriptionUserHistories::where('id','=',$subscriptionHistoryUserId)
                        ->update($userHistoryParameters);
                    $newUserHistory = SubscriptionUserHistories::find($subscriptionHistoryUserId);
                } else {
                    $newUserHistory = SubscriptionUserHistories::create($userHistoryParameters);
                }

                $userSubscriptionDetails = SubscriptionUsers::find($entityQuery[0]->id);

                $startDate = $userSubscriptionDetails->start_date;
                $endDate = $userSubscriptionDetails->end_date;
                $shamsiStartDate = Jalalian::forge($startDate)->format('%Y %B %d');
                $shamsiEndDate = Jalalian::forge($endDate)->format('%Y %B %d');

                $planDetails = ['id' => $planEntityDetails->id, 'start_date' => $userSubscriptionDetails->start_date, 'end_date' => $userSubscriptionDetails->end_date, 'title' => $planEntityDetails->title,
                    'max_books' => $userSubscriptionDetails['plan_details']['remain_books'], 'max_audios' => $userSubscriptionDetails['plan_details']['remain_audios'],
                    'duration' => $planEntityDetails->duration, 'price' => $planEntityDetails->price,
                    'discount_price' => 0, 'remain_days' => $userSubscriptionDetails->getRemainDaysAttribute(), 'remain_books' => $userSubscriptionDetails->getRemainBooksAttribute(),
                    'remain_audios' => $userSubscriptionDetails->getRemainAudiosAttribute(),'shamsi_start_date' => $shamsiStartDate, 'shamsi_end_date' => $shamsiEndDate];
                // planDetails

                $lastEntities = SubscriptionUsers::getUserLastEntities($userId);
                if(!in_array($entityId ,$lastEntities['all'])){
                    if($entityInfo['format'] == 'AUDIO'){
                        if($userSubscriptionDetails->remain_audios <= 0){
                            return new JsonResponse( ['data'=> ['status' => 'ranout','type' => 'audio','plan_details' => $planDetails]] ,ResponseCode::HTTP_OK);
                        }
                    } else {
                        if($userSubscriptionDetails->remain_books <= 0){
                            return new JsonResponse( ['data'=> ['status' => 'ranout','type' => 'book','plan_details' => $planDetails]] ,ResponseCode::HTTP_OK);
                        }
                    }
                }

                if(isset($planEntityDetails['price_usd'])){
                    $planDetails['price_usd'] = $planEntityDetails['price_usd'];
                }else{
                    $planDetails['price_usd'] = 0;
                }

            } else {
                throw new \Exception('book id does not exists in core service.');
            }

        } catch (\Exception $err) {
            Log::error('[SubscriptionUserHistoriesController][store] ' . $err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $newUserHistory->entity_id = (int) $entityId;
        $newUserHistory->status = 'success';
        $newUserHistory->plan_details = $planDetails;
        $newUserHistory->is_logged = $userHistoryParameters['is_logged'];

        return new JsonResponse( ['data'=> $newUserHistory->toArray()] ,ResponseCode::HTTP_CREATED);
    }

    /**
     * get specific subscription user history by $id
     * @param int $id
     * @param int $userId
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id, int $userId)
    {
        try {
            $userPlan = SubscriptionUsers::getActivePlan($userId);
            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][index]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userHistory = SubscriptionUserHistories::query()
                ->where([
                    'id' => $id,
                    'subscription_user_id' => $userPlan[0]->id,
                ])->firstOrFail();
            return new JsonResponse( ['data' => $userHistory] ,ResponseCode::HTTP_OK);

        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][show]: Something went wrong in getting the data', [
                'error' => $err->getMessage()
            ]);
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]],
                ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * update specific subscription user history by id
     *
     * @param SubscriptionUserHistoriesRequest $request
     * @param int $userId
     * @param  int $entityId
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionUserHistoriesRequest $request, int $userId, int $entityId)
    {
        try {
            $userPlan = SubscriptionUsers::getActivePlan($userId);
            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][index]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userHistories = SubscriptionUserHistories::query()
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_PLAN_ENTITIES.".id", '=', Tables::SUBSCRIPTION_USER_HISTORIES.".subscription_plan_entity_id")
                ->where([
                    Tables::SUBSCRIPTION_USER_HISTORIES.'.subscription_user_id' => $userPlan[0]->id,
                    Tables::SUBSCRIPTION_USER_HISTORIES.'.entity_id' => $entityId,
                    Tables::SUBSCRIPTION_PLAN_ENTITIES.'.plan_id' => $userPlan[0]->plan_id
                ])->select(Tables::SUBSCRIPTION_USER_HISTORIES.'.*')->get();

            foreach ($userHistories as $userHistory) {
                if (!isset($userHistory->end_date)) {
                    $userHistory->update($request->only(['read_percent_start', 'read_percent_end']));
                    Log::channel('gelf')->info('[SubscriptionUserHistoriesController][update] subscription user history has been updated id:', ['user_history' => $userHistory]);
                    return new JsonResponse(['data' => $userHistory],ResponseCode::HTTP_OK );
                }
            }

            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][update] Nothing to update. Nothing on the table.', ['user_histories' => $userHistories->pluck('id')]);
            return new JsonResponse(['data'=> ['status' => 'failed','message' => 'there is no content on the user table to be updated']],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $err){
            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][show] ', ['error' => $err->getMessage()]);
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * remove specific subscription user history by id
     * @param int $id
     * @param int $userId
     * @param int $entityId
     * @return JsonResponse
     * Test ?
     * @throws \Throwable
     */
    public function remove_from_library(int $userId, int $entityId)
    {
        $beforeUserHistories = [];
        $userHistories = [];
        $counter = 0;
        try {
            $userPlan = SubscriptionUsers::getActivePlan($userId);

            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][remove_from_library]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userHistories = SubscriptionUserHistories::query()->where([
                'subscription_user_id' => $userPlan[0]->id,
                'entity_id' => $entityId,
                'is_hide_from_list' => 0
            ])->get();
            if ( count($userHistories) == 0 ) {
                throw new \Exception("This entity is not inside the user library to be removed.");
            }

            foreach ($userHistories as $userEntityHistory) {
                array_push($beforeUserHistories, $userEntityHistory);
                $userEntityHistory->end_date = Carbon::now()->toDateTimeLocalString();
                $userEntityHistory->is_hide_from_list = 1;
                $userEntityHistory->saveOrFail();
                $counter++;
            }
        } catch (\Exception $err) {
            if (count($beforeUserHistories) > 0) {
                foreach ($beforeUserHistories as $beforeUserHistory) {
                    $beforeUserHistory->saveOrFail();
                }
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][remove]: Something went wrong in deletion of following user history', [
                    'user_history' => $beforeUserHistories[$counter],
                    'successful_removes_draw_backed' => $userHistories,
                    'error' => $err->getMessage()
                ]);
            }

            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][remove]: Unexpected Error happened', [
                'user_id' => $userId,
                'entity_id' => $entityId,
                'error' => $err->getMessage()
            ]);
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionUserHistoriesController][remove]: User removed entity from its library.', [
            'user_id' => $userId,
            'entity_id' => $entityId
        ]);
        return new JsonResponse(['data' => $userHistories],ResponseCode::HTTP_OK );
    }

    public function crm_delete_entity_from_library (SubscriptionUserHistoriesRequest $request, int $userId, int $entityId) {
        $beforeUserHistories = [];
        $userHistories = [];
        $counter = 0;

        try {
            $userPlan = SubscriptionUsers::getUserActivePlan($userId);
            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][crm_delete_entity_from_library]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            $userHistories = SubscriptionUserHistories::query()->where([
                'subscription_user_id' => $userPlan[0]->id,
                'entity_id' => $entityId
            ])->get();

            foreach ($userHistories as $userEntityHistory) {
                array_push($beforeUserHistories, $userEntityHistory);
                $userEntityHistory->operator_id = $request->get('operator_id', 0);
                $userEntityHistory->saveOrFail();
                $userEntityHistory->delete();
                $counter++;
            }


        } catch (\Exception $err) {
            if (count($beforeUserHistories) > 0) {
                foreach ($beforeUserHistories as $beforeUserHistory) {
                    $beforeUserHistory->saveOrFail();
                }
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][crm_delete_entity_from_library]: Something went wrong in deletion of following user history', [
                    'user_history' => $beforeUserHistories[$counter],
                    'successful_removes_draw_backed' => $userHistories,
                    'error' => $err->getMessage()
                ]);
            }

            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][crm_delete_entity_from_library]: Unexpected Error happened', [
                'user_id' => $userId,
                'entity_id' => $entityId,
                'error' => $err->getMessage()
            ]);
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionUserHistoriesController][crm_delete_entity_from_library]: User removed entity from its library.', [
            'user_id' => $userId,
            'entity_id' => $entityId
        ]);
        return new JsonResponse(['data' => $userHistories],ResponseCode::HTTP_OK );
    }

    /**
     * @param SubscriptionUserHistoriesRequest $request
     * @param int $userId
     * @return JsonResponse
     * @throws \Throwable
     */
    public function addToBoughtHistory(SubscriptionUserHistoriesRequest $request,int $userId)
    {
        try {
            $inputs = $request->all();
            $entityArray = $inputs['entity_id'];

            $userPlan = SubscriptionUsers::getActivePlan($userId);
            if (count($userPlan) == 0){
                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][addToBaughtHistory] UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId,'class' => __CLASS__]);
                return new JsonResponse(['data'=>['status' => 'failed','message' => "UserID doesn\'t exist in FidiPlus or doesn\t have any active plan."]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
            }

            foreach ($entityArray as $entityId) {
                $entity = SubscriptionEntities::query()->where([
                    'entity_id' => $entityId
                ])->get();

                if($entity->count() == 0){
                    Log::channel('gelf')->error('[SubscriptionUserHistoriesController][addToBaughtHistory] the entity id does not exists in subscription entities',['user_id' => $userId,'class' => __CLASS__]);
                    return new JsonResponse(['data'=>['status' => 'failed','message' => '[SubscriptionUserHistoriesController][addToBaughtHistory]: the entity id does not exists in subscription entities']],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
                }

                $boughtHistory = new SubscriptionBoughtHistories();
                $boughtHistory->user_id = $userId;
                $boughtHistory->entity_id = $entityId;
                $boughtHistory->saveOrFail();

                Log::channel('gelf')->error('[SubscriptionUserHistoriesController][addToBaughtHistory] the entity id does not exists in subscription entities',['user_id' => $userId,'class' => __CLASS__]);
            }
            $entities = SubscriptionUserHistories::getUserOnTheTableEntities($userPlan[0]->id);
            foreach ($entities as $entity) {
                if (in_array($entity['entity_id'], $entityArray)) {
                    $entity['end_date'] = Carbon::now()->toDateTimeLocalString();
                    $entity->save();
                }
            }

            return new JsonResponse(['data'=>['status' => 'success','message' => 'the bought history has been added']],ResponseCode::HTTP_OK);
        } catch (\Exception $exception){
            Log::channel('gelf')->error('[SubscriptionUserHistoriesController][addToBaughtHistory]: there is an exception:' . $exception->getMessage(),['user_id' => $userId,'class' => __CLASS__]);
            return new JsonResponse(['data'=>['status' => 'failed','message' => '[SubscriptionUserHistoriesController][addToBaughtHistory]: there is an exception:' . $exception->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
