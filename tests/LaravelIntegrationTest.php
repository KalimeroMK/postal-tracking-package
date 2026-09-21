<?php

declare(strict_types=1);

namespace KalimeroMK\PostalTracking\Tests;

use KalimeroMK\PostalTracking\Laravel\Facades\PostalTracking;
use KalimeroMK\PostalTracking\Laravel\PostalTrackingServiceProvider;
use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use Orchestra\Testbench\TestCase;

/**
 * composer.json declares a provider and a facade for auto-discovery. Laravel
 * instantiates whatever is declared, so anything missing is a fatal error on boot.
 */
final class LaravelIntegrationTest extends TestCase
{
    public function testEverythingDeclaredForAutoDiscoveryExists(): void
    {
        $manifest = json_decode((string) file_get_contents(__DIR__ . '/../composer.json'), true);
        $laravel = $manifest['extra']['laravel'] ?? [];

        foreach ($laravel['providers'] ?? [] as $provider) {
            $this->assertTrue(class_exists($provider), $provider . ' is declared but does not exist');
        }

        foreach ($laravel['aliases'] ?? [] as $alias => $class) {
            $this->assertTrue(class_exists($class), $class . ' is aliased as ' . $alias . ' but does not exist');
        }
    }

    public function testTheServiceResolvesFromTheContainer(): void
    {
        $this->assertInstanceOf(PostalTrackingService::class, $this->app->make(PostalTrackingService::class));
        $this->assertInstanceOf(PostalTrackingService::class, $this->app->make('postal-tracking'));
    }

    public function testTheServiceIsConfiguredFromConfig(): void
    {
        config(['postal-tracking.timeout' => 7, 'postal-tracking.retry_attempts' => 2]);
        $this->app->forgetInstance(PostalTrackingService::class);

        $service = $this->app->make(PostalTrackingService::class);

        $this->assertSame(7, $service->getTimeout());
        $this->assertSame(2, $service->getRetryAttempts());
    }

    public function testTheFacadeResolvesToTheService(): void
    {
        $this->assertSame(30, PostalTracking::getTimeout());
    }

    protected function getPackageProviders($app): array
    {
        return [PostalTrackingServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['PostalTracking' => PostalTracking::class];
    }
}
