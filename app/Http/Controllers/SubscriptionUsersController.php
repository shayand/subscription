<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Constants\UpdateReasons;
use App\Http\Requests\SubscriptionUsersRequest;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUserLogs;
use App\Models\SubscriptionUserPlanAssignments;
use App\Models\SubscriptionUsers;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Optimus\Bruno\EloquentBuilderTrait;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

/**
 * Class SubscriptionUsersController
 * @package App\Http\Controllers
 */
class SubscriptionUsersController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * Instantiate a new SubscriptionEntitiesController instance.
     */
    public function __construct()
    {
        $this->middleware('shuttle_auth', ['only' => ['store', 'bulk_assign_plan_users']]);
        parent::__construct();
    }

    /**
     * list resourcesList of Subscription Plans Panel
     *
     * @param int $planId
     * @return JsonResponse
     * Test ?
     */
    public function index(int $planId): JsonResponse
    {
        try {
            SubscriptionPlans::query()->findOrFail($planId);
            $queryResource = SubscriptionUsers::query()->where(['plan_id' => $planId]);
            $total = $queryResource->count();

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $list = $queryResource->get();
            $parsedData = $this->parseData($list, $resourceOptions);

            Log::info('[SubscriptionUsersController][index] the subscription users has been listed. plan id:' . $planId);

            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response );
        }catch (\Exception $exception){
            Log::error('[SubscriptionUsersController][index] ' . $exception->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $exception->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    /**
     * store resources
     *
     * @param SubscriptionUsersRequest $request
     * @param int $planId
     * @return JsonResponse
     * @throws \Throwable
     */
    public function store(SubscriptionUsersRequest $request)
    {
        try {
            $inputData = $request->all();
            $planId = $inputData['plan_id'];

            $plan = SubscriptionPlans::query()->findOrFail($planId);
            if ($plan->status != 1) {
                throw new \Exception("Deactivated plans could not be assigned to users. Please choose the right plan for assignment");
            }

            $currentPlans = SubscriptionUsers::getLastActivePlan($inputData['user_id']);
            if($currentPlans == false){
                $startDate = new \DateTime();
            } else {
                $startDate = date('Y-m-d H:i:s',strtotime($currentPlans['end_date'] . '+1 minutes'));
            }

            $is_crm = 0;
            if ($request->get('operator_id', null) != null) {
                $is_crm = 1;
            }

            $subscriptionUsers = new SubscriptionUsers();
            $subscriptionUsers->user_id = $inputData['user_id'];
            $subscriptionUsers->plan_id = $inputData['plan_id'];
            $subscriptionUsers->assignment_title = $request->get('assignment_title', null);
            $subscriptionUsers->update_reason = $request->get('update_reason', null);
            $subscriptionUsers->operator_id = $request->get("operator_id", 0);
            $subscriptionUsers->is_crm = $is_crm;
            $subscriptionUsers->start_date = $startDate;
            $subscriptionUsers->saveOrFail();

            return new JsonResponse( ['data'=>$subscriptionUsers] ,ResponseCode::HTTP_CREATED);

        } catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][store] ' . $err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * get specific subscription user by $id
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $planId, int $id)
    {
        try{
            $subscriptionUser = SubscriptionUsers::query()
                ->where(['plan_id' => $planId,'id' => $id])->get();

            if($subscriptionUser->count() == 0){
                throw new \Exception('Not found any records');
            }
        } catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][show] ' . $err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionUsersController][show] user plan view has been showed plan id:' . $planId);

        return new JsonResponse( ['data' => $subscriptionUser] ,ResponseCode::HTTP_OK);
    }

    /**
     * remove specific subscription user by id
     * @param int $planId
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $planId, int $id)
    {
        try{
            SubscriptionUsers::query()->where(['plan_id' => $planId,'id' => $id])->delete();
        } catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][destroy] ' . $err->getMessage());
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionUsersController][destroy] subscription user has been removed. plan id:' . $planId . ' user plan id:' . $id );
        return new JsonResponse(['data' => ['status' => 'success' , 'message' => 'The plan has been removed.']],ResponseCode::HTTP_OK);
    }

    /**
     * @param int $userId
     * @return JsonResponse
     * @throws \Exception
     */
    public function checkUserIsSubscribed(int $userId)
    {
        Log::channel('gelf')->error('[SubscriptionUserHistoriesController][index]: UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId]);
        try {
            $entityQuery = SubscriptionUsers::withTrashed()
                ->where(['user_id' => $userId])->get();

            foreach ($entityQuery as $singleUserPlan) {
                $startDateTime = new \DateTime($singleUserPlan->start_date, new \DateTimeZone('Asia/Tehran'));
                $startDate = $singleUserPlan->start_date;
                $duration = $singleUserPlan->duration;
                $endDateTime = new \DateTime($singleUserPlan->start_date, new \DateTimeZone('Asia/Tehran'));
                $endDateTime->add( new \DateInterval( sprintf("PT%dM", $duration) ) );
                $endDate = date('Y-m-d', strtotime($startDate . ' +' . $duration . 'minutes'));

                $currentTime = new \DateTime(now(), new \DateTimeZone('Asia/Tehran'));

                $planDetails = $singleUserPlan->plan_details;
                $shamsiStartDate = Jalalian::forge($startDateTime)->format('%d %B %Y');
                $shamsiEndDate = Jalalian::forge($endDateTime)->format('%d %B %Y');
                if ($startDateTime > $currentTime) {
                    // future plans
                    $final['future'][] = ['id' => $singleUserPlan->plan_id,'subscription_user_id' => $singleUserPlan->id, 'start_date' => $startDate, 'end_date' => $endDate, 'title' => $planDetails['title'],
                        'max_entities' => $planDetails['max_books'] + $planDetails['max_audios'],
                        'duration' => $planDetails['duration'], 'price' => $planDetails['price'], 'shamsi_start_date' => $shamsiStartDate,
                        'discount_price' => 0, 'remain_days' => $planDetails['duration'],
                        'remain_entities' => $planDetails['max_books'] + $planDetails['max_audios'],
                        'shamsi_start_date' => $shamsiStartDate, 'shamsi_end_date' => $shamsiEndDate];
                } elseif ($startDateTime <= $currentTime & $endDateTime > $currentTime) {
                    // active plans
                    $userCurrentPlan = SubscriptionUsers::getActivePlan($userId);
                    if($userCurrentPlan->count() > 0){
                        $userSubscriptionDetails = SubscriptionUsers::find($userCurrentPlan[0]['id']);

                        $final['active'][] = ['id' => $singleUserPlan->plan_id, 'subscription_user_id' => $singleUserPlan->id, 'start_date' => $startDate, 'end_date' => $endDate, 'title' => $planDetails['title'],
                            'max_entities' => $planDetails['max_books'] + $planDetails['max_audios'],
                            'duration' => $planDetails['duration'], 'price' => $planDetails['price'],
                            'discount_price' => 0, 'remain_days' => $userSubscriptionDetails->remain_days,
                            'remain_entities' => $userSubscriptionDetails->remain_books + $userSubscriptionDetails->remain_audios,
                            'shamsi_start_date' => $shamsiStartDate, 'shamsi_end_date' => $shamsiEndDate];
                    }else{
                        $final['active'][] = [];
                    }
                } else {
                    // passed plans
                    $final['passed'][] = ['id' => $singleUserPlan->plan_id,'subscription_user_id' => $singleUserPlan->id, 'start_date' => $startDate, 'end_date' => $endDate, 'title' => $planDetails['title'],
                        'max_books' => $planDetails['max_books'] + $planDetails['max_audios'],
                        'duration' => $planDetails['duration'], 'price' => $planDetails['price'], 'shamsi_start_date' => $shamsiStartDate,
                        'discount_price' => 0, 'remain_days' => 0,
                        'remain_entities' => $planDetails['max_books'] + $planDetails['max_audios'],
                        'shamsi_end_date' => $shamsiEndDate];
                }
            }

            if( isset($final['active']) ){
                if (count($final['active']) > 1) {
                    throw new \Exception('Two active subscriptions plan at same time');
                }
                $active = $final['active'][0];

                $logs = SubscriptionUserLogs::where('subscription_user_id',$active['subscription_user_id'])
                    ->with('subscriptionUserHistory')
                    ->get();

                if(!array_key_exists('passed',$final)){
                    $final['passed'] = [];
                }

                if(!array_key_exists('future',$final)) {
                    $final['future'] = [];
                }

                $data = [
                    'status' => 'success',
                    'subscription' => $active,
                    'history' => $final,
                    'capacities' => SubscriptionUsers::calculateUserCapacity($userId),
                ];
            } else {
                $final['active'] = [];
                $final['future'] = [];
                $data = [
                    'status' => 'empty',
                    'subscription' => null,
                    'history' => $final,
                    'capacities' => ['audios' => 0, 'books' => 0],
                ];
            }

            Log::info('[SubscriptionUsersController][checkUserIsSubscribed] user id:' . $userId);
            return new JsonResponse(['data' => $data], ResponseCode::HTTP_OK);
        } catch (\Exception $exception){
            Log::error('[SubscriptionUsersController][checkUserIsSubscribed] ' . $exception->getMessage());
            return new JsonResponse(['data'=>['status' => 'failed','message' => $exception->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param int $userId
     * @return JsonResponse
     * @throws \Exception
     */
    public function getUserSubscriptionStatus(int $userId)
    {
        $cacheKey = 'subscription_user_data_' . $userId;
        $cachedData = Cache::get($cacheKey);
//        if( $cachedData == null ){
            $planEntityDetails = SubscriptionUsers::getActivePlan($userId);
            $isSubscriptionForEver = SubscriptionUsers::getIsSubscribedEver($userId);

            if ((!isset($planEntityDetails[0])) || $planEntityDetails[0] == null) {
                $planEntityDetails = SubscriptionUsers::getLastPlan($userId);
            } else {
                $planEntityDetails = $planEntityDetails[0];
            }
            if ($planEntityDetails == null) {
                Log::channel('gelf')->error('[SubscriptionUsersController][getUserSubscriptionStatus] UserID doesn\'t have any active plan or does not exist.',['user_id' => $userId,'class' => __CLASS__]);
                $returnData = ['data'=>['subscription_check' => 'none' ,'ever_subscribed' => $isSubscriptionForEver , 'status' => 'failed']];
                // @TODO: this caused error for new registered users
                // cache user data until midnight
                //Cache::put($cacheKey,$returnData,strtotime('tomorrow') - time());
                return new JsonResponse($returnData,ResponseCode::HTTP_OK);
            }

            if ($planEntityDetails->remain_days > 0) {
                $subscriptionStatus = 'active';
            }  else {
                $subscriptionStatus = 'expired';
            }
            $planJson = $planEntityDetails->plan_details;
            $userSubscriptionDetails = SubscriptionUsers::find($planEntityDetails->id);

            $startDate = $planEntityDetails->start_date;
            $duration = $planJson['duration'];
            $endDateTime = strtotime($startDate . ' +' . $duration . 'days');
            $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $duration . 'days'));
            $shamsiStartDate = Jalalian::forge($startDate)->format('%Y %B %d');
            $shamsiEndDate = Jalalian::forge($endDateTime)->format('%Y %B %d');

            $remainEntities = $userSubscriptionDetails->getRemainEntitiesAttribute() + $userSubscriptionDetails->getRemainEntitiesReserveAttribute();

            $planDetails = [
                'id' => $userSubscriptionDetails->plan_id,
                'start_date' => $userSubscriptionDetails->start_date,
                'end_date' => $endDate,
                'title' => $planJson['title'],
                'max_entities' => $planJson['max_entities'],
                'duration' => $duration,
                'price' => $planJson['price'],
                'discount_price' => 0,
                'remain_days' => $userSubscriptionDetails->getRemainDaysAttribute(),
                'reserve_days' => $userSubscriptionDetails->getRemainDaysAttribute(true),
                'remain_entities' => $remainEntities,
                'reserve_entities' => $userSubscriptionDetails->getRemainEntitiesReserveAttribute(),
                'shamsi_start_date' => $shamsiStartDate,
                'shamsi_end_date' => $shamsiEndDate
            ];
            if(isset($planJson['price_usd'])){
                $planDetails['price_usd'] = $planJson['price_usd'];
            }else{
                $planDetails['price_usd'] = 0;
            }

            Log::info('[SubscriptionUsersController][checkUserIsSubscribed] user id:' . $userId);
            $returnData = ['subscription_check' => $subscriptionStatus, 'status' => 'success','subscription' => $planDetails,'ever_subscribed' => $isSubscriptionForEver];
            Cache::put($cacheKey,$returnData,$this->_calculateCacheEndDate($startDate));
            return new JsonResponse(['data' => $returnData], ResponseCode::HTTP_OK);
//        }else{
//            return new JsonResponse(['data' => $cachedData], ResponseCode::HTTP_OK);
//        }
    }

    public function bulk_assign_plan_users (SubscriptionUsersRequest $request) {
        try {
            $planId = $request->get("plan_id", null);
            if ($planId == null) {
                throw new \Exception("plan id is required.");
            }

            $plan = SubscriptionPlans::query()->findOrFail($planId);
            if ($plan->status != 1) {
                throw new \Exception("Deactivated plans could not be assigned to users. Please choose the right plan for assignment");
            }

            $userIds = $request->get('user_id', null);

            $userIdsArr = explode(",", $userIds);
            // TODO validate users by Papi
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                'json' => [ 'user_id' => $userIdsArr, 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $result = json_decode($response->getBody()->getContents(),true);
            $validUserIDs = array_keys($result);

            $invalidIDs = array_diff($userIdsArr, $validUserIDs);

            $planAssignment = SubscriptionUserPlanAssignments::create([
                'operator_id' => $request->get("operator_id", 0),
                'number_of_ids' => count($userIdsArr),
                'invalid_ids' => $invalidIDs,
                'all_ids' => $userIdsArr,
                'assignment_reason' => $request->get("update_reason", 0),
                'assignment_title' => $request->get("assignment_title", ""),
                'subscription_plan_id' => $plan->id
            ]);

            $createdUsers = [];
            $insertedIDs = [];
            $failedIDs = [];
            foreach ($validUserIDs as $userId) {
                try {
                    $userPlan = SubscriptionUsers::getLastActivePlan($userId);

                    if($userPlan == false){
                        $startDate = new \DateTime();
                    } else {
                        $startDate = date('Y-m-d H:i:s',strtotime($userPlan['end_date'] . '+1 minutes'));
                    }

                    $subscriptionUser = [
                        'user_id' => $userId,
                        'plan_id' => $plan->id,
                        'start_date' =>  $startDate,
                        'duration' => $plan->duration,
                        'operator_id' => $request->get("operator_id", 0),
                        'update_reason' => $request->get("update_reason", 0),
                        'assignment_title' => $request->get("assignment_title", ""),
                        'subscription_assignment_id' => $planAssignment->id
                    ];

                    $userPlan = SubscriptionUsers::create($subscriptionUser);
                    array_push($createdUsers, $userPlan);
                    array_push($insertedIDs, $userId);

                    $planAssignment->inserted_ids = $insertedIDs;
                    $planAssignment->save();
                } catch (\Exception $err) {
                    $failed = ['id' => $userId, 'err' => $err->getMessage()];
                    array_push($failedIDs, $failed);
                }
            }
        } catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][bulk_assign_plan_users] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = [
            'inserted_entities' => $createdUsers,
            'inserted_ids' => $insertedIDs,
            'failed_ids' => $failedIDs,
            'invalid_ids' => $invalidIDs
        ];
        $data['inserted_ID_numbers'] = count($insertedIDs);
        $data['invalid_ID_numbers'] = count($failedIDs);
        Log::info('[SubscriptionUsersController][bulk_assign_plan_users] the subscription users has been stored');
        return new JsonResponse(['data'=> $data ],ResponseCode::HTTP_CREATED );

    }

    public function bulk_assignment_list(SubscriptionUsersRequest $request) {
        try {
            $filter = $request->only(['filter']);
            if($filter != null) {
                $filter = json_decode($filter["filter"], true);
            }

            $queryResource = SubscriptionUserPlanAssignments::query()
                ->join(Tables::SUBSCRIPTION_PLANS, Tables::SUBSCRIPTION_PLANS.'.id', '=', Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS.'.subscription_plan_id');
            $queryResource = SubscriptionPlans::applyPlanTitleFilter($queryResource, $filter);

            if (array_key_exists("date_filter_start", $filter)) {
                $queryResource->where(Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS.'.created_at', '>=', $filter["date_filter_start"]);
            }

            if (array_key_exists("date_filter_end", $filter)) {
                $queryResource->where(Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS.'.created_at', '<=', $filter["date_filter_end"]);
            }

            $operatorQuery = clone($queryResource);
            $operatorIDs = $operatorQuery->pluck(Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS.".operator_id");

            if ( count($operatorIDs) == 0 ) {
                $resourceOptions = $this->parseResourceOptions();
                $response = ['total' => 0, 'per_page' => $resourceOptions['limit'], 'data' => []];
                return new JsonResponse( $response );
            }

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                'json' => [ 'user_id' => $operatorIDs, 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $result = json_decode($response->getBody()->getContents(),true);

            $totalQuery = clone($queryResource);
            $total = $totalQuery->count();

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);

            $assignments = $queryResource->select([
                Tables::SUBSCRIPTION_PLANS.'.title',
                Tables::SUBSCRIPTION_USER_PLAN_ASSIGNMENTS.'.*'
            ])->get()->each(function ($item) use ($result) {
                if (array_key_exists($item->operator_id, $result)) {
                    $item->operator_name = $result[''.$item->operator_id];
                } else {
                    $item->operator_name = $item->operator_id;
                }

                if (array_key_exists($item->assignment_reason, UpdateReasons::UPDATE_REASONS)) {
                    $item->assignment_reason = UpdateReasons::UPDATE_REASONS[$item->assignment_reason];
                } else {
                    $item->assignment_reason = "Unknown Reason";
                }
            });

            $parsedData = $this->parseData($assignments, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response );
        } catch (\Exception $err){
            Log::error('[SubscriptionUsersController][bulk_assignment_list] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function list_users_CRM (SubscriptionUsersRequest $request) {
        try {
            $filter = $request->only(['filter']);
            if($filter != null) {
                $filter = json_decode($filter["filter"], true);
            }

            $lastPlans = SubscriptionUsers::query()
                ->selectRaw("MAX(created_at) as created_at")
                ->whereRaw(Tables::SUBSCRIPTION_USERS.'.start_date <= DATE_FORMAT(CONVERT_TZ(UTC_TIMESTAMP(), "UTC", "Asia/Tehran"), "%Y-%m-%d %T")')
//                ->whereRaw(Tables::SUBSCRIPTION_USERS.".start_date <= DATE_FORMAT(NOW(), '%Y-%m-%d %T')")
                ->groupBy('user_id')->pluck('created_at');

//            $users = SubscriptionUsers::query()
//                ->select(['user_id', 'created_at'])
//                ->whereIn('created_at', $lastPlans)->pluck('user_id');

            $queryResource = SubscriptionUsers::query()->orderBy('created_at', 'desc');
//            $queryResource = $queryResource->whereIn('user_id', $users)->whereIn('created_at', $lastPlans);
            $queryResource = SubscriptionUsers::applyCRMUserFilters($queryResource, $filter);

            $totalQuery = clone($queryResource);
            $total = $totalQuery->count();

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);

            $userIDsQuery = clone($queryResource);
            $userIDs = $userIDsQuery->pluck('user_id');
            $entityResult = [];
            if (count($userIDs) != 0) {
                $guzzle = new Client();
                $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                    'json' => [ 'user_id' => $userIDs, 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);
                $entityResult = json_decode($response->getBody()->getContents(),true);
            }

            $subscriptionUsers = $queryResource->get()->each(function ($item) use ($entityResult) {
                if (isset($entityResult[$item->user_id])) {
                    $item->username = $entityResult[$item->user_id]["username"];
                } else {
                    $item->username = "";
                }
            });
            $parsedData = $this->parseData($subscriptionUsers, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response );
        }catch (\Exception $err){
            Log::error('[SubscriptionUsersController][list_users_CRM] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_active_plan ($userId) {
        try {
            $activePlan = SubscriptionUsers::getActivePlan($userId);
            if (count($activePlan) == 0) {
                throw new \Exception("This user has no active plans");
            }
            $response = ['user_plan' => $activePlan];
            return new JsonResponse( $response );
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_active_plan] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_active_plan_contents($userId) {
        try {
            $activePlan = SubscriptionUsers::getActivePlan($userId);
            if (count($activePlan) == 0) {
                throw new \Exception("No Such User with active_plan");
            }
            $res = SubscriptionUserHistories::get_crm_user_plan_contents($activePlan[0]);
            $planContents = $this->get_crm_user_plan_contents($res['contents_query']);

            $response = [
                'total' => $res['total'],
                'per_page' => $planContents['per_page'],
                'contents' => $planContents['plan_contents']
            ];
            return new JsonResponse( $response);
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_active_plan_contents] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_active_plan_histories ($userId) {
        try {
            $activePlan = SubscriptionUsers::getActivePlan($userId);
            if (count($activePlan) == 0) {
                throw new \Exception("No Such User with active_plan");
            }

            $res = SubscriptionUserHistories::get_crm_user_plan_histories($activePlan[0]);

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($res['history_query'],$resourceOptions);
            $entitiesHistories = $res['history_query']->get()->each(function($item) {
                $item['read_percent_end'] = ($item['read_percent_end'] != null) ? $item['read_percent_end']:0;
                $item['read_percent'] = max($item['read_percent_end'], $item['read_percent_start']);
            });

            $response = ['total' => $res['total'], 'per_page' => $resourceOptions['limit'], 'histories' => $entitiesHistories];
            return new JsonResponse( $response );
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_active_plan_histories] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_passed_plans ($userId) {
        try {
            $passedPlans = SubscriptionUsers::getPassedPlans($userId);
            if (count($passedPlans) == 0) {
                throw new \Exception("This user has no passed plans");
            }

            $response = ['user_plan' => $passedPlans];
            return new JsonResponse( $response );
        } catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_passed_plans] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_passed_plan_contents($userId, $planId) {
        try {
            $passedPlan = SubscriptionUsers::getPassedPlan($userId, $planId);
            if (count($passedPlan) == 0) {
                throw new \Exception("This user had no such plan");
            }
            $res = SubscriptionUserHistories::get_crm_user_plan_contents($passedPlan[0]);
            $planContents = $this->get_crm_user_plan_contents($res['contents_query']);

            $response = [
                'total' => $res['total'],
                'per_page' => $planContents['per_page'],
                'contents' => $planContents['plan_contents']
            ];
            return new JsonResponse($response);
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_passed_plan_contents] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_passed_plan_histories($userId, $planId) {
        try {
            $passedPlan = SubscriptionUsers::getPassedPlan($userId, $planId);
            if (count($passedPlan) == 0) {
                throw new \Exception("No Such User with active_plan");
            }

            $res = SubscriptionUserHistories::get_crm_user_plan_histories($passedPlan[0]);

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($res['history_query'],$resourceOptions);
            $entitiesHistories = $res['history_query']->get()->each(function($item) {
                $item['read_percent_end'] = ($item['read_percent_end'] != null) ? $item['read_percent_end']:0;
                $item['read_percent'] = max($item['read_percent_end'], $item['read_percent_start']);
            });

            $response = ['total' => $res['total'], 'per_page' => $resourceOptions['limit'], 'histories' => $entitiesHistories];
            return new JsonResponse( $response );
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_passed_plan_histories] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function get_crm_user_plan_contents ($query) {
        $resourceOptions = $this->parseResourceOptions();
        $this->applyResourceOptions($query,$resourceOptions);

        try {
            $planContents = $query->get()->each( function ($history) {
//                $min_read = min($history->min_read_start, $history->min_read_end);
                $max_read = max($history->max_read_start, $history->max_read_end);
                $history->read_percent = $max_read;// - $min_read;
            })->makeHidden([
                'max_read_start',
                'max_read_end',
                'min_read_start',
                'min_read_end',
                'time_on_the_table'
            ]);
            return [
                'plan_contents' => $planContents,
                'per_page' => intval($resourceOptions['limit'])
            ];
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public function get_user_future_plans($userId) {
        try {
            $futurePlans = SubscriptionUsers::getFuturePlans($userId);
            if (count($futurePlans) == 0) {
                throw new \Exception("This user has no future plans");
            }
            $response = ['user_plan' => $futurePlans];
            return new JsonResponse( $response );
        }catch (\Exception $err) {
            Log::error('[SubscriptionUsersController][get_user_future_plans] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_user_changes(SubscriptionUsersRequest $request) {
        try {
            $filter = $request->only(['filter']);
            if($filter != null) {
                $filter = json_decode($filter["filter"], true);
            }

            $query = SubscriptionUsers::query()->whereNotNull('operator_id')
                ->where('is_crm', '=', 1);

            $query = SubscriptionUsers::applyUserInfoFilter(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS, $query, $filter);
            if (array_key_exists("date_filter_start", $filter)) {
                $query->where(Tables::SUBSCRIPTION_USERS.'.created_at', '>=', $filter["date_filter_start"]);
            }

            if (array_key_exists("date_filter_end", $filter)) {
                $query->where(Tables::SUBSCRIPTION_USERS.'.created_at', '<=', $filter["date_filter_end"]);
            }

            $totalQuery = clone($query);
            $IDsQuery = clone($query);
            $operatorIDsQuery = clone($query);

            $userIDs = $IDsQuery->pluck('user_id')->toArray();
            $operatorIDs = $operatorIDsQuery->pluck('operator_id')->toArray();
            $IDs = array_merge($userIDs, $operatorIDs);
            $entityResult = [];

            if (count($userIDs) != 0) {
                $guzzle = new Client();
                $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                    'json' => [ 'user_id' => $IDs, 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);
                $entityResult = json_decode($response->getBody()->getContents(),true);
            }

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($query,$resourceOptions);
            $userChanges = $query->get()->each(function ($item) use ($entityResult) {
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
            });

            $response = ['total' => $totalQuery->count(), 'per_page' => $resourceOptions['limit'], 'data' => $userChanges];
            return new JsonResponse( $response );
        } catch (\Exception $err){
            Log::error('[SubscriptionUsersController][get_user_changes] ' . $err->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function get_assignment_reasons() {
        $response = UpdateReasons::UPDATE_REASONS;
        return new JsonResponse( $response );
    }

    /**
     * @param string $date
     * @return int
     */
    private function _calculateCacheEndDate(string $date)
    {
        $hoursMinutesStr = explode(' ',$date)[1];
        $today = date('Y-m-d ').$hoursMinutesStr;
        return strtotime($today.' -1 minutes') - time();
    }
}
