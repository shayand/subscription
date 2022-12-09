<?php

namespace App\Console\Commands;

use Amqp;
use App\Http\Resources\SubscriptionPurchaseResource;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use Illuminate\Console\Command;

class SendEntitiesToElastic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-entities:elastic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Entities to Elastic Search Queue';

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
        $this->deactivate_ended_plans();

        $entities = SubscriptionEntities::all();

        $final = [];
        foreach ($entities as $single){
            $seId = $single->id;
            $entityId = $single->entity_id;
            $entityTitle = 'book_'. $entityId;
            $planEntities = SubscriptionPlanEntities::query()
                ->where('entity_id','=',$seId)
                ->get();

            $plansArray = [];
            foreach ($planEntities as $singlePlan){
                $plansArray[] = $singlePlan->plan_id;
            }

            if (count($plansArray) != 0){
                $final[] = ['_id' => $entityTitle,'modifications' => ['subscription' => $plansArray]];
            }
        }

        $message = json_encode(['index' => 'fidibo-content-v1.0','cols' => $final]);
        Amqp::publish('/', $message, ['queue' => env('AMQP_QUEUE_SUBSCRIPTION')]);
    }

    protected function deactivate_ended_plans() {
        $plans = SubscriptionPlans::withTrashed()->get();
        foreach ($plans as $plan) {
            $plan->status = 2;
            $plan->save();
        }
    }
}
