<?php

namespace App\Console\Commands;

use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;

class PlanExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:plan:expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set plan expiration time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $passedPlans = SubscriptionUsers::getAllUsersPassedPlans();
        foreach ($passedPlans as $passedPlan){
            $userPlanId = $passedPlan->id;
            // soft delete histories of specific user plan
            SubscriptionUserHistories::where('subscription_user_id',$userPlanId)->delete();
            // delete main record of subscription user
            SubscriptionUsers::where('id',$userPlanId)->delete();
        }
    }
}
