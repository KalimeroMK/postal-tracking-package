<?php

declare(strict_types=1);

namespace KalimeroMK\PostalTracking\Laravel;

use Illuminate\Support\ServiceProvider;
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

final class PostalTrackingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/postal-tracking.php', 'postal-tracking');

        $this->app->singleton(PostalTrackingService::class, static function ($app): PostalTrackingService {
            /** @var array{timeout?: int, retry_attempts?: int, transform_data?: bool} $config */
            $config = $app['config']->get('postal-tracking', []);

            return new PostalTrackingService(
                (int) ($config['timeout'] ?? 30),
                (int) ($config['retry_attempts'] ?? 3),
                (bool) ($config['transform_data'] ?? true),
            );
        });

        $this->app->alias(PostalTrackingService::class, 'postal-tracking');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/postal-tracking.php' => $this->app->configPath('postal-tracking.php'),
            ], 'postal-tracking-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [PostalTrackingService::class, 'postal-tracking'];
    }
}
