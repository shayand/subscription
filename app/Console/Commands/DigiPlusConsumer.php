<?php

namespace App\Console\Commands;

use Amqp;
use App\Exceptions\PartnerTrackingTransitionException;
use App\Models\SubscriptionPartnersPlans;
use App\Models\SubscriptionPartnersTracking;
use App\Models\SubscriptionUsers;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DigiPlusConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:digiplus:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digiplus Consumer';

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
     */
    public function handle()
    {
        if (getenv('MASTER_CLUSTER') == false){
            return 0;
        }
        $this->info('📙  subscription:digiplus:consumer.');
        $this->comment('Attempting to process new entities resolved from Digiplus integration ...');

        $unTrackedEntities = SubscriptionPartnersTracking::get_unprocessed_entities();
        foreach ($unTrackedEntities as $unTrackedEntity) {
            $phoneNumber = $unTrackedEntity->phone;
            $partnerPlanId = $unTrackedEntity->partner_plan_id;
            $res = SubscriptionPartnersPlans::getPlanDetailsByPartnerPlanId($partnerPlanId);
            $firstResponse = $res->first();

            $papiResponse =  null;
            if ($unTrackedEntity->is_delivered_fdb == 0) {
                try {
                    $papiResponse = self::callFDB($unTrackedEntity, $phoneNumber, $firstResponse['duration']);
                    $this->info('📙  papi called for '. $phoneNumber);
                } catch (\Exception $exception) {
                    continue;
                }
            }

            // $unTrackedEntity->is_fdb_processed == 0 && ($unTrackedEntity->is_delivered_fdb == 0 || 1)
            try {
                $this->info('📙  prepare to creating plan '. $phoneNumber);
                self::create_user($unTrackedEntity, $partnerPlanId, $firstResponse, $papiResponse,$unTrackedEntity->id);
            } catch (\Exception $exception) {
                continue;
            }
        }
    }

    protected static function callFDB($unTrackedEntity, $phoneNumber , $duration = null) {
        $guzzle = new Client();

        $sendArray = [ 'mobile' =>  $phoneNumber ];
        if($duration != null){
            $sendArray['duration'] = $duration;
        }

        $response = $guzzle->post('https://papi.fidibo.com/User/checkUserExistsOrRegister',[
            'json' => $sendArray
        ]);
        $papiResponse = json_decode($response->getBody()->getContents(),true);
        if ($papiResponse['output']['result'] == false) {
            throw new \Exception("Non of the input IDs are valid.");
        }
        $unTrackedEntity->is_delivered_fdb = 1;
        $unTrackedEntity->save();

        return $papiResponse;
    }

    /**
     * @param $unTrackedEntity
     * @param $partnerPlanId
     * @param $res
     * @param $papiResponse
     * @throws \Throwable
     */
    protected static function create_user($unTrackedEntity,$partnerPlanId, $res, $papiResponse, $trackingId = null) {
        $papiResponse['output']['user_type'] == 'exists' ? $newUser = 0 : $newUser = 1;

        DB::beginTransaction();

        try {
            SubscriptionUsers::createSubscriptionUserSentFromPartners(
                $papiResponse['output']['user_id'],
                $res['plan_id'] ,
                $partnerPlanId,
                $newUser,
                $trackingId
            );
            $unTrackedEntity->is_fdb_processed = 1;
            $unTrackedEntity->save();

            DB::commit();
        } catch (\Exception $exception){
            DB::rollBack();
            throw new PartnerTrackingTransitionException('User ID: ' . $papiResponse['output']['user_id'] . ' tracking id:' . $trackingId);
        }
    }
}
