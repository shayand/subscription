<?php

namespace App\Console;

use App\Console\Commands\CalculatePublisherShares;
use App\Console\Commands\FdbPurchasesConsumerOnAmqp;
use App\Console\Commands\ModifyUsersDurationsToMinutes;
use App\Console\Commands\PlanExpiration;
use App\Console\Commands\ProducePurchasesOnAmqp;
use App\Console\Commands\SendEntitiesToElastic;
use App\Console\Commands\SendNullEntitiesOnAmqp;
use App\Console\Commands\UserPlanRenewal;
use App\Jobs\RefreshIndexerPlanEntitiesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CalculatePublisherShares::class,
        PlanExpiration::class,
        ProducePurchasesOnAmqp::class,
        SendEntitiesToElastic::class,
        SendNullEntitiesOnAmqp::class,
        UserPlanRenewal::class,
        FdbPurchasesConsumerOnAmqp::class,
        ModifyUsersDurationsToMinutes::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        if(env('master_cluster')) {
//            $schedule->command('subscription:shares_calculation:run')
//                ->runInBackground()
//                ->withoutOverlapping()
//                ->everyMinute()
//            ;

            $schedule->command('subscription:fdb:consumer')
                ->runInBackground()
                ->withoutOverlapping()
                ->everyMinute();

            $schedule->command('subscription:purchase:produce')
                ->name('Produce purchases of subscription payments')
                ->runInBackground()
                ->withoutOverlapping()
                ->everyMinute()
                ->sendOutputTo(storage_path('logs/produce-purchases.log'));

            $schedule->command('subscription:digiplus:consumer')
                ->name('Processes unprocessed data sent from digiplus')
                ->runInBackground()
                ->withoutOverlapping()
                ->everyMinute()
                ->sendOutputTo(storage_path('logs/subscription_integration.log'));

            $schedule->call(new RefreshIndexerPlanEntitiesJob)
                ->name('Refreshes each entity Plan IDs array field inside elastic')
                ->runInBackground()
                ->withoutOverlapping()
                ->everyTwoHours() // TODO change it to everyday
                ->sendOutputTo(storage_path('logs/send_entities_to_elastic.log'));

            $schedule->command('subscription:digiplus:consumer')
                ->name('Consume partner un-tracked users')
                ->runInBackground()
                ->withoutOverlapping()
                ->everyMinute()
                ->sendOutputTo(storage_path('logs/digiplus_consumer.log'));
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function scheduleTimezone()
    {
        return config('app.timezone');
    }
}
