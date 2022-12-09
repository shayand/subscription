<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPartners;
use App\Models\SubscriptionPartnersPlans;
use App\Models\SubscriptionPlans;
use App\Traits\MSLog;
use Illuminate\Console\Command;

class CreatePartnerPlans extends Command
{
    use MSLog;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:partners:create-plans {partner}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create plans for partners with different duration';


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
     * @throws \Throwable
     */
    public function handle()
    {
        $partnerName = $this->argument('partner');
        $partnerDetails = SubscriptionPartners::where('endpoint_path' , $partnerName)->get();
        for ($i = 140; $i <= 300;$i++){
            if ($i == 30 || $i == 90) {
                continue;
            }
            $contentCapacity = 50 * round($i / 30);
            if($contentCapacity < 50){
                $contentCapacity = 50;
            }
            $array = [
                'duration' => $i,
                'capacity_audio' => $contentCapacity / 2,
                'capacity_book' => $contentCapacity / 2,
            ];

            $newPlan = new SubscriptionPlans();
            $newPlan->title = sprintf('پلن %s روزه (دیجی‌پلاس)',$i);
            $newPlan->price = 0;
            $newPlan->price_usd = 0;
            $newPlan->duration = $i;
            $newPlan->max_books = $contentCapacity/2;
            $newPlan->max_audios = $contentCapacity/2;
            $newPlan->store_id = 1;
            $newPlan->total_publisher_share = 50;
            $newPlan->is_show = 0;
            $newPlan->start_date = date('Y-m-d');
            $newPlan->operator_id = 447708;
            $newPlan->saveOrFail();

            $partnerPlan = new SubscriptionPartnersPlans();
            $partnerPlan->partner_id = $partnerDetails[0]['id'];
            $partnerPlan->plan_id = $newPlan->id;
            $partnerPlan->saveOrFail();

            $this->info(json_encode($array));
            $this->log('Command','handle',[$array]);
        }
        return 0;
    }
}
