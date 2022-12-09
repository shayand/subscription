<?php

namespace App\Console\Commands;

use App\Models\SubscriptionUsers;
use Illuminate\Console\Command;

class ModifyUsersDurationsToMinutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:user-duration:minutes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subscription users duration days to minutes';

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
        $subscriptionUsers = SubscriptionUsers::all();
        foreach ($subscriptionUsers as $singleUser){
            try{
                // convert duration in days
                $singleUser->duration = $singleUser->duration * 60 * 24;
                $singleUser->saveOrFail();
                $this->info('Subscription users duration has been updated for ' . $singleUser->user_id . ' user id | ' . $singleUser->id . ' id');
            }catch (\Exception $exception){
                $this->comment('There is error for ' . $singleUser->user_id . ' user id | ' . $singleUser->id . ' id');
                continue;
            }
        }
    }
}
