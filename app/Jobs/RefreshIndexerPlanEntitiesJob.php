<?php

namespace App\Jobs;

use App\Console\Commands\SendEntitiesToElastic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RefreshIndexerPlanEntitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var SendEntitiesToElastic
     */
    private SendEntitiesToElastic $sendEntitiesToElastic;

    /**
     * Create a new job instance.
     *
     * @param SendEntitiesToElastic $sendEntitiesToElastic
     */
    public function __invoke(SendEntitiesToElastic $sendEntitiesToElastic)
    {
        $this->sendEntitiesToElastic = $sendEntitiesToElastic;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->sendEntitiesToElastic->handle();
    }
}
