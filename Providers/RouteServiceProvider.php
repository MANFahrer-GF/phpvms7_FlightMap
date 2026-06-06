<?php

namespace Modules\FlightMap\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'Modules\\FlightMap\\Http\\Controllers';

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->namespace($this->namespace.'\\Frontend')
            ->group(__DIR__.'/../Routes/web.php');
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->namespace($this->namespace.'\\Api')
            ->prefix('flightmap')
            ->as('modules.flightmap.')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
