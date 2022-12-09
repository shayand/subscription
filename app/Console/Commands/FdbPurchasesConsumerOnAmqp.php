<?php

namespace App\Console\Commands;

use Amqp;
use App\Models\SubscriptionBoughtHistories;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;

class FdbPurchasesConsumerOnAmqp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:fdb:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consumer of FDB purchases for user that have active subscription plan';

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
        $this->info('📙  subscription:fdb:consumer.');
        $this->comment('Attempting to consume queue...');
        Amqp::consume(env('AMQP_QUEUE_FDB','subscription-orders-purchase'), function ($message, $resolver) {
            try{
                $messageArray = json_decode($message->body, true);
                $userId = $messageArray['user_id'];
                if (array_key_exists('book_ids',$messageArray)) {
                    $booksArray = $messageArray['book_ids'];
                    foreach ($booksArray as $singleBook){
                        $boughtHistory = new SubscriptionBoughtHistories();
                        $boughtHistory->user_id = $userId;
                        $boughtHistory->entity_id = $singleBook;
                        $boughtHistory->saveOrFail();
                    }
                } else {
                    $this->_createPaymentUser($messageArray);
                }
                $resolver->acknowledge($message);

            } catch(\Exception $exception){
//                $resolver->acknowledge($message);
            }
            $resolver->stopWhenProcessed();
        });
    }

    /**
     * @param array $inputData
     */
    private function _createPaymentUser(array $inputData){
        //{"user_id":27998,"plan_id":3,"currency":"toman","device_id":32149364,"device_type":"android","app_version":"9.1.4","store_id":1,"payment_id":3423038,"payment_type":"payment","price":25000,"campaign_id":81,"discount_code":"fidiplus","discount_price":15500}
        $currentPlans = SubscriptionUsers::getLastActivePlan($inputData['user_id']);

        if($currentPlans == false){
            $startDate = new \DateTime();
        } else {
            $startDate = date('Y-m-d H:i:s',strtotime($currentPlans['end_date'] . '+1 minutes'));
        }

        (!isset($inputData['campaign_id']) || (int)($inputData['campaign_id']) === 0) && $inputData['is_processed'] = 1;

        SubscriptionPayment::create($inputData);

        $subscriptionUsers = new SubscriptionUsers();
        $subscriptionUsers->user_id = $inputData['user_id'];
        $subscriptionUsers->plan_id = $inputData['plan_id'];
        $subscriptionUsers->start_date = $startDate;
        $subscriptionUsers->saveOrFail();
    }
}
