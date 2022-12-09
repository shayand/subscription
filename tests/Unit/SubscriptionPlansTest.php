<?php

namespace Tests\Unit;

use App\Models\SubscriptionPlans;
use Faker\Factory;
use Faker\Generator;
use Tests\TestCase;

class SubscriptionPlansTest extends TestCase
{
    /**
     * @var Factory
     */
    private Generator $faker;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testDbInsert()
    {
        for ($i = 0; $i < 50;$i++){
            $subscriptionPlan = new SubscriptionPlans();
            $subscriptionPlan->title = $this->faker->word();
            $subscriptionPlan->start_date = $this->faker->date('Y-m-d H:i:s','now');
            $subscriptionPlan->price  = $this->faker->numberBetween(100000,900000);
            $subscriptionPlan->duration = $this->faker->numberBetween(7,61);
            $subscriptionPlan->max_books = $this->faker->numberBetween(9,19);
            $subscriptionPlan->max_audios = $this->faker->numberBetween(9,19);
            $subscriptionPlan->store_id = 1;
            $subscriptionPlan->total_publisher_share = $this->faker->randomFloat(2,10,50);
            $subscriptionPlan->status = $this->faker->numberBetween(0,2);
            $subscriptionPlan->is_show = $this->faker->boolean();
            $ret = $subscriptionPlan->save();
            $this->assertTrue($ret);
        }
    }

    public function testDbUpdate()
    {
        $plans = SubscriptionPlans::all();
        foreach ($plans as $singlePlan){
            $singlePlan->title = 'updated: ' . $this->faker->word();
            $ret = $singlePlan->save();
            $this->assertTrue($ret);
        }
    }

//    public function testDbDelete(){
//        $plans = SubscriptionPlans::all();
//        foreach ($plans as $singlePlan){
//            $ret = $singlePlan->delete();
//            $this->assertTrue($ret);
//        }
//    }
}
