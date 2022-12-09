<?php

namespace App\Http\Controllers;

use App\Constants\Tables;
use App\Helpers\Helper;
use App\Http\Requests\SubscriptionSharesRequest;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionShareItems;
use App\Models\SubscriptionShares;
use App\Models\SubscriptionUserHistories;
use Bschmitt\Amqp\Table;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;
use Optimus\Bruno\EloquentBuilderTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SubscriptionSharesController extends Controller
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
        $ss = SubscriptionShares::latest()->get();

        return new JsonResponse( [ 'data' => $ss ] ,ResponseCode::HTTP_OK );
    }

    /**
     * list resources
     *
     * @param SubscriptionSharesRequest $request
     * @return JsonResponse
     * Test ?
     */
    public function report_sales(SubscriptionSharesRequest $request): JsonResponse
    {
        $filter = $request->get('filter', null);
        if ($filter != null) {
            $planFilters = json_decode($filter, true);
        }

        $publisherId = $request->get("publisher_id", null);
        $planName = ( isset($planFilters['plan_title_filter']) ) ? $planFilters['plan_title_filter'] : null;
        $dateFilterEnd = ( isset($planFilters['date_filter_end']) ) ? $planFilters['date_filter_end'] : null;
        $dateFilterStart = ( isset($planFilters['date_filter_start']) ) ? $planFilters['date_filter_start'] : null;
        $entityNameFilter = ( isset($planFilters['entity_name_filter']) ) ? $planFilters['entity_name_filter'] : null;

        $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
        if (count($publisherPlanEntityIds) == 0) {
            return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_NO_CONTENT );
        }

        try {
            $entityInfos = SubscriptionUserHistories::getEntityNamesByPlanEntities($publisherPlanEntityIds, $entityNameFilter);
        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }

        try {
            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon()->toDateTimeLocalString();

            if ($entityNameFilter != null) {
                $publisherPlanEntityIds = array_keys($entityInfos);
            }

            $query = SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
                ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id')
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
                ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_user_id')
                ->where(Tables::SUBSCRIPTION_SHARES.'.publisher_share_amount', '!=', 0)
                ->whereRaw(Tables::SUBSCRIPTION_SHARES.".created_at >= DATE_FORMAT('".$thisMonth."', '%Y-%m-%d %T')");

            if (isset($planName)) {
                $query = $query->whereRaw("JSON_EXTRACT(".Tables::SUBSCRIPTION_USERS.'.plan_details'.", '$.title') LIKE CONCAT('%','$planName','%')");
            }
            if (isset($dateFilterStart)) {
                $query = $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '>=', $dateFilterStart);
            }
            if (isset($dateFilterEnd)) {
                $query = $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $dateFilterEnd);
            }

            $totalQuery = clone($query);
            $total = $totalQuery->count();

            $userIDsQuery = clone($query);
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                'json' => [ 'user_id' =>  $userIDsQuery->pluck(Tables::SUBSCRIPTION_USERS.'.user_id'), 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);


            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($query,$resourceOptions);

            $result = $query->select([
                DB::raw('JSON_EXTRACT(plan_details, "$.title") AS plan_title'),
                DB::raw('JSON_EXTRACT(plan_details, "$.duration") AS plan_duration'),
                Tables::SUBSCRIPTION_SHARES.'.*',
                Tables::SUBSCRIPTION_ENTITIES.'.price_factor', Tables::SUBSCRIPTION_ENTITIES.'.entity_type',
                Tables::SUBSCRIPTION_USERS.'.user_id',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id as plan_entity_id'
            ])->orderBy(Tables::SUBSCRIPTION_SHARES.'.created_at', 'DESC')
            ->get()->each(function ($item) use ( $entityResult ){
                $item['shamsi_created_at'] = Jalalian::forge($item->created_at)->format('Y-m-d H:i:s');
                $item['user_info'] = Helper::username_masker($entityResult[$item->user_id]["username"]);
            })->makeHidden(['subscription_user_id', 'id', 'updated_at'])->toArray();

            $response = [];
            foreach ($result as $row) {
                $planEntityId = $row['plan_entity_id'];
                if (array_key_exists(''.$planEntityId,$entityInfos)) {
                    $row['plan_title'] = json_decode($row['plan_title']);
                    $row['entity_name'] = $entityInfos[''.$planEntityId]['title'];
                    $row['entity_price'] = $entityInfos[''.$planEntityId]['price'];
                    array_push($response, $row);
                }
            }
            $parsedData = $this->parseData($response, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];

            return new JsonResponse( $response ,ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }

    }

    public function report_sales_boxes(SubscriptionSharesRequest $request)
    {
        $filter = $request->get('filter', null);
        if ($filter != null) {
            $planFilters = json_decode($filter, true);
        }

        $publisherId = $request->get("publisher_id", null);
        $planName = ( isset($planFilters['plan_title_filter']) ) ? $planFilters['plan_title_filter'] : null;
        $dateFilterEnd = ( isset($planFilters['date_filter_end']) ) ? $planFilters['date_filter_end'] : null;
        $dateFilterStart = ( isset($planFilters['date_filter_start']) ) ? $planFilters['date_filter_start'] : null;
        $entityNameFilter = ( isset($planFilters['entity_name_filter']) ) ? $planFilters['entity_name_filter'] : null;

        $publisherId = $request->get("publisher_id", null);
        $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
        if (count($publisherPlanEntityIds) == 0) {
            return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_NO_CONTENT );
        }

        try {
            $entityInfos = SubscriptionUserHistories::getEntityNamesByPlanEntities($publisherPlanEntityIds, $entityNameFilter);
        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_NO_CONTENT );
        }

        try {
            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon()->toDateTimeLocalString();
            $query = SubscriptionShareItems::query()->whereIn(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', $publisherPlanEntityIds)
                ->join(Tables::SUBSCRIPTION_SHARES, Tables::SUBSCRIPTION_SHARES.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id')
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id')
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
                ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_user_id')
                ->where(Tables::SUBSCRIPTION_SHARES.'.publisher_share_amount', '!=', 0)
                ->whereRaw(Tables::SUBSCRIPTION_SHARES.".created_at >= DATE_FORMAT('".$thisMonth."', '%Y-%m-%d %T')");

            if (isset($planName)) {
                $query = $query->whereRaw("JSON_EXTRACT(".Tables::SUBSCRIPTION_USERS.'.plan_details'.", '$.title') = '$planName'");
            }
            if (isset($dateFilterStart)) {
                $query = $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '>=', $dateFilterStart);
            }
            if (isset($dateFilterEnd)) {
                $query = $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $dateFilterEnd);
            }

            $lastMonthReport = SubscriptionShares::total_report($query);

            return new JsonResponse( $lastMonthReport ,ResponseCode::HTTP_OK );
        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }

    }
    /**
     * list resources
     *
     * @param SubscriptionSharesRequest $request
     * @return JsonResponse
     * Test ?
     */
    public function abstract_report(SubscriptionSharesRequest $request) {
        try {
            $publisherId = $request->get('publisher_id', null);
            $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon()->toDateTimeLocalString();

            $earning = SubscriptionShares::getPublisherThisMonthEarning($thisMonth, $publisherPlanEntityIds);
            $userNumbers = SubscriptionUserHistories::getThisMonthDownloaded($thisMonth, $publisherPlanEntityIds);
            $publisherEntities = SubscriptionEntities::getPublisherSubscriptionBooks($publisherPlanEntityIds);

            return new JsonResponse( [ 'box_stats' => [
                    [
                        'name' => 'fidiplus_publisher_earning',
                        'title' => 'فروش فیدی پلاس (غیر ارزی)',
                        'count' => (int)$earning
                    ],
                    [
                        'name' => 'fidiplus_publisher_downloaded_books',
                        'title' => 'کتاب های دانلود شده پابلیشر در فیدی پلاس',
                        'count' => $userNumbers
                    ],
                    [
                        'name' => 'fidiplus_publisher_published_books',
                        'title' => 'کتاب های منتشر شده در فیدی پلاس',
                        'count' => $publisherEntities
                    ],
                ]
            ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * list resources
     *
     * @param SubscriptionSharesRequest $request
     * @return JsonResponse
     * Test ?
     */
    public function abstract_report_chart(SubscriptionSharesRequest $request) {
        try {
            $publisherId = $request->get('publisher_id', null);
            $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);

            $earningChart = SubscriptionShares::buildThisMonthPublisherDashboardChartData($publisherPlanEntityIds);
            $userNumbersChart = SubscriptionUserHistories::buildThisMonthDownloadedChartData($publisherPlanEntityIds);

            $chartResult = array_merge($earningChart, $userNumbersChart);
            return new JsonResponse( [ 'data' => $chartResult ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * list resources
     *
     * @param SubscriptionSharesRequest $request
     * @return JsonResponse
     * Test TODO Test sales report to return valid data after testing settlement new just for test API
     */
    public function sales_report_chart(SubscriptionSharesRequest $request) {
        try {
            $filterJson = $request->get('filter', null);
            $filter = json_decode($filterJson, true);

            $publisherId = $request->get('publisher_id', null);
            $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
            $earningChart = SubscriptionShares::buildThisMonthPublisherDashboardChartData($publisherPlanEntityIds, $filter);

            $chartResult = array_merge($earningChart);
            return new JsonResponse( [ 'data' => $chartResult ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * list resources
     *
     * @param SubscriptionSharesRequest $request
     * @param int publisherId
     * @return JsonResponse
     * Test ?
     */
    public function dashboard_dot_chart(SubscriptionSharesRequest $request) {
        try {
            $publisherId = $request->get('publisher_id', null);
            $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
            $earningChart = SubscriptionShares::buildPublisherDashboardChartData($publisherPlanEntityIds);

            return new JsonResponse( [ 'data' => $earningChart ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    public function dashboard_mostly_sold_subscription_books(SubscriptionSharesRequest $request) {
        try {
            $publisherId = $request->get('publisher_id', null);
            $publisherPlanEntityIds = SubscriptionPlanEntities::getPublisherPlanEntityIds($publisherId);
            $firstOfThisYear = Carbon::now()->subMonths(12)->toDateTimeLocalString();
            $result = SubscriptionUserHistories::query()->whereIn(
                'subscription_plan_entity_id' ,$publisherPlanEntityIds
            )->where('start_date', '>=', $firstOfThisYear)
                ->select(['entity_id'])->selectRaw(
                "count( distinct(subscription_user_id) ) as sold_numbers"
            )->groupBy('entity_id')->orderByRaw("count( distinct(subscription_user_id) ) ASC")
                ->limit(5)->get()->toArray();

            return new JsonResponse( [ 'data' => $result ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    public function crm_publisher_shares_report(SubscriptionSharesRequest $request) {
        try {
            $filter = $request->get('filter', null);
            if ($filter != null) {
                $planFilters = json_decode($filter, true);
            }

            $dateFilterEnd = ( isset($planFilters['date_filter_end']) ) ? $planFilters['date_filter_end'] : null;
            $dateFilterStart = ( isset($planFilters['date_filter_start']) ) ? $planFilters['date_filter_start'] : null;
            $entityNameFilter = ( isset($planFilters['entity_name_filter']) ) ? $planFilters['entity_name_filter'] : null;
            $entityTypeFilter = ( isset($planFilters['entity_type_filter']) ) ? $planFilters['entity_type_filter'] : null;
            $publisherNameFilter = ( isset($planFilters['publisher_name_filter']) ) ? $planFilters['publisher_name_filter'] : null;
            $valid_to_pay = ( isset($planFilters['valid_to_pay']) ) ? $planFilters['valid_to_pay'] : null;

            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon()->toDateTimeLocalString();
            $query = SubscriptionShares::query()
                ->join(Tables::SUBSCRIPTION_SHARE_ITEMS, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id', '=', Tables::SUBSCRIPTION_SHARES.'.id')
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id')
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
                ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_user_id');

            if (!isset($dateFilterStart) && !isset($dateFilterEnd)) {
                $query->whereRaw(Tables::SUBSCRIPTION_SHARES.".created_at >= DATE_FORMAT('".$thisMonth."', '%Y-%m-%d %T')");
            }

            if (isset($dateFilterStart)) {
                $query->where(Tables::SUBSCRIPTION_SHARES . '.created_at', '>=', $dateFilterStart);
            }
            if (isset($dateFilterEnd)) {
                $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $dateFilterEnd);
            }

            if ($valid_to_pay != null) {
                $query->where(Tables::SUBSCRIPTION_SHARES.'.valid_to_pay', '=', $valid_to_pay);
            }

            if ($entityTypeFilter != null) {
                $query->where(Tables::SUBSCRIPTION_ENTITIES.'.entity_type', '=', $entityTypeFilter);
            }

            if ($entityNameFilter != null) {
                $guzzle = new Client();
                try {
                    $response = $guzzle->post('https://papi.fidibo.com/get/book/by/name',[
                        'json' => [ 'book_title' =>  $entityNameFilter, 'access_key' => env("PAPI_ACCESS_KEY") ]
                    ]);
                    $entities = json_decode($response->getBody()->getContents(),true);
                    $entityIDs = [];
                    foreach ($entities as $entity) {
                        $entityIDs[] = $entity['book_id'];
                    }
                    $query->whereIn(Tables::SUBSCRIPTION_ENTITIES.'.entity_id', $entityIDs);
                } catch (\Exception $err) {
                    return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
                }
            }

            if (isset($publisherNameFilter)) {
                $guzzle = new Client();
                try {
                    $response = $guzzle->post('https://papi.fidibo.com/get/publisher/id/by/name',[
                        'json' => [ 'publisher_name' =>  $publisherNameFilter, 'access_key' => env("PAPI_ACCESS_KEY") ]
                    ]);
                    $publisherIDs = json_decode($response->getBody()->getContents(),true);
                    $query->whereIn(Tables::SUBSCRIPTION_ENTITIES.'.publisher_id', $publisherIDs);
                } catch (\Exception $err) {
                    return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
                }
            }

            $planEntitiesQuery = clone($query);
            $publisherPlanEntityIds = $planEntitiesQuery->pluck(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id');
            if (count($publisherPlanEntityIds) == 0) {
                return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_NO_CONTENT );
            }

            try {
                $entityInfos = SubscriptionUserHistories::getEntityNamesByPlanEntities($publisherPlanEntityIds, $entityNameFilter);
            } catch (\Exception $err) {
                return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
            }

            $totalQuery = clone($query);
            $total = $totalQuery->count();

            $userIDsQuery = clone($query);
            $guzzle = new Client();
            $userResult = [];
            $entityResult = [];
            try {
                $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                    'json' => [ 'user_id' =>  $userIDsQuery->pluck(Tables::SUBSCRIPTION_USERS.'.user_id'), 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);
                $userResult = json_decode($response->getBody()->getContents(),true);
            } catch (\Exception $err) {
                return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
            }

            try {
                $entityQuery = clone($query);
                $response = $guzzle->post('https://papi.fidibo.com/get/book/by/id',[
                    'json' => [ 'book_ids' =>  $entityQuery->pluck(Tables::SUBSCRIPTION_ENTITIES.'.entity_id'), 'access_key' => env("PAPI_ACCESS_KEY") ]
                ]);
                $entityResult = json_decode($response->getBody()->getContents(),true);
            } catch (\Exception $err) {
                return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
            }

            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($query,$resourceOptions);

            $result = $query->select([
                DB::raw('JSON_EXTRACT(plan_details, "$.title") AS plan_title'),
                DB::raw('JSON_EXTRACT(plan_details, "$.duration") AS plan_duration'),
                Tables::SUBSCRIPTION_SHARES.'.*',
                Tables::SUBSCRIPTION_ENTITIES.'.price_factor', Tables::SUBSCRIPTION_ENTITIES.'.entity_type',
                Tables::SUBSCRIPTION_ENTITIES.'.entity_id', Tables::SUBSCRIPTION_ENTITIES.'.publisher_share',
                Tables::SUBSCRIPTION_USERS.'.user_id',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id as plan_entity_id'
            ])->orderBy(Tables::SUBSCRIPTION_SHARES.'.created_at', 'DESC')
                ->get()->each(function ($item) use ( $userResult, $entityResult ){
                    $item['shamsi_created_at'] = Jalalian::forge($item->created_at)->format('Y-m-d H:i:s');
//                    dd($item->created_at, $item['shamsi_created_at']);
                    if (array_key_exists($item->user_id, $userResult)) {
                        $item['user_info'] = $userResult[$item->user_id]["username"];
                    }
                    if (array_key_exists($item->entity_id, $entityResult)) {
                        $item['publisher_name'] = $entityResult[$item->entity_id]["publisher_title"];
                        $item['max_to_pay'] = $entityResult[$item->entity_id]["price"] * ($item->publisher_share / 100);
                    }
                    $item['fidibo_share'] = $item->total_calculated_amount - $item->publisher_share_amount;
                })->makeHidden(['subscription_user_id', 'id', 'updated_at'])->toArray();
//            dd($result);

            $response = [];
            foreach ($result as $row) {
                $planEntityId = $row['plan_entity_id'];
                if (array_key_exists(''.$planEntityId,$entityInfos)) {
                    $row['plan_title'] = json_decode($row['plan_title']);
                    $row['entity_name'] = $entityInfos[''.$planEntityId]['title'];
                    $row['entity_price'] = $entityInfos[''.$planEntityId]['price'];
                    array_push($response, $row);
                }
            }

            $parsedData = $this->parseData($response, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( $response ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    public function crm_shares_report(SubscriptionSharesRequest $request) {
        try {
            $filter = $request->get('filter', null);
            if ($filter != null) {
                $planFilters = json_decode($filter, true);
            }

            $dateFilterEnd = ( isset($planFilters['date_filter_end']) ) ? $planFilters['date_filter_end'] : null;
            $dateFilterStart = ( isset($planFilters['date_filter_start']) ) ? $planFilters['date_filter_start'] : null;
            $entityNameFilter = ( isset($planFilters['entity_name_filter']) ) ? $planFilters['entity_name_filter'] : null;
            $publisherNameFilter = ( isset($planFilters['publisher_name_filter']) ) ? $planFilters['publisher_name_filter'] : null;

            $thisMonth = Jalalian::fromFormat('Y-m-d H:i:s', Jalalian::now()->format('Y-m-01 00:00:00'))->toCarbon()->toDateTimeLocalString();
            $query = SubscriptionShares::query()
                ->join(Tables::SUBSCRIPTION_SHARE_ITEMS, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_share_id', '=', Tables::SUBSCRIPTION_SHARES.'.id')
                ->join(Tables::SUBSCRIPTION_ENTITIES, Tables::SUBSCRIPTION_ENTITIES.'.id', '=', Tables::SUBSCRIPTION_SHARES.'.subscription_entity_id')
                ->join(Tables::SUBSCRIPTION_PLAN_ENTITIES, Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id', '=', Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id')
                ->join(Tables::SUBSCRIPTION_USERS, Tables::SUBSCRIPTION_USERS.'.id', '=', Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_user_id');

            if (!isset($dateFilterStart)) {
                $query->whereRaw(Tables::SUBSCRIPTION_SHARES.".created_at >= DATE_FORMAT('".$thisMonth."', '%Y-%m-%d %T')");
            } else {
                $query->where(Tables::SUBSCRIPTION_SHARES . '.created_at', '>=', $dateFilterStart);
            }
            if (isset($dateFilterEnd)) {
                $query->where(Tables::SUBSCRIPTION_SHARES.'.created_at', '<=', $dateFilterEnd);
            }

//            // TODO @Sadegh must implement an API which returns the publisher id by getting publisher name.
//            if (isset($publisherNameFilter)) {
//
//            }

            $planEntitiesQuery = clone($query);
            $publisherPlanEntityIds = $planEntitiesQuery->pluck(Tables::SUBSCRIPTION_SHARE_ITEMS.'.subscription_plan_entity_id');
            if (count($publisherPlanEntityIds) == 0) {
                return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_NO_CONTENT );
            }

            try {
                $entityInfos = SubscriptionUserHistories::getEntityNamesByPlanEntities($publisherPlanEntityIds, $entityNameFilter);
            } catch (\Exception $err) {
                return new JsonResponse( [ 'data' => [], 'message' => "There are no entities from this publisher in FidiPlus." ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
            }

            $totalQuery = clone($query);
            $total = $totalQuery->count();

            $userIDsQuery = clone($query);
            $guzzle = new Client();
            $response = $guzzle->post('https://papi.fidibo.com/get/user/by/id',[
                'json' => [ 'user_id' =>  $userIDsQuery->pluck(Tables::SUBSCRIPTION_USERS.'.user_id'), 'access_key' => env("PAPI_ACCESS_KEY") ]
            ]);
            $entityResult = json_decode($response->getBody()->getContents(),true);


            $resourceOptions = $this->parseResourceOptions();
            $this->applyResourceOptions($query,$resourceOptions);

            $result = $query->select([
                DB::raw('JSON_EXTRACT(plan_details, "$.title") AS plan_title'),
                DB::raw('JSON_EXTRACT(plan_details, "$.duration") AS plan_duration'),
                Tables::SUBSCRIPTION_SHARES.'.*',
                Tables::SUBSCRIPTION_ENTITIES.'.price_factor', Tables::SUBSCRIPTION_ENTITIES.'.entity_type',
                Tables::SUBSCRIPTION_USERS.'.user_id',
                Tables::SUBSCRIPTION_PLAN_ENTITIES.'.id as plan_entity_id'
            ])->orderBy(Tables::SUBSCRIPTION_SHARES.'.created_at', 'DESC')
                ->get()->each(function ($item) use ( $entityResult ){
                    $item['shamsi_created_at'] = Jalalian::forge($item->created_at)->format('Y-m-d H:i:s');
                    $item['user_info'] = Helper::username_masker($entityResult[$item->user_id]["username"]);
                })->makeHidden(['subscription_user_id', 'id', 'updated_at'])->toArray();

            $response = [];
            foreach ($result as $row) {
                $planEntityId = $row['plan_entity_id'];
                if (array_key_exists(''.$planEntityId,$entityInfos)) {
                    $row['plan_title'] = json_decode($row['plan_title']);
                    $row['entity_name'] = $entityInfos[''.$planEntityId]['title'];
                    $row['entity_price'] = $entityInfos[''.$planEntityId]['price'];
                    array_push($response, $row);
                }
            }

            $parsedData = $this->parseData($response, $resourceOptions);
            $response = ['total' => $total, 'per_page' => $resourceOptions['limit'], 'data' => $parsedData];
            return new JsonResponse( [ 'data' => $response ] ,ResponseCode::HTTP_OK );

        } catch (\Exception $err) {
            return new JsonResponse( [ 'data' => [], 'message' => $err->getMessage() ] ,ResponseCode::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
