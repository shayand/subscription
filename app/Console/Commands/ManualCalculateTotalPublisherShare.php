<?php

namespace App\Console\Commands;

use App\Models\SubscriptionJobs;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionShareItems;
use App\Models\SubscriptionShares;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManualCalculateTotalPublisherShare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:shares_calculation:manual_run {--time=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the job that is responsible for calculating publisher shares.';

    protected SubscriptionJobs $subscriptionJobs;
    protected int $settlementDuration;

    protected array $calculatePhase1 = [];
    protected array $calculatePhase2 = [];
    protected array $calculatePhase3 = [];
    protected array $calculatePhase4 = [];

    protected array $user_id_to_settlement_duration_dict = [];
    protected array $secondDenominator = [];
    protected array $thirdDenominator = [];
    protected float $normalizeDenominator = 0.0;
    protected $now;

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
        $this->info('📙  Fidibo:Microservice:Subscription Shares Calculator Manual started.');
        $this->comment('Attempting to calculate shares and settle with publishers...');
        if($this->option('time')) {
            $time = $this->option('time');
        } else {
            $time = Carbon::now()->timezone('Asia/Tehran')->toDateTimeLocalString();
        }

        $this->now = $time;

        $this->subscriptionJobs = new SubscriptionJobs();
        $this->subscriptionJobs->uuid = Str::orderedUuid();
        $this->subscriptionJobs->save();

        $this->calculatePhaseOne();
        $this->calculatePhaseTwo();
        $this->calculatePhaseThree();

        foreach ($this->calculatePhase3 as $subscriptionUser) {
            $this->calculatePhaseFour($subscriptionUser);
        }
        $this->store_share_results_to_DB();
        // $this->calculatePhase4['user_id_to_settlement_duration_dict'] = $this->user_id_to_settlement_duration_dict;
        // $this->subscriptionJobs->update(['subscription_users_entities_shares' => $this->calculatePhase4]);

        $json_string = json_encode($this->subscriptionJobs, JSON_PRETTY_PRINT);
        print_r($json_string."\n");

        return 0;
    }

    protected function calculatePhaseOne()
    {
        // Gets latest settlements that must be settled based on the settlement_date field
        $this->calculatePhase1 = SubscriptionSettelmentPeriods::getLatestSettlements($this->now);
        try {
            $settlements = json_encode($this->calculatePhase1['settlements']);
            $this->subscriptionJobs->update(['settlements' => $settlements]);

            print_r($this->calculatePhase1);
            foreach ($this->calculatePhase1['settlements'] as $settlement) {
                $this->user_id_to_settlement_duration_dict[$settlement->subscription_user_id] = $settlement;
            }
            print("Phase1_Status: Successful\n");
        } catch (\Exception $exception) {
            print_r("subscription_job_phase_one_failed: ".$exception->getMessage()."\n");
        }
    }

    protected function calculatePhaseTwo()
    {
        $this->calculatePhase2 = SubscriptionUsers::getSubscriptionUsersByID($this->calculatePhase1['settlement_user_IDs']);
        try {
            $this->subscriptionJobs->update(['subscription_users' => $this->calculatePhase2]);
            print_r("Phase2_Status: Successful"."\n");
        } catch (\Exception $exception) {
            print_r("subscription_job_phase_two_failed: ".$exception->getMessage()."\n");
        }
    }

    protected function calculatePhaseThree()
    {
        $this->calculatePhase3 = $this->calculatePhase2;

        for ($i = 0; $i < count($this->calculatePhase3); $i++) {
            $subscription_user = $this->calculatePhase3[$i];
            $userPlanEntityIDS = SubscriptionUserHistories::getUserPlanEntitiesHistory($subscription_user->id, $subscription_user->plan_id);
//            dd($userPlanEntityIDS);
            $entities = [];
            foreach ($userPlanEntityIDS as $planEntityID) {
                $entityHistory = SubscriptionUserHistories::getEntityAndEntityHistories($subscription_user->id, $planEntityID);
                $entityHistories = $entityHistory['entity_histories'];
                // dd($entityHistories);
                $entity = $entityHistory['entity'];

                if (isset($this->thirdDenominator[sprintf("%d", $subscription_user->id)])) {
                    $this->thirdDenominator[sprintf("%d", $subscription_user->id)] += $entity['publisher_share'];
                    $this->secondDenominator[sprintf("%d", $subscription_user->id)] += $entity['price'] * $entity['price_factor'];
                } else {
                    $this->thirdDenominator[sprintf("%d", $subscription_user->id)] = $entity['publisher_share'];
                    $this->secondDenominator[sprintf("%d", $subscription_user->id)] = $entity['price'] * $entity['price_factor'];
                }

                $timeOnTheTable = SubscriptionUserHistories::calculateTimeEntityWasOnTheTable($entityHistories, $this->user_id_to_settlement_duration_dict[$subscription_user->id], $this->now);
                if ($timeOnTheTable > 0) {
                    array_push($entities, [
                        'entity' => $entity,
                        'on_the_table_time' => $timeOnTheTable
                    ]);
                } else {
                    print_r("[Phase3:calculateDaysEntityOnTheTable: got error in calculation or something else]\n");
                }
            }
            $this->calculatePhase3[$i]->entities = $entities;
        }
        print_r($this->calculatePhase3);
        $this->subscriptionJobs->update(['subscription_users_entities' => $this->calculatePhase3]);
    }

    protected function calculatePhaseFour($subscription_user)
    {
        $entities = $subscription_user->entities;
        $newEntities = [];

        $plan_details = json_decode($subscription_user->plan_details);
        $totalPublisherShareAmount = ($plan_details->total_publisher_share * $plan_details->price) / 100;
//        dd($this->user_id_to_settlement_duration_dict[$subscription_user->id], $plan_details->duration);
        $totalPublisherShareAmountPerMonth = $totalPublisherShareAmount * ($this->user_id_to_settlement_duration_dict[$subscription_user->id]->settlement_duration / $plan_details->duration);

        $this->normalizeDenominator = 0.0;
        foreach ($entities as $entity) {
            $first_statement = ($entity['on_the_table_time'] / ( ( $this->user_id_to_settlement_duration_dict[$subscription_user->id]->settlement_duration ) * 24 * 60 ) );
//            dd($first_statement,( ( $this->user_id_to_settlement_duration_dict[$subscription_user->id]->settlement_duration ) * 24 * 60 ),$entity['on_the_table_time']);

            if ($this->secondDenominator[sprintf("%d", $subscription_user->id)] != 0) {
                $second_statement = 1 + ( ($entity['entity']['price'] * $entity['entity']['price_factor']) / $this->secondDenominator[sprintf("%d", $subscription_user->id)]);
            } else {
                continue;
            }
            // print_r(sprintf("secondDenominator:%d\n", $this->secondDenominator[sprintf("%d", $subscription_user->id)]));
//            $second_statement = 1 + ( ($entity['entity']['price'] * $entity['entity']['price_factor']) / $this->secondDenominator[sprintf("%d", $subscription_user->id)]);
//            $third_statement =  1 + ($entity['entity']['publisher_share'] / $this->thirdDenominator[sprintf("%d", $subscription_user->id)]);
            // print_r(sprintf("thirdDenominator:%d\n", $this->thirdDenominator[sprintf("%d", $subscription_user->id)]));
            if ($this->thirdDenominator[sprintf("%d", $subscription_user->id)] != 0) {
                $third_statement = 1 + ($entity['entity']['publisher_share'] / $this->thirdDenominator[sprintf("%d", $subscription_user->id)]);
            } else {
                continue;
            }

            $entity['publisher_share_per_user_per_bookI'] = $first_statement * $second_statement * $third_statement;
            $this->normalizeDenominator += $entity['publisher_share_per_user_per_bookI'];
            array_push($newEntities, $entity);
        }
        // dd($entities,$newEntities);

        $finalEntities = [];
        foreach ($newEntities as $entity) {
            $entity['normalize_publisher_share_per_user_per_bookI'] = ($entity['publisher_share_per_user_per_bookI'] / $this->normalizeDenominator );
//            $this->normalizeDenominator += $entity['publisher_share_per_user_per_bookI'];
//            dd($entity['normalize_publisher_share_per_user_per_bookI'], $totalPublisherShareAmountPerMonth);
            $entity['calculated_publisher_share_amount'] = $entity['normalize_publisher_share_per_user_per_bookI'] * $totalPublisherShareAmountPerMonth;
            $entity['constant_fidibo_share_amount'] =  $plan_details->price * ( (100 - $plan_details->total_publisher_share) / 100);
            array_push($finalEntities, $entity);
        }

        array_push($this->calculatePhase4, [
            'id' => $subscription_user->id,
            'user_id' => $subscription_user->user_id,
            'plan_id' => $subscription_user->plan_id,
            'plan_details' => $subscription_user->plan_details,
            'entities' => $finalEntities,
        ]);
        $this->subscriptionJobs->update(['subscription_users_entities_shares' => $this->calculatePhase4]);
    }

    protected function store_share_results_to_DB() {
        $newEntities = [];
        $finalCalculations = [];

        $results = $this->subscriptionJobs['subscription_users_entities_shares'];
        if ($results == null)
        {
            return;
        }
        foreach ($results as $subscription_user) {
            foreach ($subscription_user['entities'] as $entity) {
                $previousPaidShare = SubscriptionShares::CalculatePreviousPaidPublisherShareAmount($subscription_user['id'], $subscription_user['plan_id'], $entity['entity']['id']);
                $entity['previous_paid_share'] = $previousPaidShare;
                $publisherShare = ($entity['calculated_publisher_share_amount']);

                // Check not to pay to publisher more than entity_price
                $entity['max_to_pay'] = ($entity['entity']['price'] * ($entity['entity']['publisher_share'] / 100 ) );
                if ($publisherShare + $previousPaidShare >  $entity['max_to_pay']) {
                    $publisherShare = $entity['max_to_pay'] - $previousPaidShare;
                    if ($publisherShare < 0) {
                        $publisherShare = 0;
                    }
                }

                $entityHistories = SubscriptionUserHistories::getUserPlanEntityHistories($subscription_user['id'], $subscription_user['plan_id'], $entity['entity']['id']);
                $isValid = SubscriptionUserHistories::isEntityValidForPublisherShare($entityHistories['entity_histories'], $entity);
                if (!$isValid[0]) {
                    $publisherShare = 0;
                }

                $subscription_share = [
                    'subscription_user_id' => $subscription_user['id'],
                    'subscription_entity_id' => $entity['entity']['id'],
                    'total_duration' => $this->user_id_to_settlement_duration_dict[$subscription_user['id']]->settlement_duration,
                    'total_calculated_amount' => $entity['calculated_publisher_share_amount'],
                    'publisher_share_amount' => $publisherShare,
                    'publisher_previous_paid_share_amount' => $previousPaidShare,
                    'valid_to_pay' => $isValid[0],
                    'on_the_table' => $entity['on_the_table_time'],
                    'read_percent' => $isValid[1]
                ];

                $entity['total_calculated_amount'] = $entity['calculated_publisher_share_amount'];
                $entity['publisher_share_amount'] = $publisherShare;
                $entity['valid_to_pay'] = $isValid;

                $subscriptionShareModel = new SubscriptionShares($subscription_share);
                $subscriptionShareModel->save();

                $planEntity = SubscriptionPlanEntities::query()->where([
                    'entity_id' => $entity['entity']['id'],
                    'plan_id' => $subscription_user['plan_id']
                ])->get()->toArray();
                // dd(count($planEntity));

                if (count($planEntity) == 0) {
                    continue;
                }

                $share_item = new SubscriptionShareItems([
                    'subscription_user_id' => $subscription_user['id'],
                    'subscription_plan_entity_id' => $planEntity[0]['id'],
                    'subscription_share_id' => $subscriptionShareModel->id
                ]);
                $share_item->save();
                array_push($newEntities, $entity);
            }
            $subscription_user['entities'] = $newEntities;
            // dd($subscription_user['entities']);
            if (array_key_exists(sprintf("%d", $subscription_user['id']), $this->secondDenominator)) {
                $subscription_user['second_denominator'] = $this->secondDenominator[sprintf("%d", $subscription_user['id'])];
            } else {
                $subscription_user['second_denominator'] = "";
            }

            if (array_key_exists(sprintf("%d", $subscription_user['id']), $this->thirdDenominator)) {
                $subscription_user['third_denominator'] = $this->thirdDenominator[sprintf("%d", $subscription_user['id'])];
            } else {
                $subscription_user['third_denominator'] = "";
            }
            array_push($finalCalculations, $subscription_user);
            // dd($finalCalculations);
            $userSettlementID = $this->user_id_to_settlement_duration_dict[$subscription_user['id']]->id;
            // print_r(sprintf("%d\n",$userSettlementID));
            $settlement = SubscriptionSettelmentPeriods::query()->find($userSettlementID);
            $settlement->update(['is_settled' => 1]);
        }
        $this->subscriptionJobs->update(['subscription_users_entities_shares' => $finalCalculations]);
    }
}
