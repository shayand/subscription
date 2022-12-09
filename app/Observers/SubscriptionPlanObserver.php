<?php

namespace App\Observers;

use App\Helpers\Helper;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlans;

class SubscriptionPlanObserver
{
    /**
     * @param SubscriptionPlans $subscriptionPlan
     */
    public function creating(SubscriptionPlans $subscriptionPlan)
    {
        // SubscriptionPlans::check_and_set_activation_status($subscriptionPlan);
    }

    /**
     * Handle the SubscriptionPlans "created" event.
     *
     * @param SubscriptionPlans $subscriptionPlan
     * @return void
     */
    public function created(SubscriptionPlans $subscriptionPlan)
    {
        SubscriptionPlans::create_plan_entities($subscriptionPlan->id, $subscriptionPlan->operator_id);
        SubscriptionPlans::update_elastic();
    }

    /**
     * Handle the SubscriptionPlans "updating" event.
     *
     * @param  \App\Models\SubscriptionPlans  $subscriptionPlan
     * @return void
     */
    public function updating(SubscriptionPlans $subscriptionPlan)
    {
        // SubscriptionPlans::check_and_set_activation_status($subscriptionPlan);
    }

    /**
     * Handle the SubscriptionPlans "updated" event.
     *
     * @param  \App\Models\SubscriptionPlans  $subscriptionPlan
     * @return void
     */
    public function updated(SubscriptionPlans $subscriptionPlan)
    {
        //
    }

    /**
     * Handle the SubscriptionPlans "deleted" event.
     *
     * @param SubscriptionPlans $subscriptionPlan
     * @return void
     */
    public function deleted(SubscriptionPlans $subscriptionPlan)
    {
        SubscriptionPlans::remove_plan_entities($subscriptionPlan->id);
        SubscriptionPlans::update_elastic();
    }

    /**
     * Handle the SubscriptionPlans "restored" event.
     *
     * @param  \App\Models\SubscriptionPlans  $subscriptionPlans
     * @return void
     */
    public function restored(SubscriptionPlans $subscriptionPlans)
    {
        //
    }

    /**
     * Handle the SubscriptionPlans "force deleted" event.
     *
     * @param  \App\Models\SubscriptionPlans  $subscriptionPlans
     * @return void
     */
    public function forceDeleted(SubscriptionPlans $subscriptionPlans)
    {
        //
    }
}
