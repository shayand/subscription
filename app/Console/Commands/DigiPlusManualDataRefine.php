<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPartnersPlans;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;

class DigiPlusManualDataRefine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:digiplus-manual:data-refine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'manual digiplus data refine';

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
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle()
    {
        $this->info('subscription:digiplus-manual:data-refine');

        // user plans 9 10 and partner plan
        $userQuery = SubscriptionUsers::query()
            ->where('partner_plan_id','=','2')
            ->whereNotNull('partner_plan_id')
            ->whereRaw("JSON_EXTRACT(subscription_users.plan_details,'$.max_books') = 30")
            ->get();

        foreach ($userQuery as $singleUser) {
            $planDetails = $singleUser['plan_details'];
            $planDetails['max_books'] = 75;
            $planDetails['max_audios'] = 75;

            $singleUser->plan_details = $planDetails;
            $singleUser->saveOrFail();

            $this->comment('user with this id has been refined: ' . $singleUser['user_id']);
        }
    }
}
