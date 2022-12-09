<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionSettelmentPeriods;
use App\Models\SubscriptionUsers;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionSettelmentPeriodsTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionSettelmentPeriods $model;
//    protected Model $subscriptionSettlement;
//    protected SubscriptionUsers $user;
//    protected string $modelName = Tables::SUBSCRIPTION_SETTELMENT_PERIODS;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_SETTELMENT_PERIODS['endpoint'];

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
//        $this->model = new SubscriptionSettelmentPeriods();
//        $this->user = SubscriptionUsers::factory()->createOne();
//        $this->subscriptionSettlement = SubscriptionSettelmentPeriods::factory()->createOne();
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
//        SubscriptionSettelmentPeriods::factory()->state(['user_id' => $this->user->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('subscription_user_id', $resource);
//            self::assertArrayHasKey('settelment_date', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionSettelmentPeriods::factory()->state(['user_id' =>$this->user->id, 'settelment_date' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['user_id' => $this->user->id]), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionSettelmentPeriods::factory()->state(['user_id' =>$this->user->id])->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['user_id' => $this->user->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
