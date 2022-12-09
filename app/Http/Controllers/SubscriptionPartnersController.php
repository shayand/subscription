<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionPartnersRequest;
use App\Models\SubscriptionPartners;
use App\Models\SubscriptionPartnersPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Optimus\Bruno\EloquentBuilderTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class SubscriptionPartnersController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * list resourcesList of Subscription Partners Panel
     *
     * @return JsonResponse
     * Test ?
     */
    public function index(): JsonResponse
    {
        try {
            $queryResource = SubscriptionPartners::query();
            $total = $queryResource->count();

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $list = $queryResource->with('plans')->get();
            $parsedData = $this->parseData($list, $resourceOptions);

            Log::channel('gelf')->info('[SubscriptionPartnersController][store] the subscription partners list fetched successfully.');
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response , ResponseCode::HTTP_OK);
        }catch (\Exception $exception){
            Log::error('[SubscriptionUsersController][index] ' . $exception->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $exception->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * store resources
     *
     * @param  SubscriptionPartnersRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionPartnersRequest $request) {
        try {
            $inputs = $request->all();
            $partner = SubscriptionPartners::create($inputs);

            Log::channel('gelf')->info('[SubscriptionPartnersController][store] the subscription partner created successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_CREATED );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][store] the subscription partner creation got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * show single resource
     *
     * @param SubscriptionPartnersRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function show(SubscriptionPartnersRequest $request, $id) {
        try {
            $partner = SubscriptionPartners::with('plans')->findOrFail($id);

            Log::channel('gelf')->info('[SubscriptionPartnersController][store] the subscription partner created successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][store] the subscription partner creation got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function update(SubscriptionPartnersRequest $request, $id) {
        try {
            $partner = SubscriptionPartners::findOrFail($id);
            $partner->update($request->only([
                'name',
                'endpoint_path',
                'client_id',
                'provision_key',
                'secret_key',
                'scope',
            ]));
            Log::channel('gelf')->info('[SubscriptionPartnersController][store] the subscription partner created successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][store] the subscription partner creation got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * remove specific subscription partner by id
     * @param SubscriptionPartnersRequest $request
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy (SubscriptionPartnersRequest $request, int $id) {
        try {
            $partner = SubscriptionPartners::findOrFail($id);
            SubscriptionPartnersPlans::where('subscription_partner_id', '=', $id)->delete();
            $partner->delete();

            Log::channel('gelf')->info('[SubscriptionPartnersController][destroy] the subscription partner deleted successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][destroy] the subscription partner deletion got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
