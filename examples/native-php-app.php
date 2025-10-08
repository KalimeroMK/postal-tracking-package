<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use KalimeroMK\PostalTracking\Exceptions\PostalTrackingException;

class PostalTrackingApp
{
    private PostalTrackingService $service;

    public function __construct()
    {
        $this->service = new PostalTrackingService(
            timeout: 30,
            retryAttempts: 3,
            transformData: true
        );
    }

    /**
     * Track a shipment and return formatted result
     */
    public function trackShipment(string $trackingCode): array
    {
        try {
            $result = $this->service->trackShipment($trackingCode);
            return $this->formatResponse($result);
        } catch (PostalTrackingException $e) {
            return $this->formatErrorResponse($e, $trackingCode);
        }
    }

    /**
     * Format successful response
     */
    private function formatResponse(array $result): array
    {
        return [
            'status' => 'success',
            'tracking_code' => $result['tracking_code'],
            'events' => $result['data'],
            'total_events' => $result['metadata']['total_events'],
            'last_update' => $result['metadata']['last_update']
        ];
    }

    /**
     * Format error response
     */
    private function formatErrorResponse(PostalTrackingException $e, string $trackingCode): array
    {
        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'tracking_code' => $trackingCode,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Display tracking information in HTML format
     */
    public function displayTrackingHtml(string $trackingCode): string
    {
        $result = $this->trackShipment($trackingCode);
        
        if ($result['status'] === 'success') {
            $html = "<h2>Tracking Information for: {$result['tracking_code']}</h2>";
            $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $html .= "<tr><th>Од</th><th>До</th><th>Датум</th><th>Забелешка</th></tr>";
            
            foreach ($result['events'] as $event) {
                $html .= "<tr>";
                $html .= "<td>{$event['Од']}</td>";
                $html .= "<td>{$event['До']}</td>";
                $html .= "<td>{$event['Датум']}</td>";
                $html .= "<td>{$event['Забелешка']}</td>";
                $html .= "</tr>";
            }
            
            $html .= "</table>";
            $html .= "<p><strong>Total Events:</strong> {$result['total_events']}</p>";
            $html .= "<p><strong>Last Update:</strong> {$result['last_update']}</p>";
        } else {
            $html = "<h2>Error</h2>";
            $html .= "<p style='color: red;'>{$result['message']}</p>";
            $html .= "<p>Tracking Code: {$result['tracking_code']}</p>";
        }
        
        return $html;
    }
}

// Usage examples:

// Example 1: Command line usage
if (php_sapi_name() === 'cli') {
    $app = new PostalTrackingApp();
    
    if (isset($argv[1])) {
        $trackingCode = $argv[1];
        $result = $app->trackShipment($trackingCode);
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Usage: php app.php <tracking_code>\n";
        echo "Example: php app.php RA123456789MK\n";
    }
    exit;
}

// Example 2: Web usage
if (isset($_GET['tracking_code'])) {
    $app = new PostalTrackingApp();
    $trackingCode = $_GET['tracking_code'];
    
    // Return JSON response
    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($app->trackShipment($trackingCode));
    } else {
        // Return HTML response
        echo $app->displayTrackingHtml($trackingCode);
    }
} else {
    // Show form
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Postal Tracking</title>
        <meta charset='UTF-8'>
    </head>
    <body>
        <h1>Postal Tracking System</h1>
        <form method='GET'>
            <label for='tracking_code'>Tracking Code:</label>
            <input type='text' id='tracking_code' name='tracking_code' placeholder='RA123456789MK' required>
            <br><br>
            <label for='format'>Format:</label>
            <select name='format'>
                <option value='html'>HTML</option>
                <option value='json'>JSON</option>
            </select>
            <br><br>
            <button type='submit'>Track</button>
        </form>
    </body>
    </html>";
}
