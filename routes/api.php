<?php

use App\Constants\Endpoints;
use App\Http\Controllers\SubscriptionPartnersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::get('handshake', function () {
    return response()->json(['status' => 'OK'], Response::HTTP_OK);
});
Route::get(Endpoints::SUBSCRIPTION_METRIC['endpoint'],Endpoints::SUBSCRIPTION_METRIC['class']);
Route::get(Endpoints::SUBSCRIPTION_Utilities['endpoint'],Endpoints::SUBSCRIPTION_Utilities['class']);

/*
|--------------------------------------------------------------------------
| Mobile and Shuttle-Panel API Routes
|--------------------------------------------------------------------------
*/

Route::apiResource(Endpoints::SUBSCRIPTION_PLANS['endpoint'],Endpoints::SUBSCRIPTION_PLANS['class']);
Route::get(Endpoints::SUBSCRIPTION_PLANS_PANEL_LIST['endpoint'], Endpoints::SUBSCRIPTION_PLANS_PANEL_LIST['class']);

Route::apiResource(Endpoints::SUBSCRIPTION_ENTITIES['endpoint'],Endpoints::SUBSCRIPTION_ENTITIES['class']);
Route::post(Endpoints::SUBSCRIPTION_ENTITIES_MANUAL_STORE['endpoint'],Endpoints::SUBSCRIPTION_ENTITIES_MANUAL_STORE['class']);
Route::post(Endpoints::SUBSCRIPTION_ENTITIES_EXCEL_STORE['endpoint'],Endpoints::SUBSCRIPTION_ENTITIES_EXCEL_STORE['class']);
Route::apiResource(Endpoints::SUBSCRIPTION_PAYMENTS['endpoint'],Endpoints::SUBSCRIPTION_PAYMENTS['class'])->only('index', 'store', 'show');

// SUBSCRIPTION PARTNER PLANS ROUTES
Route::apiResource(Endpoints::SUBSCRIPTION_PARTNERS['endpoint'],Endpoints::SUBSCRIPTION_PARTNERS['class']);
Route::apiResource(Endpoints::SUBSCRIPTION_PARTNERS_PLANS['endpoint'],Endpoints::SUBSCRIPTION_PARTNERS_PLANS['class'])->only( 'store', 'destroy');
Route::post(Endpoints::SUBSCRIPTION_PARTNERS_TRACKING['endpoint'], Endpoints::SUBSCRIPTION_PARTNERS_TRACKING['class']);
Route::post(Endpoints::SUBSCRIPTION_PARTNERS_CHECK_TRACKING['endpoint'], Endpoints::SUBSCRIPTION_PARTNERS_CHECK_TRACKING['class']);
Route::post(Endpoints::SUBSCRIPTION_PARTNERS_PLANS_LIST['endpoint'], Endpoints::SUBSCRIPTION_PARTNERS_PLANS_LIST['class']);

Route::apiResource(Endpoints::SUBSCRIPTION_PAYMENTS['endpoint'],Endpoints::SUBSCRIPTION_PAYMENTS['class'])->only('index', 'store', 'show');

