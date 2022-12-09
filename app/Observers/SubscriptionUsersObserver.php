<?php

namespace App\Observers;

use App\Models\SubscriptionPlans;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionUsers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class SubscriptionUsersObserver
 * @package App\Observers
 */
class SubscriptionUsersObserver
{
    /**
     * @param SubscriptionUsers $subscriptionUsers
     */
    public function creating(SubscriptionUsers $subscriptionUsers)
    {
        try{
            $singlePlan = SubscriptionPlans::findOrFail($subscriptionUsers['plan_id']);
            // convert to minutes
            $subscriptionUsers->duration = $singlePlan->duration * 60 * 24;
            $singlePlanArray = $singlePlan->toArray();
            if ($subscriptionUsers->getTrackingId() != null){
                $singlePlanArray['tracking_id'] = $subscriptionUsers->getTrackingId();
            }

            $subscriptionUsers->plan_details = $singlePlanArray;

            Log::info('[SubscriptionUsersObserver][creating] create user logs');
        } catch (\Exception $exception){
            Log::error('[SubscriptionUsersObserver][creating]'.$exception->getMessage());
        }
    }

    /**
     * @param SubscriptionUsers $subscriptionUsers
     */
    public function created(SubscriptionUsers $subscriptionUsers)
    {
        $settlmentDuration = 30;
        $startDate = Carbon::parse($subscriptionUsers->start_date);
        try{
            // convert duration in minutes to duration in days
            $duration = $subscriptionUsers['duration'] / (24 * 60);

            if ($duration <= $settlmentDuration) {
                $model = SubscriptionSettelmentPeriods::create([
                    'subscription_user_id' => $subscriptionUsers->id,
                    'settelment_date' => $startDate->addDays($duration),
                    'settlement_duration' => $duration
                ]);

            } else {
                $totalRecords = (int) $duration / $settlmentDuration;

                for($i=1;$i <= $totalRecords;$i++){
                    $model = SubscriptionSettelmentPeriods::create([
                        'subscription_user_id' => $subscriptionUsers->id,
                        'settelment_date' => $startDate->addDays($settlmentDuration),
                        'settlement_duration' => $settlmentDuration
                    ]);
                }

                $remainDays = fmod($duration,$settlmentDuration);

                if($remainDays > 0) {
                    $model2 = SubscriptionSettelmentPeriods::create([
                        'subscription_user_id' => $subscriptionUsers->id,
                        'settelment_date' => $startDate->addDays($remainDays),
                        'settlement_duration' => $remainDays
                    ]);
                }
            }

            Log::info('[SubscriptionUsersObserver][created] create settelement records');
        } catch (\Exception $exception){
            Log::error('[SubscriptionUsersObserver][created]'.$exception->getMessage());
        }
    }
}
