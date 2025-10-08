<?php

declare(strict_types=1);

namespace KalimeroMK\PostalTracking\Tests;

use PHPUnit\Framework\TestCase;
use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use KalimeroMK\PostalTracking\Exceptions\InvalidTrackingCodeException;

class PostalTrackingServiceTest extends TestCase
{
    private PostalTrackingService $service;

    protected function setUp(): void
    {
        $this->service = new PostalTrackingService();
    }

    public function testValidateTrackingCodeWithEmptyCode(): void
    {
        $this->expectException(InvalidTrackingCodeException::class);
        $this->expectExceptionMessage('Tracking code cannot be empty');

        $this->service->trackShipment('');
    }

    public function testValidateTrackingCodeWithInvalidFormat(): void
    {
        $this->expectException(InvalidTrackingCodeException::class);
        $this->expectExceptionMessage('Invalid tracking code format');

        $this->service->trackShipment('INVALID123');
    }

    public function testValidateTrackingCodeWithValidFormat(): void
    {
        // This test will fail with API call, but validation should pass
        try {
            $this->service->trackShipment('RA123456789MK');
        } catch (\Exception $e) {
            // We expect an API exception, not validation exception
            $this->assertNotInstanceOf(InvalidTrackingCodeException::class, $e);
        }
    }

    public function testServiceConfiguration(): void
    {
        $service = new PostalTrackingService(60, 5, false);

        $this->assertEquals(60, $service->getTimeout());
        $this->assertEquals(5, $service->getRetryAttempts());
        $this->assertEquals(false, $service->getTransformData());
    }

    public function testSetters(): void
    {
        $this->service->setTimeout(45);
        $this->service->setRetryAttempts(2);
        $this->service->setTransformData(false);

        $this->assertEquals(45, $this->service->getTimeout());
        $this->assertEquals(2, $this->service->getRetryAttempts());
        $this->assertEquals(false, $this->service->getTransformData());
    }
}
