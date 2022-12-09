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

class BulkUpsertToElastic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:bulk-upsert:elastic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upsert all fidiplus entities to elastic search';

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
        $plans = SubscriptionPlans::all(['id'])->pluck('id');
        $entities = SubscriptionEntities::all(['id','entity_id']);
        $bar = $this->output->createProgressBar($entities->count());

        $bar->start();
        foreach ($entities as  $singleEntity){
            try {
                $existsEntity = SubscriptionPlanEntities::query()
                    ->where('entity_id', '=', $singleEntity['id'])
                    ->count();

                if ($existsEntity >= 0) {
                    $body = [
                        'doc' => ['subscription' => [$plans]]
                    ];

                    $restRequest = new Client();
                    $_id = 'book_' . $singleEntity['entity_id'];
                    $url = env('ELASTICSEARCH_SCHEME') . '://' . env('ELASTICSEARCH_HOST') . ':' . env('ELASTICSEARCH_PORT') . DIRECTORY_SEPARATOR . 'fidibo-content-v1.0' . DIRECTORY_SEPARATOR . '_doc' . DIRECTORY_SEPARATOR . $_id . DIRECTORY_SEPARATOR . '_update';
                    $response = $restRequest->post($url, [
                        'json' => $body
                    ]);

                    if ($response->getStatusCode() == 200) {
                        $this->comment('[SUBSCRIPTION][BulkUpsertToElastic] The indexer data of this record has been modified : ' . $_id);
                        Log::channel('gelf')->info('[SUBSCRIPTION][BulkUpsertToElastic] The indexer data of this record has been modified : ' . $_id);
                    } else {
                        $this->comment('[SUBSCRIPTION][BulkUpsertToElastic] The indexer data of this record has been modified : ' . $_id);
                        Log::channel('gelf')->error('[SUBSCRIPTION][BulkUpsertToElastic] The consumer cannot consume doc:  ' . $_id);
                    }
                    $this->comment('The book has been modified : ' . $singleEntity['entity_id']);
                    sleep(0.100);
                }
            } catch (\Exception $exception){

            }
            $bar->advance();

        }

        $bar->finish();
    }
}
