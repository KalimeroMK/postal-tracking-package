<?php

declare(strict_types=1);

namespace KalimeroMK\PostalTracking\Services;

use KalimeroMK\PostalTracking\Exceptions\PostalTrackingException;
use KalimeroMK\PostalTracking\Exceptions\InvalidTrackingCodeException;
use KalimeroMK\PostalTracking\Exceptions\ApiException;

class PostalTrackingService
{
    private const API_URL = 'https://www.posta.com.mk/api/api.php/shipment';
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_RETRY_ATTEMPTS = 3;

    private int $timeout;
    private int $retryAttempts;
    private bool $transformData;

    public function __construct(
        int $timeout = self::DEFAULT_TIMEOUT,
        int $retryAttempts = self::DEFAULT_RETRY_ATTEMPTS,
        bool $transformData = true
    ) {
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
        $this->transformData = $transformData;
    }

    /**
     * Track a postal shipment
     *
     * @param string $trackingCode The tracking code to look up
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed> Tracking data
     * @throws PostalTrackingException
     */
    public function trackShipment(string $trackingCode, array $options = []): array
    {
        $this->validateTrackingCode($trackingCode);

        $timeout = $options['timeout'] ?? $this->timeout;
        $retryAttempts = $options['retry_attempts'] ?? $this->retryAttempts;
        $transformData = $options['transform'] ?? $this->transformData;

        $apiUrl = $this->buildApiUrl($trackingCode);
        $rawData = $this->fetchData($apiUrl, $timeout, $retryAttempts);

        if ($transformData) {
            $transformedData = $this->transformData($rawData);
        } else {
            $transformedData = $rawData;
        }

        return [
            'success' => true,
            'tracking_code' => $trackingCode,
            'data' => $transformedData,
            'metadata' => [
                'total_events' => count($transformedData),
                'last_update' => date('c'),
                'api_url' => $apiUrl
            ]
        ];
    }

    /**
     * Validate tracking code format
     *
     * @param string $trackingCode
     * @throws InvalidTrackingCodeException
     */
    private function validateTrackingCode(string $trackingCode): void
    {
        $trackingCode = trim($trackingCode);

        if (empty($trackingCode)) {
            throw new InvalidTrackingCodeException('Tracking code cannot be empty');
        }

        // Basic validation for Macedonian postal codes
        if (!preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}$/', strtoupper($trackingCode))) {
            throw new InvalidTrackingCodeException('Invalid tracking code format');
        }
    }

    /**
     * Build API URL with tracking code
     *
     * @param string $trackingCode
     * @return string
     */
    private function buildApiUrl(string $trackingCode): string
    {
        $encodedCode = urlencode(strtoupper(trim($trackingCode)));
        return self::API_URL . '?code=' . $encodedCode;
    }

    /**
     * Fetch data from API with retry logic
     *
     * @param string $url
     * @param int $timeout
     * @param int $retryAttempts
     * @return array<mixed>
     * @throws ApiException
     */
    private function fetchData(string $url, int $timeout, int $retryAttempts): array
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $data = $this->makeApiCall($url, $timeout);
                return $data;
            } catch (ApiException $e) {
                $lastError = $e;

                if ($attempt < $retryAttempts) {
                    sleep(1); // Wait 1 second before retry
                }
            }
        }

        throw new ApiException(
            'Failed to fetch data after ' . $retryAttempts . ' attempts: ' . ($lastError?->getMessage() ?? 'Unknown error')
        );
    }

    /**
     * Make API call using cURL
     *
     * @param string $url
     * @param int $timeout
     * @return array<mixed>
     * @throws ApiException
     */
    private function makeApiCall(string $url, int $timeout): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PostalTracking-PHP/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false) {
            throw new ApiException('cURL error: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new ApiException('HTTP error: ' . $httpCode);
        }

        $data = json_decode((string) $response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data ?? [];
    }

    /**
     * Transform raw API data
     *
     * @param array<mixed> $rawData
     * @return array<array<string, string>>
     */
    private function transformData(array $rawData): array
    {
        $transformedData = [];
        $checkWithThis = "Macedonia (the former Yugoslav Republic of)";
        $checkWithThisCustoms = "1006";

        foreach ($rawData as $item) {
            $beginning = $item['EMRI'] . " " . $item['POSTA_FILLIM'];
            $end = $item['POSTA_FUND'];
            $notice = $item['ZABELESKA'];

            // Transformations
            if (strtolower($beginning) === strtolower($checkWithThis)) {
                $beginning = "Северна Македонија";
            }

            if ($notice === "Ispora~ana") {
                $notice = "Испорачана";
            }

            if ($end === null) {
                $end = " ";
            }

            if (strtolower($end) === strtolower($checkWithThisCustoms)) {
                $end .= " - CARINA";
            }

            $transformedData[] = [
                'Од' => $beginning,
                'До' => $end,
                'Датум' => $item['DATA_PERPUNIMIT'],
                'Забелешка' => $notice
            ];
        }

        return $transformedData;
    }

    /**
     * Get timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get retry attempts
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Get transform data setting
     *
     * @return bool
     */
    public function getTransformData(): bool
    {
        return $this->transformData;
    }

    /**
     * Set timeout
     *
     * @param int $timeout
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set retry attempts
     *
     * @param int $retryAttempts
     * @return self
     */
    public function setRetryAttempts(int $retryAttempts): self
    {
        $this->retryAttempts = $retryAttempts;
        return $this;
    }

    /**
     * Set data transformation
     *
     * @param bool $transformData
     * @return self
     */
    public function setTransformData(bool $transformData): self
    {
        $this->transformData = $transformData;
        return $this;
    }
}
