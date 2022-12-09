<?php


namespace App\Constants;


use App\Http\Controllers\MetricsController;
use App\Http\Controllers\SubscriptionEntitiesController;
use App\Http\Controllers\SubscriptionPartnersController;
use App\Http\Controllers\SubscriptionPartnersPlansController;
use App\Http\Controllers\SubscriptionPartnersTrackingController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionPlanEntitiesController;
use App\Http\Controllers\SubscriptionPlansController;
use App\Http\Controllers\SubscriptionSettelmentPeriodsController;
use App\Http\Controllers\SubscriptionShareItemsController;
use App\Http\Controllers\SubscriptionSharesController;
use App\Http\Controllers\SubscriptionUserHistoriesController;
use App\Http\Controllers\SubscriptionUserLogsController;
use App\Http\Controllers\SubscriptionUsersController;
use App\Http\Controllers\UtilitiesController;
use App\Models\SubscriptionPartnersPlans;

abstract class Endpoints
{
    const HTTP_API_SUBSCRIPTION = [
        'name'          => 'core.api.subscription',
        'description'   => 'crude of subscription package',
        'endpoint'      => 'subscription',
        'method'        => 'api_resource',
        'class'         => 'SubscriptionController'
    ];

    const HTTP_API_SUBSCRIPTION_ENTITY = [
        'name'          => 'core.api.subscription.entities',
        'description'   => 'crude of settlement_test_creator subscription entities package',
        'endpoint'      => 'subscription_entities',
        'method'        => 'api_resource',
        'class'         => 'EntityController'
    ];

    const HTTP_API_SINGLE_SUBSCRIPTION_ENTITY = [
        'name'          => 'core.api.subscription.single_entities',
        'description'   => 'crude of  subscription entities package',
        'endpoint'      => 'subscription_entities/single/{subscriptionId}',
        'method'        => 'get',
        'class'         => 'EntityController@single',
    ];

    public const SUBSCRIPTION_METRIC = [
        'name' => 'core.api.subscription.metric',
        'description' => 'gather metric params',
        'endpoint' => 'metrics',
        'class' => [ MetricsController::class, 'index'],
    ];

    public const SUBSCRIPTION_Utilities = [
        'name' => 'core.api.subscription.utilities',
        'description' => 'clear redis cache',
        'endpoint' => 'utilities/redis-cache',
        'class' => [ UtilitiesController::class, 'clearRedisCache'],
    ];

    public const SUBSCRIPTION_PLANS = [
        'name' => 'core.api.subscription.plan',
        'description' => 'crud of subscription plan types',
        'endpoint' => 'plans',
        'class' => SubscriptionPlansController::class,
    ];

    public const SUBSCRIPTION_PLANS_PANEL_LIST = [
        'name' => 'core.api.subscription.plan.panel.list',
        'description' => 'list of subscription plan types',
        'endpoint' => 'plans_list',
        'class' =>  [ SubscriptionPlansController::class , 'panel_index' ],
    ];

    public const SUBSCRIPTION_USERS_PLAN_LIST = [
        'name' => 'core.api.subscription.plan.index',
        'description' => 'list of subscription user types',
        'endpoint' => 'users/{planId}',
        'class' => [ SubscriptionUsersController::class , 'index' ],
    ];


    public const SUBSCRIPTION_USERS_PLAN_CREATE = [
        'name' => 'core.api.subscription.plan.store',
        'description' => 'create subscription user types',
        'endpoint' => 'users/',
        'class' => [ SubscriptionUsersController::class , 'store' ],
    ];

    public const SUBSCRIPTION_USERS_PLAN_ASSIGNMENT_REASONS = [
        'name' => 'core.api.subscription.user_plan.assignment_reasons',
        'description' => 'Get all the assignment reasons',
        'endpoint' => 'users/assignment_reasons',
        'class' => [ SubscriptionUsersController::class , 'get_assignment_reasons' ],
    ];

