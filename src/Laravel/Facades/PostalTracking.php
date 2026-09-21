<?php

declare(strict_types=1);

namespace KalimeroMK\PostalTracking\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> trackShipment(string $trackingCode, array<string, mixed> $options = [])
 * @method static int getTimeout()
 * @method static int getRetryAttempts()
 * @method static bool getTransformData()
 *
 * @see \KalimeroMK\PostalTracking\Services\PostalTrackingService
 */
final class PostalTracking extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'postal-tracking';
    }
}
