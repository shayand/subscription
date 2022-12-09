<?php

namespace Database\Factories;

use App\Models\SubscriptionShares;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionSharesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionShares::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = Faker::create();
        return [
            'subscription_user_id' => 1,
            'subscription_entity_id' => 1,
            'total_calculated_amount' => $faker->randomNumber($nbDigits = 9, $strict = false),
            'publisher_share_amount' => $faker->randomNumber($nbDigits = 9, $strict = false),
            'total_duration' => $faker->numberBetween($min = 0, $max = 360),
        ];
    }
}
