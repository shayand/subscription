<?php

namespace Database\Factories;

use Faker\Factory as Faker;
use App\Models\SubscriptionJobs;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionJobsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionJobs::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = Faker::create();
        return array(
            'uuid' => $faker->uuid,
            'settlements' => array(
                "1" => array(
                    "id" => "1",
                    "subscription_user_id" => "1",
                    "settlement_date" => "2021-01-01"
                ),
                "2" => array(
                    "id" => "2",
                    "subscription_user_id" => "2",
                    "settlement_date" => "2021-01-01"
                ),
                "3" => array(
                    "id" => "3",
                    "subscription_user_id" => "3",
                    "settlement_date" => "2021-01-01"
                ),
            ),
            'subscription_users' => array(
                "1" => array(
                    "id" => "1",
                    "user_id" => "1",
                    "plan_id" => "1",
                    "plan_details" => "plan_details from plan table or plan_details field"
                ),
                "2" => array(
                    "id" => "2",
                    "user_id" => "2",
                    "plan_id" => "2",
                    "plan_details" => "plan_details from plan table or plan_details field"
                ),
                "3" => array(
                    "id" => "3",
                    "user_id" => "3",
                    "plan_id" => "3",
                    "plan_details" => "plan_details from plan table or plan_details field"
                ),
            ),
            'subscription_users_entities' => array(
                "1" => array(
                    "id" => "1",
                    "user_id" => "1",
                    "plan_id" => "1",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14"
                        )
                    )
                ),
                "2" => array(
                    "id" => "2",
                    "user_id" => "2",
                    "plan_id" => "2",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14"
                        )
                    )
                ),
                "3" => array(
                    "id" => "3",
                    "user_id" => "3",
                    "plan_id" => "3",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14"
                        )
                    )
                ),
            ),
            'subscription_users_entities_shares' => array(
                "1" => array(
                    "id" => "1",
                    "user_id" => "1",
                    "plan_id" => "1",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        )
                    )
                ),
                "2" => array(
                    "id" => "2",
                    "user_id" => "2",
                    "plan_id" => "2",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        )
                    )
                ),
                "3" => array(
                    "id" => "3",
                    "user_id" => "3",
                    "plan_id" => "3",
                    "plan_details" => "plan_details from plan table or plan_details field",
                    "entities" => array(
                        "1" => array(
                            "entity" => "{entity_1}",
                            "on_the_table_dates_number" => "10",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        ),
                        "2" => array(
                            "entity" => "{entity_2}",
                            "on_the_table_dates_number" => "14",
                            "publisher_share" => "x",
                            "fidibo_share" => "y"
                        )
                    )
                ),
            ),
            'total_publisher_share_amount' => $faker->randomNumber($nbDigits = 9, $strict = false),
            'total_fidibo_share_amount' => $faker->randomNumber($nbDigits = 9, $strict = false),
        );
    }
}