    public const SUBSCRIPTION_USERS_CHECK_SUBSCRIPTION = [
        'name' => 'core.api.subscription.user.check',
        'description' => 'create subscription user types',
        'endpoint' => 'users/check-subscription/{userId}',
        'class' => [ SubscriptionUsersController::class , 'checkUserIsSubscribed' ],
    ];

    public const SUBSCRIPTION_USERS_GET_STATUS = [
        'name' => 'core.api.subscription.user.check',
        'description' => 'create subscription user types',
        'endpoint' => 'users/get-status/{userId}',
        'class' => [ SubscriptionUsersController::class , 'getUserSubscriptionStatus' ],
    ];

    public const SUBSCRIPTION_USERS_PLAN_READ = [
        'name' => 'core.api.subscription.user.show',
        'description' => 'get single subscription user types',
        'endpoint' => 'users/{planId}/{id}',
        'class' => [ SubscriptionUsersController::class , 'show' ],
    ];

    public const SUBSCRIPTION_USERS_PLAN_DELETE = [
        'name' => 'core.api.subscription.user.delete',
        'description' => 'delete subscription user types',
        'endpoint' => 'users/{planId}/{id}',
        'class' => [ SubscriptionUsersController::class , 'destroy' ],
    ];

    public const SUBSCRIPTION_SHARES = [
        'name' => 'core.api.subscription.share',
        'description' => 'crud of subscription share types',
        'endpoint' => 'shares',
        'class' => SubscriptionSharesController::class,
    ];

    public const SUBSCRIPTION_SHARE_ITEMS = [
        'name' => 'core.api.subscription.share_item',
        'description' => 'crud of subscription share item types',
        'endpoint' => 'share_items',
        'class' => SubscriptionShareItemsController::class,
    ];

    public const SUBSCRIPTION_USER_LOGS = [
        'name' => 'core.api.subscription.user.log',
        'description' => 'crud of subscription user logs types',
        'endpoint' => 'user_logs',
        'class' => SubscriptionUserLogsController::class,
    ];

    public const SUBSCRIPTION_PAYMENTS = [
        'name' => 'core.api.subscription.payment',
        'description' => 'crud of subscription payment types',
        'endpoint' => 'payments',
        'class' => SubscriptionPaymentController::class,
    ];

    public const SUBSCRIPTION_PARTNERS = [
        'name' => 'core.api.subscription.partners',
        'description' => 'crud of subscription partners types',
        'endpoint' => 'partners',
        'class' => SubscriptionPartnersController::class,
    ];

    public const SUBSCRIPTION_PARTNERS_PLANS = [
        'name' => 'core.api.subscription.partners.plans',
        'description' => 'crud of subscription partners plans types',
        'endpoint' => 'partners_plans',
        'class' => SubscriptionPartnersPlansController::class,
    ];

    public const SUBSCRIPTION_PARTNERS_TRACKING = [
        'name' => 'core.api.subscription.partners.tracking',
        'description' => 'create third party integration tracking id',
        'endpoint' => 'integration/{partnerUrlKey}/start-tracking',
        'class' => [ SubscriptionPartnersTrackingController::class , 'NewTracking' ],
    ];

    public const SUBSCRIPTION_PARTNERS_CHECK_TRACKING = [
        'name' => 'core.api.subscription.partners.check.tracking',
        'description' => 'check third party integration tracking status',
        'endpoint' => 'integration/{partnerUrlKey}/check-tracking',
        'class' => [ SubscriptionPartnersTrackingController::class , 'CheckTracking' ],
    ];

    public const SUBSCRIPTION_PARTNERS_PLANS_LIST = [
        'name' => 'core.api.subscription.partners.plans.list',
        'description' => 'get third party plans',
        'endpoint' => 'integration/{partnerUrlKey}/plans',
        'class' => [ SubscriptionPartnersTrackingController::class , 'Plans' ],
    ];


