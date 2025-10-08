<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use KalimeroMK\PostalTracking\Exceptions\PostalTrackingException;

/**
 * Simple Postal Tracking Class for Native PHP
 */
class SimplePostalTracker
{
    private PostalTrackingService $service;

    public function __construct()
    {
        $this->service = new PostalTrackingService();
    }

    /**
     * Track shipment with simple interface
     */
    public function track(string $code): array
    {
        try {
            return $this->service->trackShipment($code);
        } catch (PostalTrackingException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $code
            ];
        }
    }

    /**
     * Check if tracking code is valid format
     */
    public function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}$/', strtoupper($code)) === 1;
    }

    /**
     * Get tracking events as simple array
     */
    public function getEvents(string $code): array
    {
        $result = $this->track($code);
        
        if ($result['success']) {
            return $result['data'];
        }
        
        return [];
    }

    /**
     * Get last event
     */
    public function getLastEvent(string $code): ?array
    {
        $events = $this->getEvents($code);
        return !empty($events) ? end($events) : null;
    }

    /**
     * Check if shipment is delivered
     */
    public function isDelivered(string $code): bool
    {
        $lastEvent = $this->getLastEvent($code);
        
        if ($lastEvent && isset($lastEvent['Забелешка'])) {
            return strpos($lastEvent['Забелешка'], 'Испорачана') !== false;
        }
        
        return false;
    }
}

// Usage Examples:

// Example 1: Basic usage
$tracker = new SimplePostalTracker();

// Check if code is valid
$code = 'RA123456789MK';
if ($tracker->isValidCode($code)) {
    echo "Valid tracking code: $code\n";
    
    // Track the shipment
    $result = $tracker->track($code);
    
    if ($result['success']) {
        echo "Tracking successful!\n";
        echo "Total events: " . count($result['data']) . "\n";
        
        // Check if delivered
        if ($tracker->isDelivered($code)) {
            echo "Package is delivered!\n";
        } else {
            echo "Package is still in transit.\n";
        }
        
        // Show last event
        $lastEvent = $tracker->getLastEvent($code);
        if ($lastEvent) {
            echo "Last event: " . $lastEvent['Забелешка'] . " on " . $lastEvent['Датум'] . "\n";
        }
    } else {
        echo "Tracking failed: " . $result['error'] . "\n";
    }
} else {
    echo "Invalid tracking code format!\n";
}

// Example 2: Web usage
if (isset($_GET['code'])) {
    $tracker = new SimplePostalTracker();
    $code = $_GET['code'];
    
    header('Content-Type: application/json');
    
    if ($tracker->isValidCode($code)) {
        $result = $tracker->track($code);
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid tracking code format',
            'code' => $code
        ], JSON_PRETTY_PRINT);
    }
}
