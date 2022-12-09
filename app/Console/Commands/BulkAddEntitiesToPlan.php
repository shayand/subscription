<?php

namespace App\Console\Commands;

use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BulkAddEntitiesToPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:bulk-add-to-plan:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'assign all entities to plan';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $newPlanId = 164;
        $entities = SubscriptionEntities::all(['id','entity_id']);

        foreach ($entities as  $singleEntity){
            $existsEntity = SubscriptionPlanEntities::query()
                ->where('entity_id', '=', $singleEntity['id'])
                ->count();

            if ($existsEntity >= 0) {
                $now = Carbon::now()->toDateTimeLocalString();
                $planEntities = [
                    'entity_id' => $singleEntity['id'],
                    'plan_id' => $newPlanId,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
                SubscriptionPlanEntities::insert($planEntities);
                $this->comment('[SUBSCRIPTION][BulkAddEntitiesToPlan] The entity has been assigned to plan : ' . $singleEntity['entity_id']);
                Log::channel('gelf')->error('[SUBSCRIPTION][BulkAddEntitiesToPlan] The entity has been assigned to plan :  ' . $singleEntity['entity_id']);
            }
        }
    }
}
