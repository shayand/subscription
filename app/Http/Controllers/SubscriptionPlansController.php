<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionPlansRequest;
use App\Models\SubscriptionPlans;
use Carbon\Carbon;
use App\Constants\Tables;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionPlansController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * Instantiate a new SubscriptionPlanEntitiesController  instance.
     */
    public function __construct()
    {
        $this->middleware('shuttle_auth', ['only' => ['store']]);
        parent::__construct();
    }

    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function panel_index(SubscriptionPlansRequest $request): JsonResponse
    {
        $filter = $request->get('filter', null);
        if ($filter != null) {
            $filter = json_decode($filter, true);
        }
        $queryResource = SubscriptionPlans::query();
        $queryResource = SubscriptionPlans::getFilteredPlans($queryResource, $filter)
            ->orderBy(Tables::SUBSCRIPTION_PLANS. '.created_at','DESC');

//        $total = SubscriptionPlans::select('id')->get()->count();
        $resourceOptions = $this->parseResourceOptions();
        $this->applyResourceOptions($queryResource,$resourceOptions);
        $list = $queryResource->get();
        $parsedData = $this->parseData($list, $resourceOptions);
        Log::info('[SubscriptionPlansController][index] the subscription plan has been listed');

        $response = ['total' => 149, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
        return new JsonResponse( $response );
    }

    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function index(): JsonResponse
    {
        $cachedData = Cache::get('subscription_plans_data');
        if( $cachedData == null ) {
            $queryResource = SubscriptionPlans::getActivePlans();
            $total = $queryResource->count();
            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $list = $queryResource->get();
            $list->makeHidden(['max_books','max_audios','remain_audios','remain_books']);
            $parsedData = $this->parseData($list, $resourceOptions);
            Log::info('[SubscriptionPlansController][index] the subscription plan has been listed');

            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            Cache::put('subscription_plans_data',$response,strtotime('tomorrow') - time());
        }else{
            $response = $cachedData;
        }

        return new JsonResponse( $response );
    }

    /**
     * store resources
     *
     * @param  SubscriptionPlansRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionPlansRequest $request)
    {
        try {
            $inputs = $request->all();
            $inputs['operator_id'] = $request->get('operator_id');
            if (!array_key_exists("start_date",$inputs)) {
                $inputs = array_add($inputs, 'start_date', Carbon::now()->toDateString());
//                $inputs = array_add($inputs, 'start_date', Carbon::now()->toDateString());
            }
            if (!array_key_exists("status",$inputs)) {
                $inputs = array_add($inputs, 'status', 2);
            }
            if (!array_key_exists("is_show",$inputs)) {
                $inputs = array_add($inputs, 'is_show', 0);
            }
            $plan = SubscriptionPlans::create($inputs);
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlansController][store] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
        Log::info('[SubscriptionPlansController][store] the subscription plan has been stored');
        return new JsonResponse(['data'=> $plan ],ResponseCode::HTTP_CREATED );
    }

    /**
     * get specific subscription plan by $id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id)
    {
        try{
            $plan = SubscriptionPlans::findOrFail($id);
        } catch (\Exception $err){
            Log::error('[SubscriptionPlansController][show] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionPlansController][show] the subscription plan has been showed');
        return new JsonResponse( ['data' => $plan] ,ResponseCode::HTTP_OK);
    }

    /**
     * update specific subscription plan by id
     *
     * @param SubscriptionPlansRequest $request
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionPlansRequest $request, int $id)
    {
        try {
            $plan = SubscriptionPlans::findOrFail($id);
            $plan->update($request->all());
        } catch (\Exception $err){
            Log::error('[SubscriptionPlansController][update] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::info('[SubscriptionPlansController][update] the subscription plan has been updated');
        return new JsonResponse(['data' => $plan],ResponseCode::HTTP_OK );
    }

    /**
     * remove specific subscription plan by id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $id)
    {
        try{
            SubscriptionPlans::query()->findOrFail($id)->delete();
        } catch (\Exception $err) {
            Log::error('[SubscriptionPlansController][destroy] throw exception');
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY );
        }

        Log::info('[SubscriptionPlansController][destroy] the subscription plan has been removed');
        return $this->response( ['data' => ['status' => 'success' , 'message' => 'The plan has been removed.']],ResponseCode::HTTP_OK);
    }
}
