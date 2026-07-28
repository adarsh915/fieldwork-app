<?php

namespace App\Providers;

use App\Events\PartyAssigned;
use App\Events\PartyUpdated;
use App\Listeners\LogPartyAssigned;
use App\Listeners\LogPartyUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PartyAssigned::class => [
            LogPartyAssigned::class,
        ],
        PartyUpdated::class => [
            LogPartyUpdated::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
