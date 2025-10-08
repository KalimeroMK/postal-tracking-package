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

## Installation & Setup

### 📦 Installation

```bash
composer require kalimeromk/postal-tracking
```

### 🚀 Framework Setup

<details>
<summary><strong>Laravel Setup</strong></summary>

The package auto-registers its service provider. Use the facade:

```php
use KalimeroMK\PostalTracking\Facades\PostalTracking;

// Track a shipment
$result = PostalTracking::trackShipment('CQ117742716DE');

// With options
$result = PostalTracking::trackShipment('CQ117742716DE', [
    'timeout' => 30,
    'retry_attempts' => 3,
    'transform' => true
]);
```

**Publish config (optional):**

```bash
php artisan vendor:publish --provider="KalimeroMK\PostalTracking\Laravel\PostalTrackingServiceProvider"
```

**Controller example:**

```php
use KalimeroMK\PostalTracking\Facades\PostalTracking;

class TrackingController extends Controller
{
    public function track(Request $request)
    {
        try {
            $trackingCode = $request->get('tracking_code');
            $result = PostalTracking::trackShipment($trackingCode);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
```

</details>

<details>
<summary><strong>Yii Setup</strong></summary>

Use the service directly in your controllers:

```php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

// In your controller
$service = new PostalTrackingService();
$trackingData = $service->trackShipment('CQ117742716DE');

// Return as JSON
Yii::$app->response->format = Response::FORMAT_JSON;
return $trackingData;
```

**Controller example:**

```php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

class TrackingController extends Controller
{
    public function actionTrack()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $trackingCode = Yii::$app->request->get('tracking_code');
            $service = new PostalTrackingService();
            $result = $service->trackShipment($trackingCode);

            return $result;
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
```

</details>

<details>
<summary><strong>Native PHP Setup</strong></summary>

No framework required! Use directly:

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

**Simple Tracker Class:**

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

**Web Application:**

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
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .form-group { margin: 20px 0; }
            input[type="text"] { padding: 10px; width: 300px; }
            button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
            button:hover { background: #005a87; }
            .result { margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>Track Your Package</h1>
        <form method="GET">
            <div class="form-group">
                <label for="tracking_code">Tracking Code:</label><br>
                <input type="text" id="tracking_code" name="tracking_code"
                       placeholder="CQ117742716DE" required>
            </div>
            <button type="submit">Track Package</button>
        </form>

        <div class="result">
            <h3>Example Tracking Codes:</h3>
            <ul>
                <li><strong>CQ117742716DE</strong> - Package from Germany</li>
                <li><strong>RA123456789MK</strong> - Macedonian postal code format</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
?>
```

**Command Line Usage:**

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
        echo "✅ Tracking successful!\n";
        echo "📦 Tracking Code: {$result['tracking_code']}\n";
        echo "📊 Total events: " . count($result['data']) . "\n";
        echo "🕒 Last update: {$result['metadata']['last_update']}\n\n";

        echo "📋 Tracking Events:\n";
        foreach ($result['data'] as $index => $event) {
            echo ($index + 1) . ". {$event['Забелешка']}\n";
            echo "   📍 From: {$event['Од']}\n";
            echo "   📍 To: {$event['До']}\n";
            echo "   📅 Date: {$event['Датум']}\n\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

</details>

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