    public const SUBSCRIPTION_ENTITIES = [
        'name' => 'core.api.subscription.entities',
        'description' => 'crud of subscription entities types',
        'endpoint' => 'entities',
        'class' => SubscriptionEntitiesController::class,
    ];

    public const SUBSCRIPTION_ENTITIES_MANUAL_STORE = [
        'name' => 'core.api.subscription.entities.manual_store',
        'description' => 'store manual bulk entities of subscription entities types',
        'endpoint' => 'entities/manual_store',
        'class' => [SubscriptionEntitiesController::class, 'manual_store']
    ];

    public const SUBSCRIPTION_ENTITIES_EXCEL_STORE = [
        'name' => 'core.api.subscription.entities.manual_store',
        'description' => 'store manual bulk entities of subscription entities types',
        'endpoint' => 'entities/excel',
        'class' => [SubscriptionEntitiesController::class, 'excel_store']
    ];


    // SUBSCRIPTION SETTLEMENT PERIODS ROUTES
    public const SUBSCRIPTION_SETTELMENT_PERIODS = [
        'name' => 'core.api.subscription.settlement',
        'description' => 'crud of subscription settlement types',
        'endpoint' => 'settlement_periods/{userSubscriptionId}',
        'class' => [SubscriptionSettelmentPeriodsController::class, 'index']
    ];
    public const SUBSCRIPTION_SETTELMENT_TEST_CREATOR = [
        'name' => 'core.api.subscription.settlement.test_creator',
        'description' => 'create test settlement for test in panel publisher and demo for specific (user,plan)',
        'endpoint' => 'settlement_periods/{userId}',
        'class' => [SubscriptionSettelmentPeriodsController::class, 'settlement_test_creator']
    ];
//    public const SUBSCRIPTION_SETTELMENT_JOB_RUNNER = [
//        'name' => 'core.api.subscription.settlement.job.runner',
//        'description' => 'run the settlement job on the server',
//        'endpoint' => 'settlement_periods/job/runner',
//        'class' => [SubscriptionSettelmentPeriodsController::class, 'settlement_job_runner']
//    ];
    /*
    |--------------------------------------------------------------------------
    | Subscription Plan-Entity API Endpoints
    |--------------------------------------------------------------------------
    */
    public const SUBSCRIPTION_PLAN_ENTITIES_INDEX = [
        'name' => 'core.api.subscription.plan_entity.index',
        'description' => 'list all subscription plan entities types',
        'endpoint' => 'plan_entities/{planId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'index'],
    ];
    public const SUBSCRIPTION_PLAN_ENTITIES_STORE = [
        'name' => 'core.api.subscription.plan_entity.store',
        'description' => 'store subscription plan entities types',
        'endpoint' => 'plan_entities/{planId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'store'],
    ];
    public const SUBSCRIPTION_PLAN_ENTITIES_SHOW = [
        'name' => 'core.api.subscription.plan_entity.show',
        'description' => 'list of subscription plan entities types',
        'endpoint' => 'plan_entities/{planId}/{planEntityId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'show'],
    ];
    public const SUBSCRIPTION_PLAN_ENTITIES_UPDATE = [
        'name' => 'core.api.subscription.plan_entity.update',
        'description' => 'list of subscription plan entities types',
        'endpoint' => 'plan_entities/{planId}/{planEntityId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'update'],
    ];
    public const SUBSCRIPTION_PLAN_ENTITIES_REMOVE = [
        'name' => 'core.api.subscription.plan_entity.remove',
        'description' => 'list of subscription plan entities types',
        'endpoint' => 'plan_entities/{planId}/{planEntityId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'destroy'],
    ];

    public const SUBSCRIPTION_PLAN_ENTITIES_CHECK_AVAILABLE_CONTENT = [
        'name' => 'core.api.subscription.plan.check_content_available',
        'description' => 'list of subscription plan entities types',
        'endpoint' => 'plan_entities/check-content-available/{userId}/{contentId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'checkIsContentAvailableForUser'],
    ];

