<?php

namespace Devkeita\Laraliff\Providers;

use Devkeita\Laraliff\JWTGuard;
use Devkeita\Laraliff\Services\LiffVerificationService;
use Illuminate\Support\ServiceProvider;

class LaraliffServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
        $this->extendAuthGuard();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('devkeita.liff_verification_service', function () {
            return new LiffVerificationService();
        });
    }

    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('laraliff', function ($app, $name, array $config) {
            $guard = new JWTGuard(
                $app['tymon.jwt'],
                new LiffUserProvider(
                    $app['hash'],
                    $app['config']['auth.providers.'.$config['provider'].'.model']
                ),
                $app['request'],
                $app['devkeita.liff_verification_service']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    private function publishConfig()
    {
        $path = realpath(__DIR__.'/../../config/config.php');

        $this->publishes([$path => config_path('laraliff.php')], 'config');
        $this->mergeConfigFrom($path, 'laraliff');
    }
}
