<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $compiledViewsPath = env('VIEW_COMPILED_PATH');

        if (! is_string($compiledViewsPath) || $compiledViewsPath === '') {
            $compiledViewsPath = base_path('tmp/compiled-views');

            if ($this->app->environment('testing')) {
                $compiledViewsPath .= DIRECTORY_SEPARATOR.'testing-'.(getmypid() ?: 'default');
            }
        }

        if (! is_dir($compiledViewsPath)) {
            @mkdir($compiledViewsPath, 0775, true);
        }

        config(['view.compiled' => $compiledViewsPath]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
