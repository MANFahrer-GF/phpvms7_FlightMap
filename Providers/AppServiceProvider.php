<?php

namespace Modules\FlightMap\Providers;

use App\Contracts\Modules\ServiceProvider;
use App\Services\ModuleService;

class AppServiceProvider extends ServiceProvider
{
    protected ModuleService $moduleSvc;

    public function boot(): void
    {
        $this->moduleSvc = app(ModuleService::class);
        $this->registerViews();
        $this->registerLinks();
    }

    public function registerLinks(): void
    {
        // Member-only frontend link. On the SPTheme the entry is also placed
        // manually under the SkyOps group in sidebar.blade.php; this registration
        // keeps a link for the Seven/Beta fallback themes.
        $this->moduleSvc->addFrontendLink('Flugkarte', '/flightmap', 'ph-fill ph-map-trifold', true);
    }

    public function registerViews(): void
    {
        $sourcePath = __DIR__.'/../Resources/views';

        $paths = array_map(function ($path) {
            return str_replace('default', setting('general.theme'), $path).'/modules/flightmap';
        }, config('view.paths'));

        $this->loadViewsFrom(array_merge($paths, [$sourcePath]), 'flightmap');
    }
}
