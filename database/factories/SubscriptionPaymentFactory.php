<?php

namespace Database\Factories;

use App\Models\SubscriptionPayment;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = Faker::create();
        return [
            'user_id' => 1,
            'plan_id' => 1,
            'amount' => $faker->randomNumber($nbDigits = 5, $strict = false),
            'payment_type' => $faker->randomElement(['type-1', 'type-2', 'type-3']),
            'payment_id' => 1,
        ];
    }
}