    public const SUBSCRIPTION_PLAN_ENTITIES_SYNC_CONTENT = [
        'name' => 'core.api.subscription.plan.SYNC_CONTENT',
        'description' => 'sync list of content with subscription user entities',
        'endpoint' => 'plan_entities/sync/{userId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'syncContents'],
    ];

    public const SUBSCRIPTION_PLAN_ENTITIES_SYNC_USER_CONTENT = [
        'name' => 'core.api.subscription.plan.SYNC_USER_CONTENT',
        'description' => 'sync list of content with subscription user entities',
        'endpoint' => 'plan_entities/sync-user/{userId}',
        'class' => [SubscriptionPlanEntitiesController::class, 'syncUserContents'],
    ];

    public const SUBSCRIPTION_PLAN_ENTITIES_CHECK_IS_SUBSCRIPTION = [
        'name' => 'core.api.subscription.plan.check.is.subscription',
        'description' => 'check list of entity ids are in subscription or not',
        'endpoint' => 'plan_entities/check_entity_is_in_subscription',
        'class' => [SubscriptionPlanEntitiesController::class, 'book_is_fidiplus'],
    ];


    /*
    |--------------------------------------------------------------------------
    | Subscription User-History API Endpoints
    |--------------------------------------------------------------------------
    */
    public const SUBSCRIPTION_USER_HISTORIES_INDEX = [
        'name' => 'core.api.subscription.user_history.index',
        'description' => 'list all subscription user history types',
        'endpoint' => 'user_histories/{userId}',
        'class' => [SubscriptionUserHistoriesController::class, 'index'],
    ];
    public const SUBSCRIPTION_USER_HISTORIES_STORE = [
        'name' => 'core.api.subscription.user_history.store',
        'description' => 'store subscription user history types',
        'endpoint' => 'user_histories/{userId}/{entityId}',
        'class' => [SubscriptionUserHistoriesController::class, 'store'],
    ];
    public const SUBSCRIPTION_USER_HISTORIES_SHOW = [
        'name' => 'core.api.subscription.user_history.show',
        'description' => 'list of subscription user history types',
        'endpoint' => 'user_histories/{id}/{userId}',
        'class' => [SubscriptionUserHistoriesController::class, 'show'],
    ];
    public const SUBSCRIPTION_USER_HISTORIES_UPDATE = [
        'name' => 'core.api.subscription.user_history.update',
        'description' => 'list of subscription user history types',
        'endpoint' => 'user_histories/{userId}/{entityId}',
        'class' => [SubscriptionUserHistoriesController::class, 'update'],
    ];
    public const SUBSCRIPTION_USER_HISTORIES_REMOVE = [
        'name' => 'core.api.subscription.user_history.remove',
        'description' => 'list of subscription user history types',
        'endpoint' => 'user_histories/{userId}/{entityId}',
        'class' => [SubscriptionUserHistoriesController::class, 'remove_from_library'],
    ];
    public const SUBSCRIPTION_USER_HISTORIES_BOUGHT = [
        'name' => 'core.api.subscription.user_history.bought',
        'description' => 'store user bought history',
        'endpoint' => 'user_histories_bought/{userId}',
        'class' => [SubscriptionUserHistoriesController::class, 'addToBoughtHistory'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Publisher Panel API Endpoints
    |--------------------------------------------------------------------------
    */
    public const SUBSCRIPTION_PUBLISHER_SALES_REPORT = [
        'name' => 'core.api.subscription.publisher.report.sales',
        'description' => 'report subscription sales for every publisher per user per book',
        'endpoint' => '/sales_report',
        'class' => [SubscriptionSharesController::class, 'report_sales'],
    ];
    public const SUBSCRIPTION_PUBLISHER_SALES_REPORT_BOXES = [
        'name' => 'core.api.subscription.publisher.report.sales.boxes',
        'description' => 'total subscription sales report for publisher',
        'endpoint' => '/sales_report_boxes',
        'class' => [SubscriptionSharesController::class, 'report_sales_boxes'],
    ];
    public const SUBSCRIPTION_PUBLISHER_SALES_CHART = [
        'name' => 'core.api.subscription.publisher.sales.chart',
        'description' => 'total subscription sales chart for publisher',
        'endpoint' => '/sales_chart',
        'class' => [SubscriptionSharesController::class, 'sales_report_chart'],
    ];
    public const SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT = [
        'name' => 'core.api.subscription.publisher.report.abstract',
        'description' => 'returns abstract last month report subscription ',
        'endpoint' => '/abstract/',
        'class' => [SubscriptionSharesController::class, 'abstract_report'],
    ];
    public const SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT_CHART = [
        'name' => 'core.api.subscription.publisher.report.abstract.chart',
        'description' => 'returns abstract last month report subscription chart',
        'endpoint' => '/abstract_chart/',
        'class' => [SubscriptionSharesController::class, 'abstract_report_chart'],
    ];
    public const SUBSCRIPTION_PUBLISHER_DOT_CHART = [
        'name' => 'core.api.subscription.publisher.chart.dot',
        'description' => 'returns dashboard dot chart data ',
        'endpoint' => '/dot_chart/',
        'class' => [SubscriptionSharesController::class, 'dashboard_dot_chart'],
    ];
    public const SUBSCRIPTION_PUBLISHER_ENTITIES = [
        'name' => 'core.api.subscription.publisher.report.sales',
        'description' => 'report subscription sales for every publisher per user per book',
        'endpoint' => '/',
        'class' => [SubscriptionEntitiesController::class, 'publisher_entities'],
    ];
    public const SUBSCRIPTION_PUBLISHER_ENTITIES_MOSTLY_SOLD_PREVIOUS_12_MONTHS = [
        'name' => 'core.api.subscription.publisher.report.sales',
        'description' => 'returns last twelve month mostly sold book IDs belongs to the publisher',
        'endpoint' => '/bestseller',
        'class' => [SubscriptionEntitiesController::class, 'publisher_bestseller'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Shuttle Panel API Endpoints (CRM)
    |--------------------------------------------------------------------------
    */
    public const SUBSCRIPTION_SHUTTLE_USER_PLAN_BULK = [
        'name' => 'core.api.subscription.shuttle.bulk.assign.plans',
        'description' => 'bulk assigns plans to Fidibo\'s users',
        'endpoint' => '/bulk_assign',
        'class' => [SubscriptionUsersController::class, 'bulk_assign_plan_users'],
    ];
    public const SUBSCRIPTION_SHUTTLE_USER_PLAN_ASSIGNMENT_LIST = [
        'name' => 'core.api.subscription.shuttle.bulk.assignment.list',
        'description' => 'bulk assignments list',
        'endpoint' => '/bulk_assign',
        'class' => [SubscriptionUsersController::class, 'bulk_assignment_list'],
    ];
    public const SUBSCRIPTION_SHUTTLE_USER_HISTORIES_STORE = [
        'name' => 'core.api.subscription.user_history.crm_assign_entity_to_user',
        'description' => 'store subscription user history types',
        'endpoint' => '/user_histories/{userId}/{entityId}',
        'class' => [SubscriptionUserHistoriesController::class, 'crm_assign_entity_to_user'],
    ];
    public const SUBSCRIPTION_USERS_CHANGES = [
        'name' => 'core.api.subscription.shuttle.user.changes',
        'description' => 'returns all the subscription users that has been changed by CRM operators',
        'endpoint' => '/changes',
        'class' => [SubscriptionUsersController::class, 'get_user_changes'],
    ];
    public const SUBSCRIPTION_USERS_CONTENT_CHANGES = [
        'name' => 'core.api.subscription.shuttle.user.content.changes',
        'description' => 'returns all the subscription user contents that has been added to subscription users by CRM operators',
        'endpoint' => '/content/changes',
        'class' => [SubscriptionUserHistoriesController::class, 'crm_get_user_history_changes'],
    ];
    public const SUBSCRIPTION_USERS_REMOVE_CONTENT = [
        'name' => 'core.api.subscription.shuttle.user.content.remove',
        'description' => 'removes all the subscription user histories for a single user and single entity for user active plan',
        'endpoint' => '/content/remove/{userId}/{entityId}',
        'class' => [SubscriptionUserHistoriesController::class, 'crm_delete_entity_from_library'],
    ];
    public const SUBSCRIPTION_USERS_LIST = [
        'name' => 'core.api.subscription.shuttle.user.list',
        'description' => 'returns all the subscription users with all the plans they have got and their status',
        'endpoint' => '/',
        'class' => [SubscriptionUsersController::class, 'list_users_CRM'],
    ];
    public const SUBSCRIPTION_USERS_ACTIVE_PLAN = [
        'name' => 'core.api.subscription.shuttle.user.active_plan',
        'description' => 'returns user active_plan and all of its contents',
        'endpoint' => '/{userId}',
        'class' => [SubscriptionUsersController::class, 'get_user_active_plan'],
    ];
    public const SUBSCRIPTION_USERS_ACTIVE_PLAN_CONTENTS = [
        'name' => 'core.api.subscription.shuttle.user.active_plan.contents',
        'description' => 'returns user active_plan contents',
        'endpoint' => '/{userId}/contents',
        'class' => [SubscriptionUsersController::class, 'get_user_active_plan_contents'],
    ];
    public const SUBSCRIPTION_USERS_ACTIVE_PLAN_HISTORY = [
        'name' => 'core.api.subscription.shuttle.user.active_plan.history',
        'description' => 'returns user active_plan content histories',
        'endpoint' => '/{userId}/history',
        'class' => [SubscriptionUsersController::class, 'get_user_active_plan_histories'],
    ];
    public const SUBSCRIPTION_USERS_PASSED_PLANS = [
        'name' => 'core.api.subscription.shuttle.user.passed_plan',
        'description' => 'returns user passed_plan and all of its contents',
        'endpoint' => '/{userId}',
        'class' => [SubscriptionUsersController::class, 'get_user_passed_plans'],
    ];
    public const SUBSCRIPTION_USERS_PASSED_PLAN_CONTENTS = [
        'name' => 'core.api.subscription.shuttle.user.passed_plan.contents',
        'description' => 'returns user passed_plan contents',
        'endpoint' => '/{userId}/{planId}/contents',
        'class' => [SubscriptionUsersController::class, 'get_user_passed_plan_contents'],
    ];
    public const SUBSCRIPTION_USERS_PASSED_PLAN_HISTORY = [
        'name' => 'core.api.subscription.shuttle.user.passed_plan.history',
        'description' => 'returns user passed_plan histories',
        'endpoint' => '/{userId}/{planId}/history',
        'class' => [SubscriptionUsersController::class, 'get_user_passed_plan_histories'],
    ];
    public const SUBSCRIPTION_USERS_FUTURE_PLANS = [
        'name' => 'core.api.subscription.shuttle.user.future_plan',
        'description' => 'returns user future_plan and all of its contents',
        'endpoint' => '/{userId}',
        'class' => [SubscriptionUsersController::class, 'get_user_future_plans'],
    ];
    public const SUBSCRIPTION_SHUTTLE_PUBLISHER_SHARE_REPORT = [
        'name' => 'core.api.subscription.shuttle.share_report.publisher',
        'description' => 'reports shares from the publishers viewpoint',
        'endpoint' => '/publisher',
        'class' => [SubscriptionSharesController::class, 'crm_publisher_shares_report'],
    ];
    public const SUBSCRIPTION_SHUTTLE_SHARE_REPORT = [
        'name' => 'core.api.subscription.shuttle.share_report',
        'description' => 'returns share reports considering Fidibo\'s subscription revenue.',
        'endpoint' => '',
        'class' => [SubscriptionSharesController::class, 'crm_shares_report'],
    ];
}
