<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use Illuminate\Console\Command;

class SendToElasticTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:sendto:rabbit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->info('📙  Fidibo:SendTo:Elastic badge producer started.');
        $this->comment('Attempting to Produce badge to rabbit...');

        $sendToQueue[] = ['_id' => 100413,'modifications' => ['subscription' => [12]]];
        $res = Helper::send_to_elastic($sendToQueue);
        dd($res);
        return 0;
    }
}
