<?php

namespace App\Jobs;

use App\Console\Commands\CalculatePublisherShares;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateTotalPublishersShareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CalculatePublisherShares
     */
    private CalculatePublisherShares $calculatePublisherShares;

    /**
     * @param CalculatePublisherShares $calculatePublisherShares
     */
    public function __invoke(CalculatePublisherShares $calculatePublisherShares)
    {
        $this->calculatePublisherShares = $calculatePublisherShares;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->calculatePublisherShares->handle();
    }
}
