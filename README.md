# Postal Tracking Package 📦

Advanced PHP package for tracking postal shipments from Posta na Severna Makedonija with multi-framework support, comprehensive error handling, and high-performance tracking capabilities. Features Laravel integration, Yii framework support, and **Native PHP** usage with configurable caching, retry mechanisms, and automatic data transformation.

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Framework Integration](#framework-integration)
  - [Laravel Setup](#laravel-setup)
  - [Yii Framework Setup](#yii-framework-setup)
  - [Native PHP Setup](#native-php-setup)
- [Usage Examples](#usage-examples)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Testing](#testing)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

- ✅ **Track postal shipments** from Posta na Severna Makedonija
- 🚀 **Laravel integration** with service provider and facade
- 🎯 **Yii framework support** with native integration
- 🔥 **Native PHP support** - No framework required!
- 📊 **JSON API responses** with structured data
- 🔄 **Automatic data transformation** and localization
- 🛡️ **Comprehensive error handling** and validation
- ⚡ **High-performance** with configurable timeouts and retries
- 🔧 **Configurable settings** via environment variables
- 📝 **Comprehensive documentation** and examples
- 🧪 **Full test coverage** with PHPUnit
- 📦 **PSR-4 autoloading** and Composer integration

## 📦 Installation

### Requirements

- PHP 8.1 or higher
- cURL extension
- JSON extension

### Install via Composer

```bash
composer require kalimeromk/postal-tracking
```

### Verify Installation

```bash
composer show kalimeromk/postal-tracking
```

## 🚀 Quick Start

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use KalimeroMK\PostalTracking\Services\PostalTrackingService;

$service = new PostalTrackingService();
$result = $service->trackShipment('CQ117742716DE');

if ($result['success']) {
    echo "Tracking successful!\n";
    echo "Total events: " . count($result['data']) . "\n";

    foreach ($result['data'] as $event) {
        echo "- {$event['Забелешка']} on {$event['Датум']}\n";
    }
} else {
    echo "Error: " . $result['error'] . "\n";
}
?>
```

### With Configuration

```php
<?php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

$service = new PostalTrackingService(
    timeout: 30,           // API timeout in seconds
    retryAttempts: 3,      // Number of retry attempts
    transformData: true    // Transform API response data
);

$result = $service->trackShipment('CQ117742716DE');
?>
```

## 🔧 Framework Integration

### Laravel Setup

The package auto-registers its service provider and facade. No additional configuration required!

#### Basic Usage

```php
use KalimeroMK\PostalTracking\Laravel\Facades\PostalTracking;

// Track a shipment
$result = PostalTracking::trackShipment('CQ117742716DE');

// With options
$result = PostalTracking::trackShipment('CQ117742716DE', [
    'timeout' => 30,
    'retry_attempts' => 3,
    'transform' => true
]);
```

#### Controller Example

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use KalimeroMK\PostalTracking\Laravel\Facades\PostalTracking;

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
?>
```

#### Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="KalimeroMK\PostalTracking\Laravel\PostalTrackingServiceProvider"
```

#### Service Provider Registration

The package automatically registers itself. If you need manual registration, add to `config/app.php`:

```php
'providers' => [
    // ...
    KalimeroMK\PostalTracking\Laravel\PostalTrackingServiceProvider::class,
],

'aliases' => [
    // ...
    'PostalTracking' => KalimeroMK\PostalTracking\Laravel\Facades\PostalTracking::class,
],
```

### Yii Framework Setup

Use the service directly in your controllers or components.

#### Basic Usage

```php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

// In your controller
$service = new PostalTrackingService();
$trackingData = $service->trackShipment('CQ117742716DE');

// Return as JSON
Yii::$app->response->format = Response::FORMAT_JSON;
return $trackingData;
```

#### Controller Example

```php
<?php
namespace app\controllers;

use yii\web\Controller;
use yii\web\Response;
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
?>
```

#### Component Registration

Add to your `config/web.php` or `config/console.php`:

```php
'components' => [
    'postalTracking' => [
        'class' => KalimeroMK\PostalTracking\Services\PostalTrackingService::class,
        'timeout' => 30,
        'retryAttempts' => 3,
        'transformData' => true,
    ],
],
```

### Native PHP Setup

No framework required! Perfect for standalone applications, microservices, or CLI tools.

#### Simple Tracker Class

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

#### Web Application

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

#### Command Line Usage

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

## 📚 Usage Examples

### Advanced Configuration

```php
<?php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

// Create service with custom configuration
$service = new PostalTrackingService(
    timeout: 60,           // 60 seconds timeout
    retryAttempts: 5,      // 5 retry attempts
    transformData: true    // Enable data transformation
);

// Track with additional options
$result = $service->trackShipment('CQ117742716DE', [
    'timeout' => 30,       // Override default timeout
    'retry_attempts' => 2, // Override default retries
    'transform' => false   // Disable transformation for this call
]);
?>
```

### Batch Processing

```php
<?php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

$service = new PostalTrackingService();
$trackingCodes = [
    'CQ117742716DE',
    'RA123456789MK',
    'DE987654321US'
];

$results = [];

foreach ($trackingCodes as $code) {
    try {
        $result = $service->trackShipment($code);
        $results[$code] = $result;
    } catch (\Exception $e) {
        $results[$code] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Process results
foreach ($results as $code => $result) {
    if ($result['success']) {
        echo "✅ {$code}: " . count($result['data']) . " events\n";
    } else {
        echo "❌ {$code}: {$result['error']}\n";
    }
}
?>
```

### Error Handling

```php
<?php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;
use KalimeroMK\PostalTracking\Exceptions\PostalTrackingException;
use KalimeroMK\PostalTracking\Exceptions\InvalidTrackingCodeException;
use KalimeroMK\PostalTracking\Exceptions\ApiException;

$service = new PostalTrackingService();

try {
    $result = $service->trackShipment('INVALID_CODE');
} catch (InvalidTrackingCodeException $e) {
    echo "Invalid tracking code: " . $e->getMessage() . "\n";
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage() . "\n";
} catch (PostalTrackingException $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
?>
```

## 📖 API Reference

### PostalTrackingService Class

#### Constructor

```php
public function __construct(
    int $timeout = 30,
    int $retryAttempts = 3,
    bool $transformData = true
)
```

#### Methods

##### trackShipment()

```php
public function trackShipment(string $trackingCode, array $options = []): array
```

**Parameters:**

- `$trackingCode` (string): The tracking code to look up
- `$options` (array): Additional options
  - `timeout` (int): API timeout in seconds
  - `retry_attempts` (int): Number of retry attempts
  - `transform` (bool): Enable/disable data transformation

**Returns:** `array` - Tracking data with success status

##### Configuration Methods

```php
// Getter methods
public function getTimeout(): int
public function getRetryAttempts(): int
public function getTransformData(): bool

// Setter methods (fluent interface)
public function setTimeout(int $timeout): self
public function setRetryAttempts(int $retryAttempts): self
public function setTransformData(bool $transformData): self
```

### Response Format

#### Success Response

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
    "last_update": "2025-01-15T10:30:00Z",
    "api_url": "https://www.posta.com.mk/api/api.php/shipment?code=CQ117742716DE"
  }
}
```

#### Error Response

```json
{
  "success": false,
  "error": "Invalid tracking code format",
  "code": "INVALID_CODE",
  "tracking_code": "INVALID123"
}
```

### Exception Classes

#### PostalTrackingException

Base exception class for all postal tracking related errors.

#### InvalidTrackingCodeException

Thrown when the tracking code format is invalid.

#### ApiException

Thrown when API communication fails.

## ⚙️ Configuration

### Environment Variables

Create a `.env` file in your project root:

```env
# Postal Tracking Configuration
POSTAL_TRACKING_API_URL=https://www.posta.com.mk/api/api.php/shipment
POSTAL_TRACKING_TIMEOUT=30
POSTAL_TRACKING_RETRY_ATTEMPTS=3
POSTAL_TRACKING_TRANSFORM_DATA=true
```

### Programmatic Configuration

```php
<?php
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

// Create service with custom configuration
$service = new PostalTrackingService(
    timeout: 60,           // 60 seconds timeout
    retryAttempts: 5,      // 5 retry attempts
    transformData: true    // Enable data transformation
);

// Or configure after instantiation
$service = new PostalTrackingService();
$service->setTimeout(60)
        ->setRetryAttempts(5)
        ->setTransformData(true);
?>
```

### Framework-Specific Configuration

#### Laravel

Publish the config file:

```bash
php artisan vendor:publish --provider="KalimeroMK\PostalTracking\Laravel\PostalTrackingServiceProvider"
```

Then edit `config/postal-tracking.php`:

```php
<?php
return [
    'timeout' => env('POSTAL_TRACKING_TIMEOUT', 30),
    'retry_attempts' => env('POSTAL_TRACKING_RETRY_ATTEMPTS', 3),
    'transform_data' => env('POSTAL_TRACKING_TRANSFORM_DATA', true),
];
?>
```

#### Yii

Add to your configuration:

```php
'components' => [
    'postalTracking' => [
        'class' => KalimeroMK\PostalTracking\Services\PostalTrackingService::class,
        'timeout' => 30,
        'retryAttempts' => 3,
        'transformData' => true,
    ],
],
```

## 🧪 Testing

The package includes comprehensive test coverage with PHPUnit.

### Run Tests

```bash
# Run all tests
composer test

# Run specific test suites
./vendor/bin/phpunit tests/PostalTrackingServiceTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Coverage

- **Unit tests** for all service methods
- **Integration tests** with real API calls
- **Exception handling** tests
- **Configuration** tests
- **Framework integration** tests

### Example Test

```php
<?php
use PHPUnit\Framework\TestCase;
use KalimeroMK\PostalTracking\Services\PostalTrackingService;

class PostalTrackingServiceTest extends TestCase
{
    public function testTrackShipmentWithValidCode()
    {
        $service = new PostalTrackingService();
        $result = $service->trackShipment('CQ117742716DE');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('metadata', $result);
    }

    public function testTrackShipmentWithInvalidCode()
    {
        $this->expectException(InvalidTrackingCodeException::class);

        $service = new PostalTrackingService();
        $service->trackShipment('INVALID_CODE');
    }
}
?>
```

## 🛠️ Development

### Code Style

The package follows PSR-12 coding standards.

```bash
# Fix code style
composer cs-fix

# Check code style
composer cs-check
```

### Static Analysis

```bash
# Run PHPStan
./vendor/bin/phpstan analyse src/
```

### Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Run tests: `composer test`
4. Fix code style: `composer cs-fix`

### Contributing Guidelines

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update documentation
4. Ensure all tests pass
5. Submit a pull request

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Fix code style
composer cs-fix

# Check code style
composer cs-check

# Run static analysis
./vendor/bin/phpstan analyse src/
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Made with ❤️ by KalimeroMK**

## 📞 Support

If you encounter any issues or have questions:

- 📧 Email: zbogoevski@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/KalimeroMK/postal-tracking-package/issues)
- 📖 Documentation: [GitHub Wiki](https://github.com/KalimeroMK/postal-tracking-package/wiki)

## 🔗 Related Projects

- [Email Check Package](https://github.com/KalimeroMK/email-check) - Advanced PHP email validation library
- [Macedonian Postal API](https://www.posta.com.mk) - Official postal service API
