<?php

namespace Database\Factories;

use App\Models\SubscriptionUserHistories;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionUserHistoriesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionUserHistories::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $data = json_encode(array("myfirstvalue" => "myfirsttext", "mysecondvalue" => "mysecondtext"));
        $faker = Faker::create();

        return [
            'subscription_user_id' => 1,
            'subscription_plan_entity_id' => 1,
            'entity_id' => 1,
            'start_date' => $faker->dateTime()->format('Y-m-d H:i:s'),
            'end_date' => $faker->dateTime()->format('Y-m-d H:i:s'),
            'is_logged' => $faker->randomElement([1, 0]),
            'subscription_entity_details' => $data,
        ];
    }
}