// SUBSCRIPTION_PLAN_ENTITY_ROUTES
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_IS_SUBSCRIPTION['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_IS_SUBSCRIPTION['class']);


// SUBSCRIPTION_PLAN_ENTITY_ROUTES
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_IS_SUBSCRIPTION['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_IS_SUBSCRIPTION['class']);
Route::get(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_INDEX['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_INDEX['class']);
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_STORE['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_STORE['class']);
Route::get(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SHOW['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SHOW['class']);
Route::put(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_UPDATE['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_UPDATE['class']);
Route::delete(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_REMOVE['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_REMOVE['class']);
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_AVAILABLE_CONTENT['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_CHECK_AVAILABLE_CONTENT['class']);
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SYNC_CONTENT['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SYNC_CONTENT['class']);
Route::post(Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SYNC_USER_CONTENT['endpoint'], Endpoints::SUBSCRIPTION_PLAN_ENTITIES_SYNC_USER_CONTENT['class']);

// SUBSCRIPTION USER HISTORY ROUTES
Route::get(Endpoints::SUBSCRIPTION_USER_HISTORIES_INDEX['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_INDEX['class']);
Route::post(Endpoints::SUBSCRIPTION_USER_HISTORIES_STORE['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_STORE['class']);
Route::get(Endpoints::SUBSCRIPTION_USER_HISTORIES_SHOW['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_SHOW['class']);
Route::put(Endpoints::SUBSCRIPTION_USER_HISTORIES_UPDATE['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_UPDATE['class']);
Route::delete(Endpoints::SUBSCRIPTION_USER_HISTORIES_REMOVE['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_REMOVE['class']);
Route::post(Endpoints::SUBSCRIPTION_USER_HISTORIES_BOUGHT['endpoint'], Endpoints::SUBSCRIPTION_USER_HISTORIES_BOUGHT['class']);

// SUBSCRIPTION SETTLEMENT PERIODS ROUTES
Route::get(Endpoints::SUBSCRIPTION_SETTELMENT_PERIODS['endpoint'], Endpoints::SUBSCRIPTION_SETTELMENT_PERIODS['class']);
Route::post(Endpoints::SUBSCRIPTION_SETTELMENT_TEST_CREATOR['endpoint'], Endpoints::SUBSCRIPTION_SETTELMENT_TEST_CREATOR['class']);
//Route::post(Endpoints::SUBSCRIPTION_SETTELMENT_JOB_RUNNER['endpoint'], Endpoints::SUBSCRIPTION_SETTELMENT_JOB_RUNNER['class']);

Route::get(Endpoints::SUBSCRIPTION_USERS_PLAN_LIST['endpoint'],Endpoints::SUBSCRIPTION_USERS_PLAN_LIST['class']);
Route::post(Endpoints::SUBSCRIPTION_USERS_PLAN_CREATE['endpoint'],Endpoints::SUBSCRIPTION_USERS_PLAN_CREATE['class']);
Route::post(Endpoints::SUBSCRIPTION_USERS_CHECK_SUBSCRIPTION['endpoint'],Endpoints::SUBSCRIPTION_USERS_CHECK_SUBSCRIPTION['class']);
Route::get(Endpoints::SUBSCRIPTION_USERS_GET_STATUS['endpoint'],Endpoints::SUBSCRIPTION_USERS_GET_STATUS['class']);
Route::get(Endpoints::SUBSCRIPTION_USERS_PLAN_READ['endpoint'],Endpoints::SUBSCRIPTION_USERS_PLAN_READ['class']);
Route::delete(Endpoints::SUBSCRIPTION_USERS_PLAN_DELETE['endpoint'],Endpoints::SUBSCRIPTION_USERS_PLAN_DELETE['class']);

/*
|--------------------------------------------------------------------------
| Publisher Panel API Routes
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'publisher', 'middleware' => ['publisher_auth']], function() {
    Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_SALES_REPORT['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_SALES_REPORT['class']);
    Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_SALES_CHART['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_SALES_CHART['class']);
    Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_SALES_REPORT_BOXES['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_SALES_REPORT_BOXES['class']);

    Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_ENTITIES['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_ENTITIES['class']);

    Route::prefix('dashboard')->group(function () {
        Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT['class']);
        Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_ENTITIES_MOSTLY_SOLD_PREVIOUS_12_MONTHS['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_ENTITIES_MOSTLY_SOLD_PREVIOUS_12_MONTHS['class']);
        Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT_CHART['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_ABSTRACT_REPORT_CHART['class']);
        Route::get(Endpoints::SUBSCRIPTION_PUBLISHER_DOT_CHART['endpoint'],Endpoints::SUBSCRIPTION_PUBLISHER_DOT_CHART['class']);
    });
});

/*
|--------------------------------------------------------------------------
| Shuttle Panel API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'crm', 'middleware' => ['shuttle_auth']], function() {
    Route::post(Endpoints::SUBSCRIPTION_SHUTTLE_USER_PLAN_BULK['endpoint'],Endpoints::SUBSCRIPTION_SHUTTLE_USER_PLAN_BULK['class']);
    Route::get(Endpoints::SUBSCRIPTION_SHUTTLE_USER_PLAN_ASSIGNMENT_LIST['endpoint'],Endpoints::SUBSCRIPTION_SHUTTLE_USER_PLAN_ASSIGNMENT_LIST['class']);
    Route::post(Endpoints::SUBSCRIPTION_SHUTTLE_USER_HISTORIES_STORE['endpoint'],Endpoints::SUBSCRIPTION_SHUTTLE_USER_HISTORIES_STORE['class']);
    Route::get(Endpoints::SUBSCRIPTION_USERS_PLAN_ASSIGNMENT_REASONS['endpoint'],Endpoints::SUBSCRIPTION_USERS_PLAN_ASSIGNMENT_REASONS['class']);

    Route::prefix('share_report')->group(function() {
        Route::get(Endpoints::SUBSCRIPTION_SHUTTLE_PUBLISHER_SHARE_REPORT['endpoint'],Endpoints::SUBSCRIPTION_SHUTTLE_PUBLISHER_SHARE_REPORT['class']);
    });

    Route::prefix('users')->group(function() {
        Route::get(Endpoints::SUBSCRIPTION_USERS_LIST['endpoint'],Endpoints::SUBSCRIPTION_USERS_LIST['class']);
        Route::get(Endpoints::SUBSCRIPTION_USERS_CHANGES['endpoint'],Endpoints::SUBSCRIPTION_USERS_CHANGES['class']);
        Route::get(Endpoints::SUBSCRIPTION_USERS_CONTENT_CHANGES['endpoint'],Endpoints::SUBSCRIPTION_USERS_CONTENT_CHANGES['class']);
        Route::delete(Endpoints::SUBSCRIPTION_USERS_REMOVE_CONTENT['endpoint'],Endpoints::SUBSCRIPTION_USERS_REMOVE_CONTENT['class']);

        Route::prefix('active')->group(function () {
            Route::get(Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN['endpoint'],Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN['class']);
            Route::get(Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN_CONTENTS['endpoint'],Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN_CONTENTS['class']);
            Route::get(Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN_HISTORY['endpoint'],Endpoints::SUBSCRIPTION_USERS_ACTIVE_PLAN_HISTORY['class']);
        });

        Route::prefix('passed')->group(function () {
            Route::get(Endpoints::SUBSCRIPTION_USERS_PASSED_PLANS['endpoint'],Endpoints::SUBSCRIPTION_USERS_PASSED_PLANS['class']);
            Route::get(Endpoints::SUBSCRIPTION_USERS_PASSED_PLAN_CONTENTS['endpoint'],Endpoints::SUBSCRIPTION_USERS_PASSED_PLAN_CONTENTS['class']);
            Route::get(Endpoints::SUBSCRIPTION_USERS_PASSED_PLAN_HISTORY['endpoint'],Endpoints::SUBSCRIPTION_USERS_PASSED_PLAN_HISTORY['class']);
        });

        Route::prefix('future')->group(function () {
            Route::get(Endpoints::SUBSCRIPTION_USERS_FUTURE_PLANS['endpoint'],Endpoints::SUBSCRIPTION_USERS_FUTURE_PLANS['class']);
        });
    });
});



