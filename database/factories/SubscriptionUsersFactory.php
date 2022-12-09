<?php

namespace Database\Factories;

use App\Models\SubscriptionPlans;
use Faker\Factory as Faker;
use App\Models\SubscriptionUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionUsersFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionUsers::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $data = json_encode(array("myfirstvalue" => "myfirsttext", "mysecondvalue" => "mysecondtext"));
        $plan = SubscriptionPlans::factory()->createOne();
        $faker = Faker::create();
        return [
            'user_id' => 1,
            'plan_id' => $plan->id,
            'start_date' => $faker->date('Y-m-d'),
            'duration' => $faker->numberBetween($min = 10, $max = 180),
            'plan_details' => $data,
        ];
    }
}
