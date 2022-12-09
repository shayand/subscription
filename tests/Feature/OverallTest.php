<?php

namespace Tests\Feature;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OverallTest extends TestCase
{
    private Generator $faker;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = Factory::create();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testOverallFlow()
    {
        // create plans
        $createPlanReq = $this->post('/api/plans',[
            'title' => 'test ' . rand(111,999) ,
            'store_id'=> '1',
            'duration' => 30,
            'price' => 300000,
            'max_books' => 10 ,
            'max_audios' => 10,
            'total_publisher_share'=> 30
        ]);

        $createPlanRes = json_decode($createPlanReq->getContent());
        $createPlanReq->assertStatus(201);
        $createPlanReq->assertJsonStructure(['data']);

        $planId = $createPlanRes->data->id;

        // create entities
        $entityTypes = ['ebook','audio','epub'];
        $mainServiceEntities = [80061,100126,96207,99688,81977,105326,70732,1631,69249];

        $entityIds = [];
        for ($i = 0;$i < 9;$i++){

            $createEntityReq = $this->post('/api/entities',[
                'entity_type' => $entityTypes[rand(0,2)],
                'entity_id' => $mainServiceEntities[$i],
                'price_factor' => rand(0,50),
                'publisher_id' => rand(1,3),
                'publisher_share' => rand(5,15)
            ]);


            $createEntityRes = json_decode($createEntityReq->getContent());
            $entityIds[] = $createEntityRes->data->id;
            $createEntityReq->assertStatus(201);
        }

        // assign plan to entities
        $assignmentArray = [];
        foreach ($entityIds as $singleEntity){
            $createAssignReq = $this->post('/api/plan_entities/'.$planId,[
                'entity_id' => $singleEntity
            ]);

            $createAssignRes = json_decode($createAssignReq->getContent());
            $assignmentArray[] = $createAssignRes->data->id;
            $createAssignReq->assertStatus(201);
        }
        // add user to subscription plan
        $users = [12,22];

        $userIds = [];
        foreach ($users as $user) {

            $createUserReq = $this->post('/api/users/'.$planId,[
                'user_id' => $user,
                'start_date' => date('Y-m-d'),
                'device_id' => $this->faker->buildingNumber
            ]);

            $createUserRes = json_decode($createUserReq->getContent());
            $userIds[] = $createUserRes->data->id;
            $createUserReq->assertStatus(201);
        }

        dd($userIds);

    }
}
