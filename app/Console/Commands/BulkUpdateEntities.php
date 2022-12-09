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

class BulkUpdateEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:bulk-modification:update-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to update too many entities';

    /**
     * @var int[]
     */
    protected $updateEntities = [
        83006
        ,82653
        ,83031
        ,83044
        ,82544
        ,82556
        ,82563
        ,82553
        ,82880
        ,82993
        ,83008
        ,82684
        ,82654
        ,83041
        ,82675
        ,83048
        ,82547
        ,83033
        ,82579
        ,82859
        ,83024
        ,82561
        ,82850
        ,83049
        ,82650
        ,82676
        ,82876
        ,82848
        ,83027
        ,82874
        ,82550
        ,82661
        ,83013
        ,83018
        ,82845
        ,83005
        ,82838
        ,82853
        ,82566
        ,83020
        ,82660
        ,82651
        ,83042
        ,83035
        ,83019
        ,82990
        ,82681
        ,82562
        ,82992
        ,82577
        ,82991
        ,82881
        ,82564
        ,82551
        ,83029
        ,82542
        ,83014
        ,82659
        ,82554
        ,82567
        ,82569
        ,82677
        ,82856
        ,82674
        ,82989
        ,82568
        ,82569
        ,82677
        ,82856
        ,82674
        ,82989
        ,82568
        ,71400
        ,70485
        ,70489
        ,70486
        ,79498
        ,70488
        ,70493
        ,70491
        ,71402
        ,71404
        ,70487
        ,71408
        ,79981
        ,80311
        ,70490
        ,79964
        ,79982
        ,71403
        ,80369
        ,80350
        ,70492
        ,79940
        ,79975
        ,80392
        ,79963
        ,79925
        ,79960
        ,80372
        ,80363
        ,79927
        ,80006
        ,79945
        ,80395
        ,80086
        ,80355
        ,80073
        ,80070
        ,79941
        ,79493
        ,80357
        ,80360
        ,80341
        ,79949
        ,79936
        ,79961
        ,79952
        ,79943
        ,80366
        ,79962
        ,79934
        ,79924
        ,80330
        ,80084
        ,80321
        ,80318
        ,79946
        ,79972
        ,79935
        ,79937
        ,79980
        ,79979
        ,79933
        ,80373
        ,80075
        ,80008
        ,79983
        ,79926
        ,79968
        ,80394
        ,79951
        ,80324
        ,80344
        ,79499
        ,80072
        ,79928
        ,80316
        ,79494
        ,79942
        ,80085
        ,80396
        ,80007
        ,80398
        ,80005
        ,80069
        ,80004
        ,80071
        ,80393
        ,80393
        ,79242
        ,67976
        ,70343
        ,82839
        ,70345
        ,70893
        ,81757
        ,69949
        ,82082
        ,70601
        ,71461
        ,83500
        ,71252
        ,79857
        ,80576
        ,80571
        ,68129
        ,82836
        ,67221
        ,67504
        ,68410
        ,80702
        ,81883
        ,80569
        ,81073
        ,68306
        ,79374
        ,82844
        ,82424
        ,83970
        ,78175
        ,79853
        ,81756
        ,70771
        ,81456
        ,70604
        ,80700
        ,67395
        ,70344
        ,82423
        ,81454
        ,67245
        ,82842
        ,81455
        ,69533
        ,83971
        ,69778
        ,79230
        ,84361
        ,67222
        ,67358
        ,68632
        ,81452
        ,81884
        ,69143
        ,67665
        ,68008
        ,67801
        ,67802
        ,70054
        ,81450
        ,81759
        ,77994
        ,83104
        ,83503
        ,80574
        ,81449
        ,81074
        ,80573
        ,79237
        ,77993
        ,81548
        ,80555
        ,69145
        ,83502
        ,83101
        ,69537
        ,79239
        ,71462
        ,70606
        ,69775
        ,83501
        ,67223
        ,69773
        ,82425
        ,68449
        ,68960
        ,80703
        ,68409
        ,71251
        ,70128
        ,81077
        ,81076
        ,67201
        ,69536
        ,67202
        ,79852
        ,99774
        ,81549
        ,99736
        ,79228
        ,67198
        ,83497
        ,70598
        ,67199
        ,81760
        ,68961
        ,82840
        ,79229
        ,67357
        ,83973
        ,83096
        ,71249
        ,79854
        ,78662
        ,82841
        ,82081
        ,67359
        ,83972
        ,80575
        ,70603
        ,81758
        ,71253
        ,68571
        ,70894
        ,78660
        ,83103
        ,68176
        ,78661
        ,83499
        ,67224
        ,67200
        ,69948
        ,69538
        ,78176
        ,67705
        ,83974
        ,69539
        ,78664
        ,79235
        ,79240
        ,81451
        ,79856
        ,83498
        ,69142
        ,68312
        ,82428
        ,82426
        ,67556
        ,81453
        ,69144
        ,67205
        ,82083
        ,68529
        ,69785
        ,68959
        ,68506
        ,81075
        ,71463
        ,82427
        ,82843
        ,83969
        ,78663
        ,78658
        ,69777
        ,107159
        ,107721
        ,107067
        ,106730
        ,99325
        ,100635
        ,104139
        ,99699
        ,99698
        ,104138
        ,107329
        ,99496
        ,99390
        ,107320
        ,99697
        ,100470
        ,100472
        ,99395
        ,106556
        ,104143
        ,107193
        ,106568
        ,99315
        ,107202
        ,106932
        ,99552
        ,99431
        ,99755
        ,99695
        ,100471
        ,99357
        ,106537
        ,107189
        ,99438
        ,107158
        ,107182
        ,99495
        ,108252
        ,107256
        ,99388
        ,99370
        ,99366
        ,107713
        ,107064
        ,99553
        ,99433
        ,99612
        ,108251
        ,100634
        ,99556
        ,99389
        ,99670
        ,100632
        ,99410
        ,99756
        ,100631
        ,100787
        ,100636
        ,108250
        ,107181
        ,99393
        ,99613
        ,107062
        ,99429
        ,107324
        ,107185
        ,107263
        ,107254
        ,100909
        ,99323
        ,103823
        ,104141
        ,107719
        ,107356
        ,99371
        ,99316
        ,99498
        ,107221
        ,99616
        ,99668
        ,103818
        ,99326
        ,103820
        ,99757
        ,103346
        ,99580
        ,103350
        ,99615
        ,107328
        ,107326
        ,99577
        ,99317
        ,107813
        ,99367
        ,100789
        ,99386
        ,99369
        ,99499
        ,99550
        ,99364
        ,99425
        ,100911
        ,99391
        ,99322
        ,99360
        ,99423
        ,99314
        ,100910
        ,99387
        ,107815
        ,99578
        ,100788
        ,99557
        ,99428
        ,108249
        ,107812
        ,99396
        ,100473
        ,99361
        ,108247
        ,99435
        ,99500
        ,99546
        ,103347
        ,99614
        ,99320
        ,99324
        ,107811
        ,99497
        ,103348
        ,99532
        ,99579
        ,99669
        ,107066
        ,107814
        ,100907
        ,99426
        ,99758
        ,100474
        ,100910
        ,99387
        ,107815
        ,99578
        ,100788
        ,99557
        ,99428
        ,108249
        ,107812
        ,99396
        ,100473
        ,99361
        ,108247
        ,99435
        ,99500
        ,99546
        ,103347
        ,99614
        ,99320
        ,99324
        ,107811
        ,99497
        ,103348
        ,99532
        ,99579
        ,99669
        ,107066
        ,107814
        ,100907
        ,99426
        ,99758
        ,100474
        ,66178
        ,70196
        ,65998
        ,66310
        ,66152
        ,67226
        ,66292
        ,66038
        ,67206
        ,70083
        ,67194
        ,66486
        ,69815
        ,69443
        ,68647
        ,66059
        ,67243
        ,66390
        ,66340
        ,66279
        ,67231
        ,66496
        ,68800
        ,69390
        ,67438
        ,62436
        ,63832
        ,65551
        ,65796
        ,87692
        ,65742
        ,62490
        ,63361
        ,65761
        ,81161
        ,65537
        ,62495
        ,98639
        ,63350
        ,87728
        ,87718
        ,65538
        ,63343
        ,63324
        ,87719
        ,81194
        ,65543
        ,81193
        ,81227
        ,63341
        ,81195
        ,6058
        ,87694
        ,62530
        ,97650
        ,87727
        ,67426
        ,87706
        ,67544
        ,98796
        ,81224
        ,63346
        ,87725
        ,6051
        ,87729
        ,87720
        ,87721
        ,63349
        ,67419
        ,63351
        ,65843
        ,97715
        ,81159
        ,63340
        ,99276
        ,97720
        ,99272
        ,62513
        ,87705
        ,87736
        ,97934
        ,97712
        ,97712
    ];

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
        $bar = $this->output->createProgressBar(count($this->updateEntities));

        $bar->start();
        foreach ($this->updateEntities as  $singleEntity){
            try {
                $existsEntity = SubscriptionEntities::query()
                    ->where('subscription_entities.entity_id', '=', $singleEntity)
                    ->first();

                if ($existsEntity != null) {
                    $entityArray = $existsEntity->toArray();
                    $entityId = $entityArray['id'];
                } else {
                    $guzzle = new Client();
                    $response = $guzzle->post('https://papi.fidibo.com/Books/SubscriptionByIds', [
                        'json' => ['book_ids' => [$singleEntity]]
                    ]);
                    $entityResult = json_decode($response->getBody()->getContents(), true);

                    if ($entityResult['output']['result'] == false) {
                        throw new \Exception("Non of the input IDs are valid.");
                    }
                    if (is_array($entityResult['output']) & is_array($entityResult['output']['books'])) {
                        foreach ($entityResult['output']['books'] as $papiEntity) {
                            $type = "book";
                            if ($papiEntity['format'] == "AUDIO" and $papiEntity['content_type'] == "book") {
                                $type = "audio";
                            }

                        }
                        $entity = SubscriptionEntities::create([
                            'entity_type' => $type,
                            'entity_id' => $papiEntity['id'],
                            'price_factor' => 100,
                            'publisher_id' => $papiEntity['publisher_id'],
                            'publisher_name' => $papiEntity['publisher_title'],
                            'publisher_share' => $papiEntity['publisher_marketshare'],
                        ]);
                        $entityId = $entity['id'];
                    }
                }

                foreach ($plans as $planID) {
                    $now = Carbon::now()->toDateTimeLocalString();
                    $exist = SubscriptionPlanEntities::query()->where([
                        'entity_id' => $entityId,
                        'plan_id' => $planID
                    ])->exists();
                    if (!$exist) {
                        $planEntities = [
                            'entity_id' => $entityId,
                            'plan_id' => $planID,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                        SubscriptionPlanEntities::insert($planEntities);
                    }else{
                        continue;
                    }
                }

                $body = [
                    'doc' => ['subscription' => $plans]
                ];

                $restRequest = new Client();
                $_id = 'book_' . $singleEntity;
                $url = env('ELASTICSEARCH_SCHEME') . '://' . env('ELASTICSEARCH_HOST') . ':' . env('ELASTICSEARCH_PORT') . DIRECTORY_SEPARATOR . 'fidibo-content-v1.0' . DIRECTORY_SEPARATOR . '_doc' . DIRECTORY_SEPARATOR . $_id . DIRECTORY_SEPARATOR . '_update';
                $response = $restRequest->post($url, [
                    'json' => $body
                ]);

                if ($response->getStatusCode() == 200) {
                    $this->comment('[SUBSCRIPTION][BulkUpdateEntities] The indexer data of this record has been modified : ' . $_id);
                    Log::channel('gelf')->info('[SUBSCRIPTION][BulkUpdateEntities] The indexer data of this record has been modified : ' . $_id);
                } else {
                    $this->error('[SUBSCRIPTION][BulkUpdateEntities] The consumer cannot consume doc: ' . $_id);
                    Log::channel('gelf')->error('[SUBSCRIPTION][BulkUpdateEntities] The consumer cannot consume doc:  ' . $_id);
                }

                $this->comment('The book has been modified : ' . $singleEntity);
            } catch (\Exception $exception){

            }
            $bar->advance();

        }

        $bar->finish();
    }
}
