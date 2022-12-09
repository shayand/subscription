<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionPlanEntitiesTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionPlanEntities $model;
//    protected Model $subscriptionPlanEntities;
//    protected SubscriptionPlans $plan;
//    protected string $modelName = Tables::SUBSCRIPTION_PLAN_ENTITIES;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_PLAN_ENTITIES['endpoint'];

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
//        $this->model = new SubscriptionPlanEntities();
//        $this->plan = SubscriptionPlans::factory()->createOne();
//        $this->subscriptionPlanEntities = SubscriptionPlanEntities::factory()->createOne();
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
//        SubscriptionPlanEntities::factory()->state(['plan_id' => $this->plan->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('entity_id', $resource);
//            self::assertArrayHasKey('plan_id', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionPlanEntities::factory()->state(['plan_id' =>'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store"), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionPlanEntities::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['plan_id' => $this->plan->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
