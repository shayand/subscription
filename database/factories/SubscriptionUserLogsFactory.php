<?php

namespace Database\Factories;

use App\Models\SubscriptionUserLogs;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionUserLogsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionUserLogs::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subscription_user_id' => 1,
            'device_id' => 1,
        ];
    }
}
