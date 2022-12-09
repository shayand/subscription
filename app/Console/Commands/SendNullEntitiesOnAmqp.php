<?php

namespace App\Console\Commands;

use Amqp;
use App\Http\Resources\SubscriptionPurchaseResource;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use Illuminate\Console\Command;

class SendNullEntitiesOnAmqp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-null-entities:elastic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Null Entities to Elastic Search Queue';

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

        $entities = SubscriptionEntities::all();

        $final = [];
        foreach ($entities as $single){
            $seId = $single->id;
            $entityId = $single->entity_id;
            $entityTitle = 'book_'. $entityId;

//            if (count($plansArray) != 0){
                $final[] = ['_id' => $entityTitle,'modifications' => ['subscription' => []]];
//            }
        }

        $message = json_encode(['index' => 'fidibo-content-v1.0','cols' => $final]);
        Amqp::publish('/', $message, ['queue' => env('AMQP_QUEUE_SUBSCRIPTION')]);
    }
}
