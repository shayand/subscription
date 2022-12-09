<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Helpers\Helper;
use App\Http\Requests\SubscriptionEntitiesImportRequest;
use App\Http\Requests\SubscriptionEntitiesRequest;
use App\Imports\SubscriptionEntitiesImport;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Morilog\Jalali\Jalalian;
use Optimus\Bruno\EloquentBuilderTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;
use function Clue\StreamFilter\fun;
use function React\Promise\all;

class SubscriptionEntitiesController extends Controller
{
    use EloquentBuilderTrait;

    /**
     * Instantiate a new SubscriptionEntitiesController instance.
     */
    public function __construct()
    {
        $this->middleware('shuttle_auth', ['only' => ['store', 'manual_store']]);
        parent::__construct();
    }


    protected array $y = [];

    /**
     * list resources
     *
     * @param
     * @return JsonResponse
     * Test ?
     */
    public function index(SubscriptionEntitiesRequest $request): JsonResponse
    {
        try {
            $queryResource = SubscriptionEntities::query()->orderBy("created_at", "desc");

            $filter = $request->get("filter", null);
            if ($filter != null) {
                $filter = json_decode($filter, true);
            }
            $queryResource = SubscriptionEntities::applyEntitiesFilters($queryResource, $filter);

            $total = $queryResource->count();
            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($queryResource,$resourceOptions);
            if ($total == 0) {
                $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => []];
                return new JsonResponse( $response , ResponseCode::HTTP_OK);
            }
            $entityIDs = [];
            $list = $queryResource->get();

            foreach ($list as $item) {
                $entityIDs[] = $item['entity_id'];
            }

            if (count($entityIDs) == 0) {
                $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => []];
                return new JsonResponse( $response , ResponseCode::HTTP_OK);
            }

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/new/get/book/by/id',[
                'json' => [ 'book_ids' =>  $entityIDs, 'access_key' => env("PAPI_ACCESS_KEY")  ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);
            if ($entityResult['output']['result'] == false) {
                throw new \Exception("Non of the input IDs are valid.");
            }

            foreach ($entityResult['output']['books'] as $singleEntity) {
                $this->y[$singleEntity['id']] = [
                    'title' => $singleEntity['title'],
                    'publisher_title' => $singleEntity['publisher_title'],
                    'price' => $singleEntity['price']
                ];
            }

            $list->each(function ($item) {
                $item['price'] = $this->y[$item['entity_id']]['price'];
                $item['publisher_title'] = $this->y[$item['entity_id']]['publisher_title'];
                $item['entity_name'] = $this->y[$item['entity_id']]['title'];
            });

