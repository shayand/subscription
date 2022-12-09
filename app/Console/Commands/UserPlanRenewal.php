<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UserPlanRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:user-plan:renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job for setting end date user history and renew open book on user dashboard';

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
        return 0;
    }
}
