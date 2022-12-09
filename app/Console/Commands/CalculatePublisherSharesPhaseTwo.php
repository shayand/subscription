<?php

namespace App\Console\Commands;

use App\Constants\QueueName;
use App\Jobs\CalculateTotalPublishersShareJob;
use App\Models\SubscriptionJobs;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionShareItems;
use App\Models\SubscriptionShares;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;


class CalculatePublisherSharesPhaseTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:shares_calculation_two:run {--time=}';

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
    protected float $normalizeDenominator = 0.0;

    protected array $user_idds = [];
    protected array $user = [];

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
        $this->info('📙  Fidibo:Microservice:Subscription Shares Calculator started.');
        $this->comment('Attempting to calculate shares and settle with publishers...');

        $time = null;
        if( $this->option('time') ) {
            $time = $this->option('time');
        } else {
            $time = Carbon::now()->timezone('Asia/Tehran')->toDateTimeLocalString();
        }

        $this->now = $time;

        $this->subscriptionJobs = new SubscriptionJobs();
        $this->subscriptionJobs->uuid = Str::orderedUuid();
        $this->subscriptionJobs->save();

        $this->startSettlement();

        $this->print_result();

        $json_string = json_encode($this->subscriptionJobs, JSON_PRETTY_PRINT);

        return 0;
    }

    protected function startSettlement() {
        $my_array = array_fill(0, 5, 0);
        $this->withProgressBar($my_array, function ($i) {
            static $cnt = 1;

            $this->output->write("<info> Calculating phase => </info>");
            $this->output->write("<info>$cnt</info>");

            if ($cnt == 1) {
                $this->calculatePhaseOne();
            } else if ($cnt == 2) {
                $this->calculatePhaseTwo();
            } else if ($cnt == 3) {
                $this->calculatePhaseThree();
            } else if ($cnt == 4) {
                foreach ($this->calculatePhase3 as $subscriptionUser) {
                    $this->calculatePhaseFour($subscriptionUser);
                }
            } else if ($cnt == 5) {
                $this->store_share_results_to_DB();
            }

            sleep(0.4);
            $cnt+=1;

        });
    }

    protected function calculatePhaseOne()
    {
        // Gets latest settlements that must be settled based on the settlement_date field
        $this->calculatePhase1 = SubscriptionSettelmentPeriods::getLatestSettlements($this->now);
        try {
            $settlements = json_encode($this->calculatePhase1['settlements']);
            $this->subscriptionJobs->update(['settlements' => $settlements]);

            foreach ($this->calculatePhase1['settlements'] as $settlement) {
                $this->user_id_to_settlement_duration_dict[$settlement->id] = $settlement;
            }
        } catch (\Exception $exception) {
            print_r("subscription_job_phase_one_failed: ".$exception->getMessage()."\n");
        }
    }

    protected function calculatePhaseTwo()
    {
        // TODO return new attributes from following method:: Done and must be tested
        $this->calculatePhase2 = SubscriptionUsers::getSubscriptionUsersByID($this->calculatePhase1['settlement_user_IDs']);
        try {
            $this->subscriptionJobs->update(['subscription_users' => $this->calculatePhase2]);
        } catch (\Exception $exception) {
            print_r("subscription_job_phase_two_failed: ".$exception->getMessage()."\n");
        }

        foreach ($this->calculatePhase2 as $su) {
            $this->user_idds[] = $su['user_id'];
        }
        $this->user_idds = array_unique($this->user_idds);
    }

    protected function calculatePhaseThree()
    {
        $this->calculatePhase3 = $this->calculatePhase2;

        for ($i = 0; $i < count($this->calculatePhase3); $i++) {
            $subscription_user_settlement = $this->calculatePhase3[$i];

            if (!array_key_exists($subscription_user_settlement['settlement_id'], $this->user_id_to_settlement_duration_dict)) {
                continue;
            }
            $user_settlement = $this->user_id_to_settlement_duration_dict[$subscription_user_settlement['settlement_id']];
            $totalEnd = new \DateTime($user_settlement['settelment_date'], new \DateTimeZone('Asia/Tehran'));
            $totalStart = clone($totalEnd);
            $totalStart->sub( new \DateInterval( sprintf("P%dD", $user_settlement['settlement_duration']) ) );

            $userPlanEntityIDS = SubscriptionUserHistories::getUserPlanEntitiesHistory($subscription_user_settlement['id'], $subscription_user_settlement['plan_id'], $totalStart->format("Y-m-d H:i:s"), $totalEnd->format("Y-m-d H:i:s"));

            $entities = [];
            foreach ($userPlanEntityIDS as $planEntityID) {
                $entityHistory = SubscriptionUserHistories::getEntityAndEntityHistories($subscription_user_settlement['id'], $planEntityID);
                $entityHistories = $entityHistory['entity_histories'];
                $entity = $entityHistory['entity'];

                if (isset($this->secondDenominator[sprintf("%d", $subscription_user_settlement['settlement_id'])])) {
                    //TODO third denominator removal: Done
//                    $this->thirdDenominator[sprintf("%d", $subscription_user->id)] += $entity['publisher_share'];
                    $this->secondDenominator[sprintf("%d", $subscription_user_settlement['settlement_id'])] += $entity['price'] * $entity['price_factor'];
                } else {
                    //TODO third denominator removal: Done
//                    $this->thirdDenominator[sprintf("%d", $subscription_user->id)] = $entity['publisher_share'];
                    $this->secondDenominator[sprintf("%d", $subscription_user_settlement['settlement_id'])] = $entity['price'] * $entity['price_factor'];
                }

                $timeOnTheTable = SubscriptionUserHistories::calculateTimeEntityWasOnTheTable($entityHistories, $user_settlement, $this->now);
                if ($timeOnTheTable > 0) {
                    array_push($entities, [
                        'entity' => $entity,
                        'on_the_table_time' => $timeOnTheTable
                    ]);

                } else {
                    print_r(sprintf("calculated time on the table for entity::%d settlement_id:%d is 0. \n",
                        $entity['price'],
                        $subscription_user_settlement['settlement_id']
                    ));
                }
            }
            $this->calculatePhase3[$i]['entities'] = $entities;
        }
        $this->subscriptionJobs->update(['subscription_users_entities' => $this->calculatePhase3]);
    }

    protected function calculatePhaseFour($subscription_user)
    {
        $entities = $subscription_user['entities'];
        $newEntities = [];

        $plan_details = $subscription_user['plan_details'];

//        // Phase 2
        $totalPublisherShareAmount = $subscription_user['amount'];

        $totalPublisherShareAmountPerMonth = $totalPublisherShareAmount * ($this->user_id_to_settlement_duration_dict[$subscription_user['settlement_id']]->settlement_duration / $plan_details['duration']);

        $finalEntities = [];
        if (count($entities) == 0) {
            $this->settle_user_with_zero_entities($subscription_user['settlement_id']);
            return;
        } else {
            $this->normalizeDenominator = 0.0;
            foreach ($entities as $entity) {
                $first_statement = ($entity['on_the_table_time'] / ( ( $this->user_id_to_settlement_duration_dict[$subscription_user['settlement_id']]->settlement_duration ) * 24 * 60 ) );

                if ($this->secondDenominator[sprintf("%d", $subscription_user['settlement_id'])] != 0) {
                    $second_statement = 1 + ( ($entity['entity']['price'] * $entity['entity']['price_factor']) / $this->secondDenominator[sprintf("%d", $subscription_user['settlement_id'])]);
                } else {
                    continue;
                }
//            $second_statement = 1 + ( ($entity['entity']['price'] * $entity['entity']['price_factor']) / $this->secondDenominator[sprintf("%d", $subscription_user->id)]);
//            $third_statement =  1 + ($entity['entity']['publisher_share'] / $this->thirdDenominator[sprintf("%d", $subscription_user->id)]);
                //TODO remove third statement
//            if ($this->thirdDenominator[sprintf("%d", $subscription_user['id'])] != 0) {
////                $third_statement = 1 + ($entity['entity']['publisher_share'] / $this->thirdDenominator[sprintf("%d", $subscription_user->id)]);
//            } else {
//                continue;
//            }

                $entity['publisher_share_per_user_per_bookI'] = $first_statement * $second_statement;
                $this->normalizeDenominator += $entity['publisher_share_per_user_per_bookI'];
                array_push($newEntities, $entity);
            }

            foreach ($newEntities as $entity) {
                $entity['normalize_publisher_share_per_user_per_bookI'] = ($entity['publisher_share_per_user_per_bookI'] / $this->normalizeDenominator );
//            $this->normalizeDenominator += $entity['publisher_share_per_user_per_bookI'];
//            $entity['calculated_publisher_share_amount'] = $entity['normalize_publisher_share_per_user_per_bookI'] * $subscription_user['amount'];

                $bookShare = $entity['normalize_publisher_share_per_user_per_bookI'] * $totalPublisherShareAmountPerMonth;

                // Phase 2
                $entity['calculated_publisher_share_amount'] = $bookShare * ($entity['entity']['publisher_marketshare'] / 100);
                $entity['book_share'] = $bookShare;

                // Phase 1
//            $entity['constant_fidibo_share_amount'] =  $plan_details['price'] * ( (100 - $plan_details['total_publisher_share']) / 100);

                // Phase 2
                $entity['constant_fidibo_share_amount'] =  ( $bookShare * ( (100 - $entity['entity']['publisher_marketshare']) / 100) );

                array_push($finalEntities, $entity);
            }
        }

        array_push($this->calculatePhase4, [
            'id' => $subscription_user['id'],
            'user_id' => $subscription_user['user_id'],
            'plan_id' => $subscription_user['plan_id'],
            'plan_details' => $subscription_user['plan_details'],
            'settlement_id' => $subscription_user['settlement_id'],
            'amount' => $subscription_user['amount'],
            'payment_id' => $subscription_user['payment_id'],
            'payment_type' => $subscription_user['payment_type'],
            'credit_id' => $subscription_user['credit_id'],
            'discount_price' => $subscription_user['discount_price'],
            'discount_code' => $subscription_user['discount_code'],
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
            global $hadEntities;
            $hadEntities =
            $settlement_users = [];
            foreach ($subscription_user['entities'] as $entity) {
                $hadEntities = 1;
                $previousPaidShare = SubscriptionShares::CalculatePreviousPaidPublisherShareAmount($subscription_user['id'], $subscription_user['plan_id'], $entity['entity']['id']);
                $entity['previous_paid_share'] = $previousPaidShare;
                $calculatedPublisherShare = ($entity['calculated_publisher_share_amount']);

                // Check not to pay to publisher more than entity_price
                $entity['max_to_pay'] = ($entity['entity']['price'] * ( $entity['entity']['publisher_marketshare'] / 100 ) );
                $publisherShare = $calculatedPublisherShare;
                if ($calculatedPublisherShare + $previousPaidShare >  $entity['max_to_pay']) {
                    $publisherShare = $entity['max_to_pay'] - $previousPaidShare;
                    if ($publisherShare < 0) {
                        $publisherShare = 0;
                    }
                }

                $entityHistories = SubscriptionUserHistories::getUserPlanEntityHistories($subscription_user['id'], $subscription_user['plan_id'], $entity['entity']['id']);
                $isValid = SubscriptionUserHistories::isEntityValidForPublisherShare($entityHistories['entity_histories'], $entity);
                if ($isValid[0] == 0) {
                    $publisherShare = 0;
                }

                $dynamicPublisherShare = $calculatedPublisherShare - $publisherShare;

                $subscription_share = [
                    'subscription_user_id' => $subscription_user['id'],
                    'subscription_entity_id' => $entity['entity']['id'],
                    'total_duration' => $this->user_id_to_settlement_duration_dict[$subscription_user['settlement_id']]->settlement_duration,
                    'total_calculated_amount' => round($entity['calculated_publisher_share_amount'], 1),
                    'publisher_share_amount' => round($publisherShare, 1),
                    'publisher_previous_paid_share_amount' => round($previousPaidShare, 1),
                    'valid_to_pay' => $isValid[0],
                    'on_the_table' => $entity['on_the_table_time'],
                    'read_percent' => $isValid[1],
                    'subscription_settlement_id' => $subscription_user['settlement_id'],
                    'book_share' => $entity['book_share'],
                    'fidibo_static_share' => $entity['constant_fidibo_share_amount'],
                    'fidibo_dynamic_share' => $dynamicPublisherShare,
                    'publisher_market_share' => $entity['entity']['publisher_marketshare']
                ];

                $settlement_user = [];
                $settlement_user['subscription_user_id'] = $subscription_user['id'];
                $settlement_user['subscription_entity_id'] = $entity['entity']['id'];
                $settlement_user['entity_price'] = $entity['entity']['price'];
                $settlement_user['publisher_marketshare'] = $entity['entity']['publisher_marketshare'];
                $settlement_user['subscription_settlement_id'] = $subscription_user['settlement_id'];
                $settlement_user['on_the_table_time'] = $entity['on_the_table_time'];
                $settlement_user['plan_id'] = $subscription_user['plan_id'];
                $settlement_user['total_calculated_amount'] = round($entity['calculated_publisher_share_amount'], 1);
                $settlement_user['book_share'] = round($entity['book_share'], 1);
                $settlement_user['publisher_share_amount'] = round($publisherShare, 1);
                $settlement_user['publisher_share_to_pay'] = round($calculatedPublisherShare, 1);
                $settlement_user['fidibo_dynamic_share'] = round($dynamicPublisherShare, 1);
                $settlement_user['fidibo_static_share'] = round($entity['constant_fidibo_share_amount'], 1);
                $settlement_user['max_to_pay'] = $entity['max_to_pay'];
                $settlement_user['publisher_previous_paid_share_amount'] = round($previousPaidShare, 1);
                $settlement_user['plan_details'] = $subscription_user['plan_details'];
                $settlement_user['valid_to_pay'] = ($isValid[0] == 1 ? 'valid_to_pay' : 'invalid_to_pay');
                $settlement_user['read_percent'] = $isValid[1];
                $settlement_user['settlement_duration'] = $this->user_id_to_settlement_duration_dict[$subscription_user['settlement_id']]->settlement_duration;
                $settlement_users[] = $settlement_user;

                $entity['total_calculated_amount'] = round($entity['calculated_publisher_share_amount'], 1);
                $entity['publisher_share_amount'] = $publisherShare;
                $entity['valid_to_pay'] = $isValid;

                $subscriptionShareModel = new SubscriptionShares($subscription_share);
                $subscriptionShareModel->save();

                $planEntity = SubscriptionPlanEntities::query()->where([
                    'entity_id' => $entity['entity']['id'],
                    'plan_id' => $subscription_user['plan_id']
                ])->get()->toArray();

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

            if (array_key_exists(sprintf("%d", $subscription_user['settlement_id']), $this->secondDenominator)) {
                $subscription_user['second_denominator'] = $this->secondDenominator[sprintf("%d", $subscription_user['settlement_id'])];
            } else {
                $subscription_user['second_denominator'] = "";
            }

            array_push($finalCalculations, $subscription_user);

            $this->user[$subscription_user['user_id']][$subscription_user['id']]['user_settlements'][] = $settlement_users;
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['payment_id'] = $subscription_user['payment_id'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['payment_type'] = $subscription_user['payment_type'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['amount'] = $subscription_user['amount'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['plan_price'] = $subscription_user['plan_details']['price'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['credit_id'] = $subscription_user['credit_id'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['discount_price'] = $subscription_user['discount_price'];
            $this->user[$subscription_user['user_id']][$subscription_user['id']]['discount_code'] = $subscription_user['discount_code'];
            $this->user[$subscription_user['user_id']]['subscription_user'][] = $subscription_user['id'];

            $userSettlementID = $this->user_id_to_settlement_duration_dict[$subscription_user['settlement_id']]->id;
            $settlement = SubscriptionSettelmentPeriods::query()->find($userSettlementID);
            $settlement->update([
                'is_settled' => 1,
                'had_entity' => 1
            ]);

            $this->user[$subscription_user['user_id']]['subscription_user'] = array_unique($this->user[$subscription_user['user_id']]['subscription_user']);
        }

        $this->subscriptionJobs->update(['subscription_users_entities_shares' => $finalCalculations]);
    }

    protected function print_result () {
        $this->newLine(3);

        $table = new Table($this->output);

        $table->setHeaders([
            'entity_id', 'plan_id', 'duration',
            'on_the_table', '$book_share', 'pub_market_share',
            'pub_share_to_pay', 'entity_price', 'max_to_pay', 'valid_pay', 'percent',
            'fdb_static_share', 'fdb_dynamic_share','pub_share_amount', 'prev_paid_amount'
        ]);

        $separator = new TableSeparator();
        foreach ($this->user_idds as $id) {
            $subscription_user_ids = $this->user[$id]['subscription_user'];
            $subscription_user_data = $this->user[$id];

            $table->addRow([
                new TableCell(sprintf("user_id:%d",$id), ['colspan' => 14]),
            ]);

            $table->addRow($separator);
            foreach ($subscription_user_ids as $subscription_user_id) {
                $subscription_user = $subscription_user_data[$subscription_user_id];
                $subscription_user_settlements = $subscription_user_data[$subscription_user_id]['user_settlements'];
//                $settlements = SubscriptionSettelmentPeriods::query()->where('subscription_user_id', '=', $subscription_user_id)->first();

                $table->addRow([
                    new TableCell(sprintf("subscription_user_id:%d",$subscription_user_id), ['colspan' => 6]),

                    new TableCell(sprintf("payment_id:%d",$subscription_user['payment_id']), ['colspan' => 2]),
                    new TableCell(sprintf("plan_price:%d",$subscription_user['plan_price']), ['colspan' => 2]),
                    new TableCell(sprintf("discount_price:%d",$subscription_user['discount_price']), ['colspan' => 2]),
                    new TableCell(sprintf("paid_amount:%d",$subscription_user['amount']), ['colspan' => 2]),
                ]);

                $table->addRow($separator);
                $counter = 0;
                foreach ($subscription_user_settlements as $settlements) {
//                    dd($settlements);
                    if (count($settlements) == 0) {
                        continue;
                    }

                    $table->addRow([
                        new TableCell(sprintf("subscription_settlement_id:%d",$settlements[$counter]['subscription_settlement_id']), ['colspan' => 14]),

                    ]);
                    $table->addRow($separator);

                    foreach ($settlements as $item) {
                        $table->addRow([
                            $item['subscription_entity_id'],
                            $item['plan_id'],
                            $item['settlement_duration'],
//                            $item['plan_details']['price'],
                            $item['on_the_table_time'],
                            $item['book_share'],
                            $item['publisher_marketshare'],
                            $item['publisher_share_to_pay'],
                            $item['entity_price'],
                            $item['max_to_pay'],
                            $item['valid_to_pay'],
                            $item['read_percent'],
                            $item['fidibo_static_share'],
                            $item['fidibo_dynamic_share'],
//                            $item['total_calculated_amount'],
                            $item['publisher_share_amount'],
                            $item['publisher_previous_paid_share_amount'],

                        ]);
                        $table->addRow($separator);
                    }
                }
                $table->addRow($separator);
            }
        }
        $table->render();
    }

    protected function settle_user_with_zero_entities ($userSettlementID) {
        $settlement = SubscriptionSettelmentPeriods::query()->find($userSettlementID);
        $settlement->update([
            'is_settled' => 1,
            'had_entity' => 0
        ]);
    }
}
