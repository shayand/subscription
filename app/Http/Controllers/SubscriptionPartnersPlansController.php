<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Http\Requests\SubscriptionPartnersPlansRequest;
use App\Http\Requests\SubscriptionPartnersRequest;
use App\Models\SubscriptionPartners;
use App\Models\SubscriptionPartnersPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Optimus\Bruno\EloquentBuilderTrait;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;
use Eloquent;

/**
 * @mixin Eloquent
 */
class SubscriptionPartnersPlansController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * store resources
     *
     * @param  SubscriptionPartnersPlansRequest $request
     * @return JsonResponse
     * @throws Throwable
     * @mixin Eloquent
     * Test ?
     */
    public function store(SubscriptionPartnersPlansRequest $request) {
        try {
            $inputs = $request->all();

            $plansStr = $inputs['plan_id'];
            $plansArr = explode(",", $plansStr);
            if (count($plansArr) > 0) {
                $partner = SubscriptionPartners::find($inputs['partner_id']);
                $partner->plans()->attach($plansArr, [Tables::SUBSCRIPTION_PARTNERS_PLANS.'.created_at' => now()->toDateTimeLocalString(),
                    Tables::SUBSCRIPTION_PARTNERS_PLANS.'.updated_at' => now()->toDateTimeLocalString()]);
            }

            Log::channel('gelf')->info('[SubscriptionPartnersPlansController][store] the subscription partner created successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_CREATED );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][store] the subscription partner creation got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * remove specific subscription partner plan by partnerId and planId
     * @param SubscriptionPartnersRequest $request
     * @param int $planId
     * @return JsonResponse
     * Test ?
     */
    public function destroy (SubscriptionPartnersRequest $request, int $planId) {
        try {
            $partnerId = $request->get("partner_id", null);
            $partner = SubscriptionPartners::findOrFail($partnerId);

            SubscriptionPartnersPlans::where(['partner_id' => $partnerId, 'plan_id' => $planId])->delete();

            Log::channel('gelf')->info('[SubscriptionPartnersController][destroy] the subscription partner plan deleted successfully.');
            return new JsonResponse(['data'=> $partner ],ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionPartnersController][destroy] the subscription partner plan deletion got the following error:'.$err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
