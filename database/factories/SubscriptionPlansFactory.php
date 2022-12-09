<?php

namespace Database\Factories;

use App\Models\SubscriptionPlans;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlansFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionPlans::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = Faker::create();
        return [
            'title' => $faker->randomElement(['plan-1', 'plan-2', 'plan-3']),
            'start_date' => $faker->date('Y-m-d'),
            'start_date' => $faker->date('Y-m-d'),
            'price' => $faker->randomNumber($nbDigits = 5, $strict = false),
            'duration' => $faker->numberBetween($min = 10, $max = 30),
            'max_books' => $faker->numberBetween($min = 10, $max = 50),
            'max_audios' => $faker->numberBetween($min = 10, $max = 50),
            'store_id' => 1,
            'total_publisher_share' => $faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 100),
            'status' => $faker->randomElement([1, 2]),
            'is_show' => $faker->randomElement([1, 0]),
            'max_devices' => $faker->numberBetween($min = 1, $max = 8),
            'max_offline_entities' => 1,
        ];
    }
}
