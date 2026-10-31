<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Support\Porta;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una porta per richiesta: i tool MCP la impostano, il registro delle scritture la legge.
        $this->app->scoped(Porta::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('it');

        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->currentAccessToken()?->id ?: $request->ip()));
    }
}
