<?php

namespace App\Providers;

use App\Channels\SistemaChannel;
use App\Channels\WebPushChannel;
use App\Models\Papeleta;
use App\Policies\PapeletaPolicy;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Papeleta::class, PapeletaPolicy::class);

        $this->app->make(ChannelManager::class)->extend('sistema', fn () => new SistemaChannel);
        $this->app->make(ChannelManager::class)->extend('webpush', fn () => new WebPushChannel);
    }
}
