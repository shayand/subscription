<?php

namespace App\Observers;

use App\Helpers\Helper;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;

class SubscriptionPlanEntityObserver
{
    /**
     * Handle the SubscriptionPlanEntities "created" event.
     *
     * @param  \App\Models\SubscriptionPlanEntities  $subscriptionPlanEntities
     * @return void
     */
    public function created(SubscriptionPlanEntities $subscriptionPlanEntities)
    {
        $planIDs = SubscriptionPlans::all()->pluck('id');
        $entity = SubscriptionEntities::query()->find($subscriptionPlanEntities->entity_id);

        $entityTitle = 'book_'. $entity->entity_id;
        $sendToQueue = [];
        foreach ($planIDs as $planID) {
            if ($subscriptionPlanEntities->plan_id == $planID) {
                continue;
            }

            SubscriptionPlanEntities::create([
                'entity_id' => $entity->id,
                'plan_id' => $planID,
                'operator_id' => $subscriptionPlanEntities->operator_id
            ]);
        }
        $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $planIDs]];
        Helper::send_to_elastic($sendToQueue);
    }

    /**
     * Handle the SubscriptionPlanEntities "updated" event.
     *
     * @param  \App\Models\SubscriptionPlanEntities  $subscriptionPlanEntities
     * @return void
     */
    public function updated(SubscriptionPlanEntities $subscriptionPlanEntities)
    {
        //
    }

    /**
     * Handle the SubscriptionPlanEntities "deleted" event.
     *
     * @param  \App\Models\SubscriptionPlanEntities  $subscriptionPlanEntities
     * @return void
     */
    public function deleted(SubscriptionPlanEntities $subscriptionPlanEntities)
    {
        $planIDs = SubscriptionPlans::all()->pluck('id')->toArray();
        $entity = SubscriptionEntities::query()->find($subscriptionPlanEntities->entity_id);
        array_diff($planIDs, [$subscriptionPlanEntities->plan_id]);
        $planEntityIDs = SubscriptionPlanEntities::query()->whereIn('plan_id', $planIDs)
            ->where('entity_id', '=', $entity->id)->pluck('id');
        SubscriptionPlanEntities::destroy( $planEntityIDs );

        $entityTitle = 'book_'. $entity->entity_id;
        $sendToQueue = [];
        $sendToQueue[] = ['_id' => $entityTitle,'modifications' => ['subscription' => []]];
        Helper::send_to_elastic( $sendToQueue );
    }

    /**
     * Handle the SubscriptionPlanEntities "restored" event.
     *
     * @param  \App\Models\SubscriptionPlanEntities  $subscriptionPlanEntities
     * @return void
     */
    public function restored(SubscriptionPlanEntities $subscriptionPlanEntities)
    {
        //
    }

    /**
     * Handle the SubscriptionPlanEntities "force deleted" event.
     *
     * @param  \App\Models\SubscriptionPlanEntities  $subscriptionPlanEntities
     * @return void
     */
    public function forceDeleted(SubscriptionPlanEntities $subscriptionPlanEntities)
    {
        //
    }
}
