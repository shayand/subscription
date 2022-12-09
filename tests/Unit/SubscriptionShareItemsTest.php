<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionShareItems;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionUsers;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionShareItemsTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionShareItems $model;
//    protected Model $subscriptionShareItem;
//    protected SubscriptionUsers $user;
//    protected SubscriptionPlanEntities $plan;
//    protected string $modelName = Tables::SUBSCRIPTION_SHARE_ITEMS;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_SHARE_ITEMS['endpoint'];

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
//        $this->model = new SubscriptionShareItems();
//        $this->user = SubscriptionUsers::factory()->createOne();
//        $this->plan = SubscriptionPlanEntities::factory()->createOne();
//        $this->subscriptionShareItem = SubscriptionShareItems::factory()->createOne();
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
//        SubscriptionShareItems::factory()->state(['subscription_user_id' => $this->user->id, 'subscription_plan_entity_id' => $this->plan->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('subscription_user_id', $resource);
//            self::assertArrayHasKey('subscription_plan_entity_id', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionShareItems::factory()->state(['subscription_user_id' => $this->user->id, 'subscription_plan_entity_id' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store"), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionShareItems::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['subscription_user_id' => $this->user->id, 'subscription_plan_entity_id' =>
//            $this->plan->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