            $parsedData = $this->parseData($list, $resourceOptions);
            Log::channel('gelf')->info('[SubscriptionEntitiesController][index] the subscription entities has list returned.');

            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response , ResponseCode::HTTP_OK);
        } catch (\Exception $exception) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][index] ' . $exception->getMessage());
            return new JsonResponse( ['data'=>['status' => 'failed' ,'message' => $exception->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * store resources
     *
     * @param  SubscriptionEntitiesRequest $request
     * @return JsonResponse
     * @throws Throwable
     * Test ?
     */
    public function store(SubscriptionEntitiesRequest $request)
    {
        $inputs = $request->all();
        $priceFactor = 100;
        if ($request->exists('price_factor')) {
            $priceFactor = $inputs['price_factor'];
        }

        $entitiesStr = $inputs['entity_id'];
        $entitiesArr = explode(",", $entitiesStr);
        try {
            $insertedEntities = [];
            $alreadyExistIDs = [];
            $insertedIDs = [];
            $failedIDs = [];

            $entityResult = ["output" => ["books" => []]];
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
                'json' => [ 'book_ids' => $entitiesArr ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);

            if ($entityResult['output']['result'] == false) {
                throw new \Exception("Non of the input IDs are valid.");
            }

            $cnt = 0;
            if( is_array($entityResult['output']) & is_array($entityResult['output']['books']) ) {
                foreach ($entityResult['output']['books'] as $singleEntity) {
                    $type = "book";
                    if ($singleEntity['format'] == "AUDIO" and $singleEntity['content_type'] == "book") {
                        $type = "audio";
                    }

                    try {
                        $entity = SubscriptionEntities::query()->where('entity_id', '=', $singleEntity['id'])->first();

                        if ($entity == null) {
                            $entity = SubscriptionEntities::create([
                                'entity_type' => $type,
                                'entity_id' => $singleEntity['id'],
                                'price_factor' => $priceFactor,
                                'publisher_id' => $singleEntity['publisher_id'],
                                'publisher_name' => $singleEntity['publisher_title'],
                                'publisher_share' => $singleEntity['publisher_marketshare'],
                                'operator_id' => $request->get('operator_id', null)
                            ]);
                            array_push($insertedEntities, $entity);
                            array_push($insertedIDs, $singleEntity['id']);
                        } else {
                            array_push($alreadyExistIDs, $singleEntity['id']);
                        }

                    } catch (\Exception $err) {
                        $failed = ['id' => $singleEntity->id, 'err' => $err->getMessage()];
                        array_push($failedIDs, $failed);
                    }
                }
            }
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][store] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = ['inserted_entities' => $insertedEntities, 'inserted_ids' => $insertedIDs, 'failed_ids' => $failedIDs, 'already_exist' => $alreadyExistIDs];
        Log::channel('gelf')->info('[SubscriptionEntitiesController][store] the subscription entities has been stored');
        return new JsonResponse(['data'=> $data ],ResponseCode::HTTP_CREATED );
    }


    public function manual_store(SubscriptionEntitiesRequest $request)
    {
        $inputs = $request->all();
        $priceFactor = 100;
        if ($request->exists('price_factor')) {
            $priceFactor = $inputs['price_factor'];
        }

        $entitiesStr = $inputs['entity_id'];
        $entitiesArr = explode(",", $entitiesStr);
        $entitiesArr = array_chunk($entitiesArr, 30);
        try {
            $insertedEntities = [];
            $alreadyExistIDs = [];
            $insertedIDs = [];
            $failedIDs = [];

            $entityResult = ["output" => ["books" => []]];
            foreach ($entitiesArr as $entities) {
                $guzzle = new Client();
                $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
                    'json' => [ 'book_ids' => $entities ]
                ]);
                $entityRes = json_decode($response->getBody()->getContents(),true);
                if ($entityRes['output']['result'] == false) {
                    throw new \Exception("Non of the input IDs are valid.");
                }
                $entityResult['output']['books'][] = $entityRes['output']['books'];
            }

            $cnt = 0;
            if( is_array($entityResult['output']) & is_array($entityResult['output']['books']) ) {
                $entities = [];
                foreach ($entityResult['output']['books'] as $booksArr) {
                    foreach ($booksArr as $singleEntity) {
                        $type = "book";
                        if ($singleEntity['format'] == "AUDIO" and $singleEntity['content_type'] == "book") {
                            $type = "audio";
                        }

                        try {
                            $entity = SubscriptionEntities::query()->where('entity_id', '=', $singleEntity['id'])->first();
                            if ($entity == null) {
                                $entities[] = [
                                    'entity_type' => $type,
                                    'entity_id' => $singleEntity['id'],
                                    'price_factor' => $priceFactor,
                                    'publisher_id' => $singleEntity['publisher_id'],
                                    'publisher_name' => $singleEntity['publisher_title'],
                                    'publisher_share' => $singleEntity['publisher_marketshare'],
                                    'created_at' => Carbon::now()->toDateTimeLocalString(),
                                    'updated_at' => Carbon::now()->toDateTimeLocalString(),
                                    'operator_id' => $request->get('operator_id', null)
                                ];
                            }

                        } catch (\Exception $err) {
                            $failed = ['id' => $singleEntity->id, 'err' => $err->getMessage()];
                            array_push($failedIDs, $failed);
                        }
                    }
                }
                $res = SubscriptionEntities::insert($entities);
                if ($res == true) {
                    $insertedEntities = $entities;
                } else {
                    throw new \Exception("exception in insertion of bulk entities");
                }
            }
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][store] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $err->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = ['count_entities' => count($insertedEntities), 'inserted_entities' => $insertedEntities, 'inserted_ids' => $insertedIDs, 'failed_ids' => $failedIDs, 'already_exist' => $alreadyExistIDs];
        Log::channel('gelf')->info('[SubscriptionEntitiesController][store] the subscription entities has been stored');
        return new JsonResponse(['data'=> $data ],ResponseCode::HTTP_CREATED );
    }

    public function excel_store(SubscriptionEntitiesImportRequest $request) {
        $allIDs = [];
        $res = [];
        try {
            $entities = (new SubscriptionEntitiesImport)->toArray(request()->file('entities'))[0];
            $headings = (new HeadingRowImport)->toArray(request()->file('entities'))[0][0];
            if(!in_array("entity_id", $headings)) {
                throw new \Exception("excel must have entity_id heading");
            }
            (new SubscriptionEntitiesImport)->import(request()->file('entities'));

            foreach ($entities as $entity) {
                $allIDs[] = $entity["entity_id"];
            }
            $res = SubscriptionEntities::set_types($request->get("operator_id", null), $request->get("price_factor", 100));
            $res["already_exist"] = [];

        } catch (\Maatwebsite\Excel\Validators\ValidationException $err) {
            $failures = $err->failures();

            $res["already_exist"] = [];
            foreach ($failures as $failure) {
                if ($failure->attribute()[0] == "Duplicate") {
                    $res["already_exist"][] = $failure->values()["entity_id"];
                }
            }

        } catch (\Exception $exception) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][store] throw exception');
            return new JsonResponse(['data'=> ['status' => 'failed','message' => $exception->getMessage()] ],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }
        $successful_IDS = array_merge($res["already_exist"], $res["inserted_ids"]);
        $res["failed_ids"] = array_diff($allIDs, $successful_IDS);
        Log::channel('gelf')->info('[SubscriptionEntitiesController][excel_store] the subscription entities has been stored');
        return new JsonResponse(['data'=> $res ],ResponseCode::HTTP_CREATED );
    }

    /**
     * get specific subscription entity by $id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function show(int $id)
    {
        try{
            $entity = SubscriptionEntities::findOrFail($id);
        } catch (\Exception $err){
            Log::channel('gelf')->error('[SubscriptionEntitiesController][show] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed', 'message' => $err->getMessage()]] ,ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionEntitiesController][show] the subscription entity has been showed');
        return new JsonResponse( ['data' => $entity] ,ResponseCode::HTTP_OK);
    }

    /**
     * update specific subscription entity by id
     *
     * @param SubscriptionEntitiesRequest $request
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function update(SubscriptionEntitiesRequest $request, int $id)
    {
        try {
            $entity = SubscriptionEntities::findOrFail($id);
            $entity->update($request->all());
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][update] throw exception');
            return new JsonResponse( ['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        Log::channel('gelf')->info('[SubscriptionEntitiesController][update] the subscription entity has been updated');
        return new JsonResponse(['data' => $entity],ResponseCode::HTTP_OK );
    }

    /**
     * remove specific subscription entity by id
     * @param int $id
     * @return JsonResponse
     * Test ?
     */
    public function destroy(int $id)
    {
        try{
            $entity = SubscriptionEntities::query()->findOrFail($id);
            $sendToQueue = [];
            $planEntities = SubscriptionPlanEntities::query()
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.entity_id')
                ->where(Tables::SUBSCRIPTION_ENTITIES.'.id' , '=' , $entity->id)
                ->select([
                    Tables::SUBSCRIPTION_PLAN_ENTITIES.'.*',
                    Tables::SUBSCRIPTION_ENTITIES.'.entity_id as real_entity_id'
                ])
                ->get();

            if ( count($planEntities) > 0 ) {
                foreach ($planEntities as $planEntity) {
                    $planEntity->delete();
                }
            }

            $deleteRes = $entity->delete();
            if (!$deleteRes) {
                throw new \Exception("Something unexpected happened. Please Try Again!");
            }

            /**
             * send data to rabbitMQ
             */
            $entityTitle = 'book_'. $entity->entity_id;
            $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => []]];
            Helper::send_to_elastic($sendToQueue);

        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][destroy] throw exception');
            return new JsonResponse(['data'=>['status' => 'failed','message' => $err->getMessage()]],ResponseCode::HTTP_UNPROCESSABLE_ENTITY );
        }

        Log::channel('gelf')->info('[SubscriptionEntitiesController][destroy] the subscription entity has been removed');
        return new JsonResponse( ['data' => ['status' => 'success' , 'message' => 'The entity has been removed.']],ResponseCode::HTTP_OK);
    }

    public function publisher_entities(SubscriptionEntitiesRequest $request) {
        $publisherId = $request->get('publisher_id', null);

        $filter = $request->get("filter", null);
        $planFilters = [];
        if ($filter != null) {
            $planFilters = json_decode($filter, true);
        }
        $planStatusFilter = ( isset($planFilters['status_filter']) ) ? $planFilters['status_filter'] : null;

        try {
            $entitiesPlans = SubscriptionEntities::query()->where('publisher_id', '=', $publisherId)
                ->orderBy('created_at', 'DESC');

            $entitiesPlans = SubscriptionEntities::applyEntitiesFilters($entitiesPlans, $planFilters);

            if (isset($planStatusFilter)) {
                if ($planStatusFilter == '1') {
                    $entitiesPlans = $entitiesPlans->with('active_plans');
                } elseif ($planStatusFilter == '0') {
                    $entitiesPlans = $entitiesPlans->with('inactive_plans');
                } else {
                    throw new \Exception("wrong input in status_filter given");
                }
            } else {
                $entitiesPlans = $entitiesPlans->with('plans');
            }

            $totalQuery = clone($entitiesPlans);
            $total = $totalQuery->count();

            $entitiesPlansIds = clone($entitiesPlans);

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds',[
                'json' => [ 'book_ids' => $entitiesPlansIds->pluck("entity_id") ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);
            if ($entityResult['output']['result'] == false) {
                throw new \Exception("Non of the input IDs are valid.");
            }

            foreach ($entityResult['output']['books'] as $singleEntity) {
                $this->y[$singleEntity['id']] = [
                    'title' => $singleEntity['title'],
                    'publisher_title' => $singleEntity['publisher_title'],
                    'price' => $singleEntity['price']
                ];
            }

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($entitiesPlans,$resourceOptions);

            $response = $entitiesPlans->get()->each(function ($entity) {
                $entity['entity_name'] = $this->y[$entity['entity_id']]['title'];
                $entity['publisher_title'] = $this->y[$entity['entity_id']]['publisher_title'];
                $entity['price'] = $this->y[$entity['entity_id']]['price'];

                foreach ($entity->plans as $plan) {
                    $plan->shamsi_created_at = Jalalian::forge($plan->pivot->created_at)->format('Y-m-d H:i:s');
                    if (isset($plan->pivot->deleted_at)) {
                        $plan->shamsi_deleted_at = Jalalian::forge($plan->pivot->deleted_at)->format('Y-m-d H:i:s');
                    } else {
                        $plan->shamsi_deleted_at = null;
                    }
                }
            });

            $parsedData = $this->parseData($response, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            Log::channel('gelf')->info('[SubscriptionEntitiesController][publisher_entities] publisher_id='.sprintf("%d",$publisherId));
            return new JsonResponse( $response ,ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][publisher_entities] throw exception: '. $err->getMessage());
            return new JsonResponse( ['data' => [], 'error' => $err->getMessage()] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    public function publisher_bestseller(SubscriptionEntitiesRequest $request) {
        try {
            $publisherId = $request->get('publisher_id', null);
            $entitiesQuery = SubscriptionEntities::getPublisherMostlySoldBookQuery($publisherId);
            $entitiesQueryTemp = clone($entitiesQuery);

            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/book/by/id',[
                'json' => [ 'book_ids' => $entitiesQuery->pluck(Tables::SUBSCRIPTION_ENTITIES.'.entity_id'), 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $entities = json_decode($response->getBody()->getContents(),true);
            $response = [];

            $entitiesQueryResult = $entitiesQueryTemp->get();
            foreach ($entitiesQueryResult as $item) {
                if (!isset($entities[ $item->entity_id ])) {
                    continue;
                }

                $entity = [
                    'book_id' => $item->entity_id,
                    'book_title' => $entities[ $item->entity_id ]['title'],
                    'count' => $item->count,
                    'book_rate' => $entities[ $item->entity_id ]['book_rating'],
                    'image' => $entities[ $item->entity_id ]['image_url'],
                ];
                array_push($response, $entity);
            }

            Log::channel('gelf')->info('[SubscriptionEntitiesController][publisher_bestseller] publisher_id='.sprintf("%d",$publisherId));
            return new JsonResponse( $response ,ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            Log::channel('gelf')->error('[SubscriptionEntitiesController][publisher_bestseller] publisher_id='.sprintf("%d",$publisherId).': throw exception: '. $err->getMessage());
            return new JsonResponse( ['data' => [], 'error' => $err->getMessage()] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
