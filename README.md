# Postal Tracking Package 📦

A PHP package for tracking postal shipments from Posta na Severna Makedonija with support for Laravel, Yii, and **Native PHP**.

## Features

- ✅ Track postal shipments from Posta na Severna Makedonija
- 🚀 Laravel integration with service provider and facade
- 🎯 Yii framework support
- 🔥 **Native PHP support** - No framework required!
- 📊 JSON API responses
- 🔄 Automatic data transformation
- 🛡️ Error handling and validation
- 📝 Comprehensive documentation

## Installation

```bash
composer require kalimeromk/postal-tracking
```

## Native PHP Usage (No Framework Required!)

### Installation

```bash
composer require kalimeromk/postal-tracking
```

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;

$service = new PostalTrackingService();
$result = $service->trackShipment('CQ117742716DE');

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
```

### Simple Tracker Class

```php
<?php
require_once 'vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;

class SimplePostalTracker
{
    private PostalTrackingService $service;

    public function __construct()
    {
        $this->service = new PostalTrackingService();
    }

    public function track(string $code): array
    {
        try {
            return $this->service->trackShipment($code);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $code
            ];
        }
    }

    public function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}$/', strtoupper($code)) === 1;
    }

    public function isDelivered(string $code): bool
    {
        $result = $this->track($code);

        if ($result['success'] && !empty($result['data'])) {
            $lastEvent = end($result['data']);
            return strpos($lastEvent['Забелешка'], 'Испорачана') !== false;
        }

        return false;
    }
}

// Usage
$tracker = new SimplePostalTracker();

if ($tracker->isValidCode('CQ117742716DE')) {
    $result = $tracker->track('CQ117742716DE');

    if ($result['success']) {
        echo "Tracking successful!\n";
        echo "Total events: " . count($result['data']) . "\n";
        echo "Is delivered: " . ($tracker->isDelivered('CQ117742716DE') ? 'Yes' : 'No') . "\n";

        // Show all events
        foreach ($result['data'] as $event) {
            echo "- {$event['Забелешка']} on {$event['Датум']}\n";
        }
    }
}
?>
```

### Web Application Example

```php
<?php
require_once 'vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;

// Handle tracking request
if (isset($_GET['tracking_code'])) {
    $service = new PostalTrackingService();
    $trackingCode = $_GET['tracking_code'];

    try {
        $result = $service->trackShipment($trackingCode);

        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    // Show tracking form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Postal Tracking</title>
        <meta charset="UTF-8">
    </head>
    <body>
        <h1>Track Your Package</h1>
        <form method="GET">
            <input type="text" name="tracking_code" placeholder="CQ117742716DE" required>
            <button type="submit">Track</button>
        </form>
    </body>
    </html>
    <?php
}
?>
```

### Command Line Usage

```php
<?php
require_once 'vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;

if ($argc < 2) {
    echo "Usage: php track.php <tracking_code>\n";
    echo "Example: php track.php CQ117742716DE\n";
    exit(1);
}

$trackingCode = $argv[1];
$service = new PostalTrackingService();

try {
    $result = $service->trackShipment($trackingCode);

    if ($result['success']) {
        echo "Tracking successful!\n";
        echo "Total events: " . count($result['data']) . "\n";

        foreach ($result['data'] as $event) {
            echo "- {$event['Забелешка']} on {$event['Датум']}\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

## Laravel Usage

### Service Provider (Auto-registered)

```php
use KalimeroMK\PostalTracking\Facades\PostalTracking;

$trackingData = PostalTracking::trackShipment('CQ117742716DE');
```

## Yii Usage

```php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

$service = new PostalTrackingService();
$trackingData = $service->trackShipment('CQ117742716DE');
```

## API Response Format

### Success Response

```json
{
  "success": true,
  "tracking_code": "CQ117742716DE",
  "data": [
    {
      "Од": "Северна Македонија",
      "До": "1006 - CARINA",
      "Датум": "2025-01-15",
      "Забелешка": "Испорачана"
    }
  ],
  "metadata": {
    "total_events": 1,
    "last_update": "2025-01-15T10:30:00Z"
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": "Invalid tracking code format",
  "code": "INVALID_CODE",
  "tracking_code": "INVALID123"
}
```

## Configuration

### Environment Variables

```env
POSTAL_TRACKING_API_URL=https://www.posta.com.mk/api/api.php/shipment
POSTAL_TRACKING_TIMEOUT=30
POSTAL_TRACKING_RETRY_ATTEMPTS=3
POSTAL_TRACKING_TRANSFORM_DATA=true
```

## Testing

```bash
composer test
```

## Code Style

```bash
composer cs-fix
composer cs-check
```

## License

MIT License - see LICENSE file for details.
