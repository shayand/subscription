<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPartnersPlans;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;

class DigiPlusManualConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:digiplus-manual:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'manual digiplus user plan assignment';

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
        $this->info('📙  subscription:digiplus:consumer.');
        $this->comment('Attempting to process new entities resolved from Digiplus integration ...');

        $userDurationArray = [
            [318080,93],
            [523959,93],
            [2798816,68],
            [3751251,90],
            [517483,71],
            [4002495,29],
            [3780090,38],
        ];

        $partnerId = 1;
        foreach ($userDurationArray as $singleUserDuration) {

            $planDetails = SubscriptionPartnersPlans::getPlanDetailsByDuration($partnerId,$singleUserDuration[1]);
            $planObject = $planDetails->first();

            $finalResponse = SubscriptionUsers::createSubscriptionUserSentFromPartners(
                $singleUserDuration[0],
                $planObject['plan_id'] ,
                $planObject->id,
                0
            );
            $this->comment('has been done' . $singleUserDuration[0]);
        }
    }
}
