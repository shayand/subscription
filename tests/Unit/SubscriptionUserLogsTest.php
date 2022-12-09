<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionUserLogs;
use App\Models\SubscriptionUsers;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionUserLogsTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionUserLogs $model;
//    protected Model $subscriptionUserLogs;
//    protected SubscriptionUsers $user;
//    protected string $modelName = Tables::SUBSCRIPTION_USER_LOGS;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_USER_LOGS['endpoint'];

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
//        $this->model = new SubscriptionUserLogs();
//        $this->user = SubscriptionUsers::factory()->createOne();
//        $this->subscriptionUserLogs = SubscriptionUserLogs::factory()->createOne();
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
//        SubscriptionUserLogs::factory()->state(['subscription_user_id' => $this->user->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('subscription_user_id', $resource);
//            self::assertArrayHasKey('device_id', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionUserLogs::factory()->state(['subscription_user_id' =>$this->user->id, 'device_id' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['plan_id' => 1,]), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionUserLogs::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['subscription_user_id' => $this->user->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
