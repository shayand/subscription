<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class TestSettlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:settlement_test:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test different scenarios that could happen in a settlement process';

    protected $user_idds = [];
    protected $test_user = [];
    protected $subscription_users = [];
    protected $subscription_payments = [];
    protected $subscription_settlements = [];
    protected $settlement_dates = [];

    /**
     * @var CalculatePublisherSharesPhaseTwo
     */
    protected CalculatePublisherSharesPhaseTwo $calculatePublisherSharesPhaseTwo;

    /**
     * @param CalculatePublisherSharesPhaseTwo $calculatePublisherSharesPhaseTwo
     */
    public function __invoke(CalculatePublisherSharesPhaseTwo $calculatePublisherSharesPhaseTwo)
    {
        $this->$calculatePublisherSharesPhaseTwo = $calculatePublisherSharesPhaseTwo;
    }

    protected int $user_id_start = 50000000;

    protected array $user_id_to_settlement_duration_dict = [];
    protected array $secondDenominator = [];
    protected array $thirdDenominator = [];
    protected float $normalizeDenominator = 0.0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->calculatePublisherSharesPhaseTwo = new CalculatePublisherSharesPhaseTwo();
        parent::__construct();
    }

    public function  __destruct()
    {
        $this->delete_items();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->comment('Attempting to test settlement.');
        $this->warn('Remember, This is gonna take time!!');
        $this->info('📙...  ');

        while (true) {
            global $userNumbers;
            $userNumbers = $this->choice(
                'How many users you want to create?',
                [1, 2, 3, 4, 5, 6, 7],
                3,
                $maxAttempts = 3,
                $allowMultipleSelections = false
            );

            if ($userNumbers < 1 && $userNumbers > 7) {
                continue;
            }
            break;
        }

        $my_array = array_fill(0, $userNumbers, 0);

        $this->withProgressBar($my_array, function ($i) {
            // Write a single blank line...
            static $cnt = 0;
            $this->newLine();
            $this->create_users();

            $cnt+=1;
        });

        $this->newLine(1);

        $this->print_results();

        $this->newLine(1);

        $this->newLine(1);
        $this->call('subscription:shares_calculation_two:run', [
            '--time' => max($this->settlement_dates),
        ]);


        return 0;
    }

    protected function create_users() {
        static $userId = 0;
        $planNumbers = rand(1,4);
        $this->info(sprintf("User %d(st||nd||rd||th) has %d plans.", $userId, $planNumbers));

        $this->create_subscription_user($planNumbers, $userId);

        $userId+=1;
        return 0;
    }

    protected function create_payment(int $userId, $plan) {
        static $paymentId = 0;
        $discounts = ['0', '25', '50', '75', '100'];

        $discount_percent = $discounts[array_rand($discounts)];

        $device_types = ['android', 'ios'];
        $payment = SubscriptionPayment::create([
            'plan_id' => $plan['id'],
            'store_id' => 1,
            'user_id' => $userId,
            'payment_id'=> 5000000 + $paymentId,
            'amount' => $plan['price'] - ($plan['price'] * ($discount_percent/100)),
            'payment_type' => 'credit',
            'currency' => 'toman',
            'device_id' => 5000000 + $paymentId,
            'device_type' => $device_types[array_rand($device_types)],
            'price' => $plan['price'],
            'discount_code' => null,
            'discount_price' => $plan['price'] * ($discount_percent/100),
            'is_processed' => 1,
            'campaign_id' => 0,
            'credit_id' => 5000000 + $paymentId,
            'app_version' => '900.0.0',
        ]);
        $this->subscription_payments[] = $payment;
        $paymentId+=1;

        return $payment;
    }

    protected function create_subscription_user(int $userPlanNumbers, int $userId) {
        $user_id = $this->user_id_start + $userId;

        $this->user_idds[] = $user_id;

        for ($i = 0; $i < $userPlanNumbers; $i++) {
            $plans = SubscriptionPlans::all()->toArray();
            $plan = $plans[array_rand($plans)];

            $currentPlans = SubscriptionUsers::getLastActivePlan($user_id);
            if($currentPlans == false) {
                $startDate = new \DateTime();
            } else {
                $startDate = date('Y-m-d H:i:s',strtotime($currentPlans['end_date'] . '+1 minutes'));
            }

            $payment = $this->create_payment($user_id, $plan);

            $subscriptionUsers = SubscriptionUsers::create([
                'user_id' => $user_id,
                'plan_id' => $plan['id'],
                'start_date' => $startDate,
                'subscription_payment_id' => $payment->id
            ]);

            $this->history_maker($plan, $subscriptionUsers);

            $this->subscription_users[] = $subscriptionUsers;

            $this->test_user[$subscriptionUsers['user_id']][$subscriptionUsers['id']]['payment'] = $payment;
        }
    }

    protected function history_maker($plan, $subscriptionUser)
    {
//        $start = $subscriptionUser['start_date'];
        $user_settlements_tmp = SubscriptionSettelmentPeriods::query()->where('subscription_user_id', '=', $subscriptionUser['id'])->get()->toArray();

        // randomize how many settlements of user plan settlements should be settled
//        $settlementNum = rand(1, count($user_settlements_tmp));
        $counter = 0;
        $this->test_user[$subscriptionUser['user_id']]['subscription_user'][] = $subscriptionUser['id'];

        foreach ($user_settlements_tmp as $user_settlement_tmp) {
            $settlement_dates = SubscriptionSettelmentPeriods::calculate_settlement_start_date($user_settlement_tmp);
            $time_diff = Helper::absolute_datetime_diff_in_minute($settlement_dates[1], $settlement_dates[0]);
            $entities = SubscriptionEntities::random_specific_number_of_entities($subscriptionUser['plan_id'], 5, 0);
            $distributed_time_per_entities = Helper::distributeRandomNumbersOverSpecificAmount(rand(0, $time_diff), count($entities));

            $response = SubscriptionUserHistories::createTestUserHistory($entities, $subscriptionUser, $user_settlement_tmp, $distributed_time_per_entities);

            $entityIDS = [];
            foreach ($entities as $entity) {
                $entityIDS[] = $entity['id'];
            }
            $this->info(sprintf("settlement_id %d => has %d entities[%s]. => count response %d", $user_settlement_tmp['id'], count($entities), implode(",", $entityIDS), count($response)));

            $this->settlement_dates[] = $user_settlement_tmp['settelment_date'];

            $this->test_user[$subscriptionUser['user_id']][$subscriptionUser['id']]['user_settlements'][] = $response;
            $this->test_user[$subscriptionUser['user_id']]['payment'] = $response;

            $counter++;
        }
    }

    protected function delete_items() {
        foreach ($this->subscription_payments as $item) {
            $item->delete();
        }

        foreach ($this->subscription_users as $item) {
            SubscriptionUserHistories::query()->where('subscription_user_id', '=', $item['id'])->delete();
            SubscriptionSettelmentPeriods::query()->where('subscription_user_id', '=', $item['id'])->delete();
            $item->delete();
        }
    }

    protected function print_results() {
        $this->newLine(3);

        $table = new Table($this->output);

        $table->setHeaders([
            'subscription_entity_id', 'plan_id', 'settlement_duration', 'plan_price', 'on_the_table_time', 'valid_to_pay'
        ]);

        $separator = new TableSeparator();
        foreach ($this->user_idds as $id) {
            $subscription_user_ids = $this->test_user[$id]['subscription_user'];
            $user_settlements_data = $this->test_user[$id];

            $table->addRow([
                new TableCell(sprintf("user_id:%d",$id), ['colspan' => 2]),
            ]);

            $table->addRow($separator);
            foreach ($subscription_user_ids as $subscription_user_id) {
                $user_payment_data = $this->test_user[$id][$subscription_user_id]['payment'];

                $settlement_data = $user_settlements_data[$subscription_user_id]['user_settlements'];
                $table->addRow([
                    new TableCell(sprintf("subscription_user_id:%d",$subscription_user_id), ['colspan' => 1]),

                    new TableCell(sprintf("payment_id:%d",$user_payment_data['id']), ['colspan' => 2]),
                    new TableCell(sprintf("discount_price:%d",$user_payment_data['discount_price']), ['colspan' => 1]),
                    new TableCell(sprintf("paid_amount:%d",$user_payment_data['amount']), ['colspan' => 2]),
                ]);

                $table->addRow($separator);
                $counter = 0;
                foreach ($settlement_data as $items) {
                    $table->addRow([
                        new TableCell(sprintf("subscription_settlement_id:%d",$items[$counter]['subscription_settlement_id']), ['colspan' => 6]),

                    ]);
                    $table->addRow($separator);
                    if ($items[$counter]['had_entity'] == 0) {
                        continue;
                    }

                    foreach ($items as $item) {
                        $table->addRow([
                            $item['subscription_entity_id'],
                            $item['plan_id'],
                            $item['settlement_duration'],
                            $item['plan_details']['price'],
                            $item['on_the_table_time'],
                            $item['valid_to_pay'],
                        ]);
                        $table->addRow($separator);
                    }
                }
                $table->addRow($separator);
            }
        }

        $table->render();
    }
}
