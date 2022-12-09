<?php

namespace Database\Factories;

use App\Models\SubscriptionPlanEntities;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanEntitiesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionPlanEntities::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'entity_id' => 1,
            'plan_id' => 1,
        ];
    }
}
