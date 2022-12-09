<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use Illuminate\Console\Command;

class DigiplusCleanData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:data:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $plan164 = SubscriptionPlanEntities::query()
            ->where('plan_id','=',164)
            ->orderBy('id','ASC')
            ->get();

        $this->comment('ssss');

        $plans = SubscriptionPlans::all();

        foreach ($plans as $singlePlan){
            foreach ($plan164 as $singleEntity){
                $innerEntities = SubscriptionPlanEntities::query()
                    ->where('plan_id','=',$singlePlan)
                    ->where('entity_id','=',$singleEntity)
                    ->count();

                if($innerEntities == 0){
                    $newPlansEntities = new SubscriptionPlanEntities();
                    $newPlansEntities->plan_id = $singlePlan->id;
                    $newPlansEntities->entity_id = $singleEntity->entity_id;
                    $newPlansEntities->operator_id = 447708;
                    $newPlansEntities->saveOrFail();

                    $this->comment('The entity: '. $singleEntity->entity_id .' for this plan: ' . $singlePlan->id);
                }
            }
        }

    }
}
