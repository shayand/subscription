<?php

namespace App\Providers;

use App\Models\SubscriptionEntities;
use App\Models\SubscriptionPlanEntities;
use App\Models\SubscriptionPlans;
use App\Models\SubscriptionUserHistories;
use App\Models\SubscriptionUsers;
use App\Observers\SubscriptionEntityObserver;
use App\Observers\SubscriptionPlanEntityObserver;
use App\Observers\SubscriptionPlanObserver;
use App\Observers\SubscriptionUserHistoriesObserver;
use App\Observers\SubscriptionUsersObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        SubscriptionUsers::observe(SubscriptionUsersObserver::class);
        SubscriptionUserHistories::observe(SubscriptionUserHistoriesObserver::class);
        SubscriptionPlans::observe(SubscriptionPlanObserver::class);
//        SubscriptionEntities::observe(SubscriptionEntityObserver::class);
        SubscriptionPlanEntities::observe(SubscriptionPlanEntityObserver::class);
    }
}
