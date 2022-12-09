<?php

namespace Database\Factories;

use App\Models\SubscriptionEntities;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionEntitiesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionEntities::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = Faker::create();
        return [
            'entity_type' => $faker->randomElement(['book', 'podcast', 'magazine', 'epub']),
            'entity_id' => 1,
            'price_factor' => $faker->randomFloat($nbMaxDecimals = NULL, $min = 0, $max = NULL),
            'publisher_id' => 1,
            'publisher_share' => $faker->randomFloat($nbMaxDecimals = NULL, $min = 0, $max = NULL),
        ];
    }
}
