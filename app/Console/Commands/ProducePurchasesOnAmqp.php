<?php

namespace App\Console\Commands;

use Amqp;
use App\Http\Resources\SubscriptionPurchaseResource;
use App\Models\SubscriptionPayment;
use Illuminate\Console\Command;

class ProducePurchasesOnAmqp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:purchase:produce';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Produce subscription purchases to queue';

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
     * @return void
     */
    public function handle(): void
    {
        try {
            $modelRestrictions = ['wheres' => [['column' => 'campaign_id', 'sign' => '!=', 'value' => 0],['column' => 'is_processed', 'value' => 0]]];
            $subscriptions = (new SubscriptionPayment)->getResources(['*'], $modelRestrictions);

            /**@var \App\Models\SubscriptionPayment $item*/
            foreach ($subscriptions as $item) {
                $message = json_encode(SubscriptionPurchaseResource::make($item), JSON_THROW_ON_ERROR);
                Amqp::publish('/', $message, [
                    'queue' => 'campaign-subscription-purchase',
//                    'exchange_type' => 'direct',
//                    'exchange' => 'campaign.direct',
                ]);
                $item->setAttribute('is_processed', 1)->save();
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }
}
