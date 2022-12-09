<?php

namespace Tests\Unit;

use App\Constants\Endpoints;
use App\Models\SubscriptionJobs;
use Faker\Factory;
use Faker\Generator;
use App\Constants\Tables;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionJobsTest extends TestCase
{
    use RefreshDatabase;

    protected Generator $faker;
    protected SubscriptionJobs $model;
    protected Model $subscriptionJobs;
    protected string $modelName = Tables::SUBSCRIPTION_JOBS;
    protected array $header = ['Accept' => 'application/json'];
//    protected string $endpoint = Endpoints::SUBSCRIPTION_JOBS['endpoint'];

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
        $this->faker = Factory::create();
        $this->model = new SubscriptionJobs();
        $this->subscriptionJobs = SubscriptionJobs::factory()->createOne();
    }

    /**
     * @throws Exception
     */
    public function testInsert(): void
    {
        $sj = SubscriptionJobs::factory()->create();

        self::assertNotEmpty($sj);
        self::assertArrayHasKey('id', $sj);
        self::assertArrayHasKey('uuid', $sj);
        self::assertArrayHasKey('settlements', $sj);
        self::assertArrayHasKey('subscription_users', $sj);
        self::assertArrayHasKey('subscription_users_entities', $sj);
        self::assertArrayHasKey('subscription_users_entities_shares', $sj);
        self::assertArrayHasKey('total_publisher_share_amount', $sj);
        self::assertArrayHasKey('total_fidibo_share_amount', $sj);
        self::assertArrayHasKey('created_at', $sj);
        self::assertArrayHasKey('updated_at', $sj);
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $sjs = SubscriptionJobs::all();

        foreach ($sjs as $singleJob){
            $singleJob->total_publisher_share_amount = $singleJob->total_publisher_share_amount + 1;
            $ret = $singleJob->save();
            $this->assertTrue($ret);
        }
    }

    /**
     * @throws Exception
     */
    public function testDelete(): void
    {
        $sjs = SubscriptionJobs::all();

        foreach ($sjs as $singleJob) {
            $this->assertTrue($singleJob->delete());
        }
    }
}
