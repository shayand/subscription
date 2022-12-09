<?php

namespace App\Observers;

use App\Models\SubscriptionUserHistories;
use Illuminate\Support\Facades\Log;

class SubscriptionUserHistoriesObserver
{
    /**
     * @param SubscriptionUserHistories $userHistory
     */
    public function creating(SubscriptionUserHistories $userHistory)
    {
        try{
            Log::info('[SubscriptionUserHistoriesController][store] checking whether a subscription user history for' .
                ' subscription_plan_entity_id:subscription_user_id => '. $userHistory->subscription_plan_entity_id . ":" .
                $userHistory->subscription_user_id . ' has been added before or not.');

            $userHistoriesBefore = SubscriptionUserHistories::query()
                ->where(['subscription_plan_entity_id' => $userHistory->subscription_plan_entity_id, 'subscription_user_id' => $userHistory->subscription_user_id])
                ->orderBy('id','desc')->limit(1)->get();

            if($userHistoriesBefore->count() > 0) {
                $userHistory->is_logged = 1;
            }

            Log::info('[SubscriptionUserHistoriesObserver][creating] creating subscription user history logs');
        } catch (\Exception $exception){
            Log::error('[SubscriptionUserHistoriesObserver][creating]'.$exception->getMessage());
        }
    }


    /**
     * Handle the SubscriptionUserHistories "created" event.
     *
     * @param  SubscriptionUserHistories  $userHistory
     * @return void
     */
    public function created(SubscriptionUserHistories $userHistory)
    {
        if($userHistory->is_logged == 1) {

            Log::info('[SubscriptionUserHistoriesController][store] subscription user for' .
                ' subscription_plan_entity_id:subscription_user_id => '. $userHistory->subscription_plan_entity_id . ":" .
                $userHistory->subscription_user_id . ' has been saved before. is_logged is 1.');

        } else {
            Log::info('[SubscriptionUserHistoriesController][store] new subscription user for' .
                ' subscription_plan_entity_id:subscription_user_id => '. $userHistory->subscription_plan_entity_id . ":" .
                $userHistory->subscription_user_id . ' has been saved and is_logged is 0.');
        }
    }

    /**
     * Handle the SubscriptionUserHistories "updated" event.
     *
     * @param  SubscriptionUserHistories  $subscriptionUserHistories
     * @return void
     */
    public function updated(SubscriptionUserHistories $subscriptionUserHistories)
    {
        //
    }

    /**
     * Handle the SubscriptionUserHistories "deleted" event.
     *
     * @param  SubscriptionUserHistories  $subscriptionUserHistories
     * @return void
     */
    public function deleted(SubscriptionUserHistories $subscriptionUserHistories)
    {
        //
    }

    /**
     * Handle the SubscriptionUserHistories "restored" event.
     *
     * @param  SubscriptionUserHistories  $subscriptionUserHistories
     * @return void
     */
    public function restored(SubscriptionUserHistories $subscriptionUserHistories)
    {
        //
    }

    /**
     * Handle the SubscriptionUserHistories "force deleted" event.
     *
     * @param  SubscriptionUserHistories  $subscriptionUserHistories
     * @return void
     */
    public function forceDeleted(SubscriptionUserHistories $subscriptionUserHistories)
    {
        //
    }
}
