<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionUsers;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionUserHistoriesTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionUserHistories $model;
//    protected Model $subscriptionUserHist;
//    protected SubscriptionUsers $user;
//    protected SubscriptionPlanEntities $plan;
//    protected string $modelName = Tables::SUBSCRIPTION_USER_HISTORIES;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_USER_HISTORIES['endpoint'];

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
//        $this->faker = Factory::create();
//        $this->model = new SubscriptionUserHistories();
//        $this->user = SubscriptionUsers::factory()->createOne();
//        $this->plan = SubscriptionPlanEntities::factory()->createOne();
//        $this->subscriptionUserHist = SubscriptionUserHistories::factory()->createOne();
    }

    public function testNoContent(): void
    {
        self::assertEquals([], []);
    }

//    /**
//     * @throws Exception
//     */
//    public function testIndex(): void
//    {
//        SubscriptionUserHistories::factory()->state(['subscription_user_id' => $this->user->id, 'subscription_plan_entity_id' => $this->plan->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('subscription_user_id', $resource);
//            self::assertArrayHasKey('subscription_plan_entity_id', $resource);
//            self::assertArrayHasKey('entity_id', $resource);
//            self::assertArrayHasKey('start_date', $resource);
//            self::assertArrayHasKey('end_date', $resource);
//            self::assertArrayHasKey('is_logged', $resource);
//            self::assertArrayHasKey('subscription_entity_details', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionUserHistories::factory()->state(['subscription_user_id' =>$this->user->id, 'start_date' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['subscription_plan_entity_id' => 1,]), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionUserHistories::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['subscription_user_id' => $this->user->id, 'subscription_plan_entity_id' =>
//            $this->plan->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
