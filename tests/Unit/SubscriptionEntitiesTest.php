<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use App\Constants\Endpoints;
use App\Models\SubscriptionEntities;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionEntitiesTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionEntities $model;
//    protected Model $subscriptionEntities;
//    protected string $modelName = Tables::SUBSCRIPTION_ENTITIES;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_ENTITIES['endpoint'];

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

//    /**
//     * Setup the test environment.
//     *
//     * @return void
//     */
//    protected function setUp(): void
//    {
//        parent::setUp();
//        $this->faker = Factory::create();
//        $this->model = new SubscriptionEntities();
//        $this->subscriptionEntities = SubscriptionEntities::factory()->createOne();
//    }
//
//    public function testNoContent(): void
//    {
//        self::assertEquals([], []);
//    }
//
//    /**
//     * @throws Exception
//     */
//    public function testIndex(): void
//    {
//        SubscriptionEntities::factory()->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('entity_type', $resource);
//            self::assertArrayHasKey('entity_id', $resource);
//            self::assertArrayHasKey('price_factor', $resource);
//            self::assertArrayHasKey('publisher_id', $resource);
//            self::assertArrayHasKey('publisher_share', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionEntities::factory()->state(['publisher_id' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store"), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionEntities::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store"), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
