<?php

namespace Database\Factories;

use App\Models\SubscriptionShareItems;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionShareItemsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionShareItems::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subscription_user_id' => 1,
            'subscription_plan_entity_id' => 1,
        ];
    }
}
