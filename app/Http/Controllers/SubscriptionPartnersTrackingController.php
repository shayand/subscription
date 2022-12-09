<?php

namespace App\Http\Controllers;

use App\Exceptions\PartnerDuplicateTracking;
use App\Exceptions\PartnerNotExists;
use App\Exceptions\PartnerPlanIncorrect;
use App\Exceptions\PartnerTrackingTransitionException;
use App\Http\Requests\SubscriptionPartnersPlanRequest;
use App\Http\Requests\SubscriptionPartnersTrackingCheckRequest;
use App\Http\Requests\SubscriptionPartnersTrackingRequest;
use App\Models\SubscriptionPartners;
use App\Models\SubscriptionPartnersPlans;
use App\Models\SubscriptionPartnersTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionPartnersTrackingController extends Controller
{

    /**
     * @param SubscriptionPartnersTrackingRequest $request
     * @param string $partnerUrlKey
     * @return JsonResponse
     * @throws \Throwable
     */
    public function NewTracking(SubscriptionPartnersTrackingRequest $request,string $partnerUrlKey)
    {
        try {
            $inputs = $request->all();

            $partnerDetails = SubscriptionPartners::where('endpoint_path', '=', $partnerUrlKey)->first();
            if($partnerDetails == null){
                throw new PartnerNotExists('The specified partner does not exists. please use correct url key');
            }

            $decoratedPhone = $this->_decoratePhone($inputs['phone']);

            if (array_key_exists('duration',$inputs)){
                $durationInDays = $inputs['duration'];

                $totalDurationArray = [];
                if ($durationInDays > 93){
                    $totalNinetyDays = floor($durationInDays / 93);
                    for ($i = 0;$i < $totalNinetyDays;$i++){
                        $totalDurationArray[] = 93;
                    }
                    $remainDays = fmod($durationInDays,93);
                    $totalDurationArray[] = $remainDays;
                } else {
                    $totalDurationArray[] = $durationInDays;
                }

                foreach ($totalDurationArray as $singleDurationArray){
                    $planDetails = SubscriptionPartnersPlans::getPlanDetailsByDuration($partnerDetails->id,$singleDurationArray);
                    $planObject = $planDetails->first();

//                    if (SubscriptionPartnersTracking::check_whether_same_user_plan_on_a_minute($planObject->id,$decoratedPhone)){
//                        throw new PartnerDuplicateTracking('You have sent same user/plan in less than one minutes, so the request does not processed');
//                    }

                    $tracking = new SubscriptionPartnersTracking();
                    $tracking->partner_plan_id = $planObject->id;
                    $tracking->tracking_uid = Str::orderedUuid();
                    $tracking->phone = $decoratedPhone;
                    $tracking->saveOrFail();

                }

            } else {
                $planDetails = SubscriptionPartnersPlans::getPlanDetailsByPlanId($partnerDetails->id,$inputs['plan']);
                if ($planDetails->count() == 0){
                    throw new PartnerPlanIncorrect('This selected partner does not have a plan with this duration or id. please use another one');
                }
                $planObject = $planDetails->first();

//                if (SubscriptionPartnersTracking::check_whether_same_user_plan_on_a_minute($planObject->id,$decoratedPhone)){
//                    throw new PartnerDuplicateTracking('You have sent same user/plan in less than one minutes, so the request does not processed');
//                }

                $tracking = new SubscriptionPartnersTracking();
                $tracking->partner_plan_id = $planObject->id;
                $tracking->tracking_uid = Str::orderedUuid();
                $tracking->phone = $decoratedPhone;
                $tracking->saveOrFail();
            }

            return new JsonResponse(['data'=> ['status' => 'success','tracking' => $tracking]], ResponseCode::HTTP_OK);
        } catch (\Exception $exception){
            \Sentry\captureException($exception);
            Log::error('[SubscriptionPartnersTrackingController][NewTracking] ' . $exception->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $exception->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

    }

    /**
     * @param SubscriptionPartnersTrackingCheckRequest $request
     * @param string $partnerUrlKey
     * @return JsonResponse
     */
    public function CheckTracking(SubscriptionPartnersTrackingCheckRequest $request,string $partnerUrlKey)
    {
        try {

            $inputs = $request->all();
            $trackingId = $inputs['tracking_id'];

            $trackingObject = SubscriptionPartnersTracking::where('tracking_uid','=',$trackingId)->first();
            if ($trackingObject == null){
                throw new PartnerTrackingTransitionException('the tracking id does not exists');
            }

            if ($trackingObject->is_fdb_processed == 0) {
                $status = 'not activated';
                $response = 'fidi-plus is not activated for this user';
            }else{
                $status = 'activated';
                $response = 'fidi-plus is activated for this user at ' . $trackingObject->updated_at;
                $trackingObject->is_checked_status = 1;
                $trackingObject->saveOrFail();
            }

            return new JsonResponse(['data'=> ['status' => $status,'response' => $response]], ResponseCode::HTTP_OK);
        } catch (\Exception $exception){
            \Sentry\captureException($exception);
            Log::error('[SubscriptionPartnersTrackingController][CheckTracking] ' . $exception->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $exception->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param SubscriptionPartnersPlanRequest $request
     * @param string $partnerUrlKey
     * @return JsonResponse
     */
    public function Plans(SubscriptionPartnersPlanRequest $request,string $partnerUrlKey)
    {
        try {
            $partnerDetails = SubscriptionPartners::where('endpoint_path', '=', $partnerUrlKey)->first();
            if ($partnerDetails == null){
                throw new PartnerNotExists('The specified partner does not exists. please use correct url key');
            }
            $plans = SubscriptionPartnersPlans::getAllPlanDetails($partnerDetails->id)->makeHidden(['id','partner_id']);
            if ($plans->count() == 0){
                throw new PartnerPlanIncorrect('There is not any plan assignment to this partner');
            }
            return new JsonResponse(['data'=> ['status' => 'success','plans' => $plans]], ResponseCode::HTTP_OK);
        } catch (\Exception $exception){
            \Sentry\captureException($exception);
            Log::error('[SubscriptionPartnersTrackingController][NewTracking] ' . $exception->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $exception->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param string $phone
     * @return false|string
     */
    private function _decoratePhone(string $phone){
        if (strpos($phone, '0') === 0){
            $phone = substr($phone,1);
        }
        if (strpos($phone, '98') === 0){
            $phone = substr($phone,2);
        }
        if (strpos($phone, '+98') === 0){
            $phone = substr($phone,3);
        }
        return $phone;
    }
}
