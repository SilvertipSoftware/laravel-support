<?php

namespace SilvertipSoftware\LaravelSupport\Providers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SilvertipSoftware\LaravelSupport\Http\Middleware\DetectDesiredResponseFormat;
use SilvertipSoftware\LaravelSupport\Http\Middleware\SealInFreshness;
use SilvertipSoftware\LaravelSupport\Http\Mixins\RequestAcceptsHelpers;
use SilvertipSoftware\LaravelSupport\Http\Mixins\RequestFreshnessHelpers;
use SilvertipSoftware\LaravelSupport\Routing\RedirectHelpers;
use SilvertipSoftware\LaravelSupport\Routing\ResourceRegistrar;
use SilvertipSoftware\LaravelSupport\Routing\UrlHelpers;
use SilvertipSoftware\LaravelSupport\View\FormatAwareViewFinder;

class LaravelSupportProvider extends ServiceProvider {

    public function boot(): void {
        RequestAcceptsHelpers::register();
        RequestFreshnessHelpers::register();
        UrlHelpers::register();
        RedirectHelpers::register();

        FormatAwareViewFinder::register($this->app);
        View::addExtension('js.php', 'blade');
        View::addExtension('turbo_stream.php', 'blade');

        $this->app->singleton('Illuminate\Routing\ResourceRegistrar', function ($app) {
            return new ResourceRegistrar($app['router']);
        });

        Route::aliasMiddleware('formats', DetectDesiredResponseFormat::class);
        Route::aliasMiddleware('freshness', SealInFreshness::class);

        View::composer('*', function ($view) {
            $view->with('currentView', $view->getName());
        });
    }
}
