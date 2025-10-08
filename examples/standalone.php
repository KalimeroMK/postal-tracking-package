<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use KalimeroMK\PostalTracking\Exceptions\PostalTrackingException;

// Set content type
header('Content-Type: application/json');

try {
    // Get tracking code from GET parameter
    $trackingCode = $_GET['tracking_code'] ?? '';
    
    if (empty($trackingCode)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Tracking code is required',
            'code' => 'MISSING_TRACKING_CODE'
        ]);
        exit;
    }

    // Create service instance
    $service = new PostalTrackingService(
        timeout: 30,
        retryAttempts: 3,
        transformData: true
    );

    // Track the shipment
    $result = $service->trackShipment($trackingCode);

    // Return success response
    echo json_encode($result);

} catch (PostalTrackingException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'TRACKING_ERROR',
        'tracking_code' => $trackingCode ?? null
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR'
    ]);
}
