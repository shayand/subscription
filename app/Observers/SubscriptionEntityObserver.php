<?php

namespace App\Observers;

use App\Helpers\Helper;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use GuzzleHttp\Client;

class SubscriptionEntityObserver
{
    /**
     * Handle the SubscriptionEntities "created" event.
     *
     * @param  \App\Models\SubscriptionEntities  $subscriptionEntity
     * @return void
     */
    public function created(SubscriptionEntities $subscriptionEntity)
    {

    }

    /**
     * Handle the SubscriptionEntities "updated" event.
     *
     * @param  \App\Models\SubscriptionEntities  $subscriptionEntities
     * @return void
     */
    public function updated(SubscriptionEntities $subscriptionEntities)
    {
        //
    }

    /**
     * Handle the SubscriptionEntities "deleted" event.
     *
     * @param  \App\Models\SubscriptionEntities  $subscriptionEntities
     * @return void
     */
    public function deleted(SubscriptionEntities $subscriptionEntities)
    {
        //
    }

    /**
     * Handle the SubscriptionEntities "restored" event.
     *
     * @param  \App\Models\SubscriptionEntities  $subscriptionEntities
     * @return void
     */
    public function restored(SubscriptionEntities $subscriptionEntities)
    {
        //
    }

    /**
     * Handle the SubscriptionEntities "force deleted" event.
     *
     * @param  \App\Models\SubscriptionEntities  $subscriptionEntities
     * @return void
     */
    public function forceDeleted(SubscriptionEntities $subscriptionEntities)
    {
        //
    }
}
