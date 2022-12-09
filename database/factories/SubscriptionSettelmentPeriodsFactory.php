<?php

namespace Database\Factories;

use App\Models\SubscriptionSettelmentPeriods;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionSettelmentPeriodsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionSettelmentPeriods::class;

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
            'settelment_date' => $faker->date('Y-m-d'),
        ];
    }
}
