<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Http\Requests\SubscriptionSettelmentPeriodsRequest;
use App\Jobs\CalculateTotalPublishersShare;
use App\Jobs\CalculateTotalPublishersShareJob;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionJobs;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionSettelmentPeriodsController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function index(int $userSubscriptionId): JsonResponse
    {
        $queryResource = SubscriptionSettelmentPeriods::query()->where('subscription_user_id', '=', $userSubscriptionId);
        $total = $queryResource->count();
        $resourceOptions = $this->parseResourceOptions();
        $this->applyResourceOptions($queryResource,$resourceOptions);
        $list = $queryResource->get();
        $parsedData = $this->parseData($list, $resourceOptions);

        Log::info('[SubscriptionSettelmentPeriodsController][index] the subscription settlement periods has been listed');

        $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
        return new JsonResponse( $response , ResponseCode::HTTP_OK);
    }

    public function settlement_test_creator(int $userId) {
        try {
            $user = SubscriptionUsers::getActivePlan($userId);
            if (count($user) == 0) {
                throw new \Exception("This user has no active plans");
            } else {
                $user = $user[0];
            }

            $histories = SubscriptionUserHistories::getUserPlanHistory($user->id, $user->plan_id);

            $planEntityId = SubscriptionPlanEntities::query()
                ->select([
                    Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id',
                    Tables::SUBSCRIPTION_ENTITIES.'.entity_id',
                ])
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
                ->whereIn(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', [4807,4759,4744,4070,4034,4031,4028,4022,4015,4005,3990] /* Afkar entities */)
                ->where('plan_id', '=', $user->plan_id)->inRandomOrder()->first();

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/Books/ByIds',[
                'json' => [ 'book_ids' => [ $planEntityId->entity_id ] ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);
            if( !is_array($entityResult['output']) || !is_array($entityResult['output']['books']) ) {
                throw new \Exception("not a valid entity_id");
            }

            $entityInfo = $entityResult['output']['books'][0];
            $entity = SubscriptionEntities::query()->where('entity_id', '=', $planEntityId->entity_id)->firstOrFail()->toArray();
            $entityInfo['price_factor'] = $entity['price_factor'];
            $entityInfo['publisher_share'] = $entity['publisher_share'];
            $entityInfo['entity_id'] = $planEntityId->entity_id;
            $entityInfo['id'] = $entity['id'];

            $testHistory = SubscriptionUserHistories::create([
                'subscription_user_id' => $user->id,
                'subscription_plan_entity_id' => $planEntityId->id,
                'entity_id' => $planEntityId->entity_id,
                'start_date' => Carbon::now()->subDay()->toDateTimeLocalString(),
                'read_percent_end' => 11,
                'subscription_entity_details' => $entityInfo
            ]);

//            $settlement = SubscriptionSettelmentPeriods::create([
//                'subscription_user_id' => $user->id,
//                'settelment_date' => Carbon::now()->toDateTimeLocalString(),
//                'settlement_duration' => 1
//            ]);

            $response = [
                'test_history' => $testHistory,
//                'test_settlement' => $settlement,
                'previous_histories' => $histories
            ];
            return new JsonResponse( ['data'=> $response] ,ResponseCode::HTTP_CREATED);

        } catch (\Exception $err) {
            Log::error('[SubscriptionSettelmentPeriodsController][settlement_test_creator] ' . $err->getMessage());
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

//    public function settlement_job_runner(SubscriptionSettelmentPeriodsRequest $request) {
//        try {
//            $time = ( $request->get('time', null) != null ) ? $request->get('time') : Carbon::now()->timezone('Asia/Tehran')->toDateTimeLocalString();
//            $subscriptionJob = new SubscriptionJobs();
//            $subscriptionJob->uuid = Str::orderedUuid();
//            $subscriptionJob->save();
//            CalculateTotalPublishersShare::dispatch($subscriptionJob, $time);
//            return new JsonResponse( ['data' => ["job_id" => $subscriptionJob->id, "status" => "Open Browser and go into horizon dashboard to watch the status of the queued job."] ], ResponseCode::HTTP_OK);
//        } catch (\Exception $err) {
//            Log::error('[SubscriptionSettelmentPeriodsController][settlement_test_creator] ' . $err->getMessage());
//            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()]], ResponseCode::HTTP_INTERNAL_SERVER_ERROR);
//        }
//    }
}
