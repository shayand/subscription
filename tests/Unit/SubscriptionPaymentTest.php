<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionUsers;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionPaymentTest extends TestCase
{
//    use RefreshDatabase;
//
//    protected Generator $faker;
//    protected SubscriptionPayment $model;
//    protected Model $subscriptionPayment;
//    protected SubscriptionUsers $user;
//    protected SubscriptionPlans $plan;
//    protected string $modelName = Tables::SUBSCRIPTION_PAYMENTS;
//    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_PAYMENTS['endpoint'];

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
//        $this->model = new SubscriptionPayment();
//        $this->user = SubscriptionUsers::factory()->createOne();
//        $this->plan = SubscriptionPlans::factory()->createOne();
//        $this->subscriptionPayment = SubscriptionPayment::factory()->createOne();
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
//        SubscriptionPayment::factory()->state(['user_id' => $this->user->id, 'plan_id' => $this->plan->id])->create();
//
//        $resources = $this->model->getResources()->toArray();
//
//        self::assertNotEmpty($resources);
//        self::assertIsArray($resources);
//        foreach ($resources as $resource) {
//            self::assertArrayHasKey('id', $resource);
//            self::assertArrayHasKey('user_id', $resource);
//            self::assertArrayHasKey('plan_id', $resource);
//            self::assertArrayHasKey('amount', $resource);
//            self::assertArrayHasKey('payment_type', $resource);
//            self::assertArrayHasKey('payment_id', $resource);
//            self::assertArrayHasKey('created_at', $resource);
//            self::assertArrayHasKey('updated_at', $resource);
//        }
//    }
//
//    public function testStoreApiWithWrongValidation(): void
//    {
//        $data = SubscriptionPayment::factory()->state(['user_id' =>$this->user->id, 'amount' => 'hello'])->makeOne()->toArray();
//
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['plan_id' => 1,]), $data, $this->header);
//        $httpRequest->assertStatus(422);
//        $httpRequest->assertJsonStructure(['data', 'errors']);
//    }
//
//    public function testStoreApi(): void
//    {
//        $data = SubscriptionPayment::factory()->makeOne()->toArray();
//        $httpRequest = $this->post(route("{$this->endpoint}.store", ['user_id' => $this->user->id, 'plan_id' => $this->plan->id]), $data, $this->header);
//        $httpRequest->assertStatus(201);
//        $httpRequest->assertJsonStructure(['data']);
//    }
}
