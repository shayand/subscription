<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionPaymentRequest;
use App\Http\Resources\SubscriptionPurchaseResource;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionUserLogs;
use App\Models\SubscriptionUsers;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseCode;


class SubscriptionPaymentController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function index(): JsonResponse
    {
        try {
            $queryResource = SubscriptionPayment::query();
            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            $list = $queryResource->get();
            $parsedData = $this->parseData($list, $resourceOptions);

            Log::channel('gelf')->info('[SubscriptionPaymentController][index] the subscription payment has been listed');

            $response = ['total' => $queryResource->count(), 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response , ResponseCode::HTTP_OK);
        } catch (\Exception $exception) {
            Log::channel('gelf')->error('[SubscriptionPaymentController][index] ' . $exception->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed' ,'message' => $exception->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * store resources
     *
     * @param  SubscriptionPaymentRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionPaymentRequest $request)
    {
        try {
            $inputData = $request->all();
            /***
             * check whether this user has ran out of entities
             */
            $userId = $inputData['user_id'];
            //SubscriptionUsers::checkRenewalActions($userId);
            $lastActivePlan = SubscriptionUsers::getLastActivePlan($userId);

            if($lastActivePlan == false){
                $startDate = new \DateTime();
            } else {
                $startDate = date('Y-m-d H:i:s',strtotime($lastActivePlan['end_date'] . '+1 minutes'));
            }


            (!isset($inputData['campaign_id']) || (int)($inputData['campaign_id']) === 0) && $inputData['is_processed'] = 1;

            $payment = SubscriptionPayment::create($inputData);
            try{
                $this->_sendDirectlyToCampaign($payment->id);
                Log::channel('gelf')->info('[SubscriptionPaymentController][store] the code has been sent to campaign microservice by curl');
            } catch (\Exception $exception){
                Log::channel('gelf')->info('[SubscriptionPaymentController][store] the code has been sent to campaign microservice by rabbitMQ. exception: '.$exception->getMessage());

            }

            $subscriptionUsers = new SubscriptionUsers();
            $subscriptionUsers->user_id = $userId;
            $subscriptionUsers->plan_id = $inputData['plan_id'];
            $subscriptionUsers->start_date = $startDate;
            $subscriptionUsers->subscription_payment_id = $payment->id;
            $subscriptionUsers->saveOrFail();

            Log::channel('gelf')->info('[SubscriptionPaymentController][store] new subscription user has been saved');
            $log = new SubscriptionUserLogs();
            $log->subscription_user_id = $subscriptionUsers->id;
            if(isset($inputData['device_id'])) {
                $log->device_id = $inputData['device_id'];
            }
            $log->saveOrFail();

        } catch (\Exception $err) {
            Log::channel('gelf')->info('[SubscriptionPaymentController][store] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionPaymentController][store] the subscription payment has been stored');
        return new JsonResponse(['data'=> $payment ],ResponseCode::HTTP_CREATED );
    }

    /**
     * get specific subscription payment by $id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id)
    {
        try{
            $payment = SubscriptionPayment::findOrFail($id);
        } catch (\Exception $err) {
            Log::channel('gelf')->info('[SubscriptionPaymentController][show] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed', 'message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionPaymentController][show] the subscription payment has been showed');
        return new JsonResponse( ['data' => $payment] ,ResponseCode::HTTP_OK);
    }

    /**
     * @param int $paymentId
     * @return boolean
     */
    private function _sendDirectlyToCampaign(int $paymentId)
    {
        $baseUrl = getenv('APIGATEWAY_URL') . getenv('CAMPAIGN_UNSECURE_URL');
        $url = $baseUrl . '/service/purchases/register';

        $modelRestrictions = ['wheres' => [['column' => 'campaign_id', 'sign' => '!=', 'value' => 0], ['column' => 'is_processed', 'value' => 0], ['column' => 'id', 'value' => $paymentId]]];
        $subscriptions = (new SubscriptionPayment)->getResources(['*'], $modelRestrictions);

        foreach ($subscriptions as $item) {
            $message = json_encode(SubscriptionPurchaseResource::make($item), JSON_THROW_ON_ERROR);

            $guzzle = new Client();
            $response = $guzzle->post($url, [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'timeout' => 2,
                'connect_timeout' => 2,
                'body' => $message
            ]);
            Log::channel('gelf')->info('[SubscriptionPaymentController][_sendDirectlyToCampaign] the campaign microservice response code is:' .$response->getStatusCode());
            Log::channel('gelf')->info('[SubscriptionPaymentController][_sendDirectlyToCampaign] the campaign microservice response body:' . json_encode($response->getBody()));
            if ($response->getStatusCode() == 201) {
                $item->setAttribute('is_processed', 1)->save();
                return true;
            }
        }
        return false;
    }
}
