<?php

namespace App\Console\Commands;

use App\Constants\Tables;
use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BulkDeleteEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:bulk-modification:delete-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to delete too many entities from subscription library';

    /**
     * @var int[]
     */
    protected $deleteEntities = [];

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
        $this->info(' Start to delete entities from plans');
        foreach ($this->deleteEntities as $singleEntities){

            $entityId = SubscriptionEntities::query()
                ->where('subscription_entities.entity_id','=',$singleEntities)
                ->first()
                ->toArray()
            ;

            $planEntitiesId = SubscriptionPlanEntities::query()
                ->where('entity_id','=',$entityId['id'])
                ->delete()
            ;

            $body = [
                'doc' => ['subscription' => []]
            ];
            $restRequest = new GuzzleClient();
            $_id = 'book_' . $singleEntities;
            $url = env('ELASTICSEARCH_SCHEME') .'://'. env('ELASTICSEARCH_HOST') . ':' . env('ELASTICSEARCH_PORT') . DIRECTORY_SEPARATOR . 'fidibo-content-v1.0' . DIRECTORY_SEPARATOR . '_doc' . DIRECTORY_SEPARATOR . $_id . DIRECTORY_SEPARATOR . '_update';
            $response = $restRequest->post($url, [
                'json' => $body
            ]);

            if ($response->getStatusCode() == 200) {
                $this->comment('[SUBSCRIPTION][BulkDeleteEntities] The indexer data of this record has been modified : ' . $_id);
                Log::channel('gelf')->info('[SUBSCRIPTION][BulkDeleteEntities] The indexer data of this record has been modified : ' . $_id);
            } else {
                $this->comment('[SUBSCRIPTION][BulkDeleteEntities] The indexer data of this record has been modified : ' . $_id);
                Log::channel('gelf')->error('[SUBSCRIPTION][BulkDeleteEntities] The consumer cannot consume doc:  ' . $_id);
            }

            $this->comment('The book has been deleted : ' . $singleEntities);
        }
    }
}
