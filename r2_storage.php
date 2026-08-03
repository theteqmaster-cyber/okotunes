<?php
/**
 * r2_storage.php - Cloudflare R2 S3-Compatible Storage Helper for okotunes
 * Lightweight, zero-dependency SigV4 implementation for Cloudflare R2 with object listing support.
 */

class R2Storage {
    private string $accountId;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $publicUrl;
    private string $prefix = 'okotunes/';

    public function __construct() {
        $this->loadEnvFile();

        $this->accountId = getenv('CF_R2_ACCOUNT_ID') ?: (getenv('R2_ACCOUNT_ID') ?: '');
        $this->accessKey = getenv('CF_R2_ACCESS_KEY_ID') ?: (getenv('R2_ACCESS_KEY_ID') ?: '');
        $this->secretKey = getenv('CF_R2_SECRET_ACCESS_KEY') ?: (getenv('R2_SECRET_ACCESS_KEY') ?: '');
        $this->bucket    = getenv('CF_R2_BUCKET') ?: (getenv('R2_BUCKET_NAME') ?: '');
        $this->publicUrl = rtrim(getenv('CF_R2_PUBLIC_URL') ?: (getenv('R2_PUBLIC_URL') ?: ''), '/');
    }

    private function loadEnvFile(): void {
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    if (!getenv($key)) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                    }
                }
            }
        }
    }

    public function isConfigured(): bool {
        return !empty($this->accountId) && !empty($this->accessKey) && !empty($this->secretKey) && !empty($this->bucket);
    }

    public function sanitizeKey(string $key): string {
        $key = ltrim($key, '/');
        if (!str_starts_with($key, $this->prefix)) {
            $key = $this->prefix . $key;
        }
        return $key;
    }

    public function getUrl(string $key): string {
        $key = $this->sanitizeKey($key);
        if (!empty($this->publicUrl)) {
            return $this->publicUrl . '/' . implode('/', array_map('rawurlencode', explode('/', $key)));
        }
        if ($this->isConfigured()) {
            return $this->getPresignedUrl($key, 86400);
        }
        return "stream.php?file=" . rawurlencode($key);
    }

    public function getPresignedUrl(string $key, int $expiresIn = 3600): string {
        $key = $this->sanitizeKey($key);
        $region = 'auto';
        $service = 's3';
        $host = "{$this->accountId}.r2.cloudflarestorage.com";
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $key)));
        $endpoint = "https://{$host}/{$this->bucket}/" . $encodedKey;

        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $queryParams = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => "{$this->accessKey}/{$credentialScope}",
            'X-Amz-Date'          => $amzDate,
            'X-Amz-Expires'       => $expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];

        ksort($queryParams);
        $canonicalQueryString = http_build_query($queryParams);

        $canonicalHeaders = "host:{$host}\n";
        $signedHeaders = "host";
        $payloadHash = 'UNSIGNED-PAYLOAD';

        $canonicalRequest = "GET\n/" . rawurlencode($this->bucket) . '/' . $encodedKey . "\n" .
                            $canonicalQueryString . "\n" .
                            $canonicalHeaders . "\n" .
                            $signedHeaders . "\n" .
                            $payloadHash;

        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return $endpoint . '?' . $canonicalQueryString . '&X-Amz-Signature=' . $signature;
    }

    public function uploadObject(string $key, string $filePathOrContent, string $mimeType = 'application/octet-stream', bool $isStringContent = false): bool {
        if (!$this->isConfigured()) {
            error_log("R2 Storage is not fully configured.");
            return false;
        }

        $key = $this->sanitizeKey($key);
        $region  = 'auto';
        $service = 's3';
        $host    = "{$this->accountId}.r2.cloudflarestorage.com";
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $key)));
        $path    = '/' . rawurlencode($this->bucket) . '/' . $encodedKey;
        $url     = "https://{$host}" . $path;

        $fileContent = $isStringContent ? $filePathOrContent : file_get_contents($filePathOrContent);
        if ($fileContent === false) return false;

        $payloadHash = hash('sha256', $fileContent);
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $headers = [
            'content-type' => $mimeType,
            'host'         => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'   => $amzDate
        ];

        ksort($headers);
        $canonicalHeaders = "";
        $signedHeaders = implode(';', array_keys($headers));
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= "{$k}:{$v}\n";
        }

        $canonicalRequest = "PUT\n{$path}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_POSTFIELDS     => $fileContent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Host: {$host}",
                    "Content-Type: {$mimeType}",
                    "x-amz-date: {$amzDate}",
                    "x-amz-content-sha256: {$payloadHash}",
                    "Authorization: {$authHeader}"
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($httpCode >= 200 && $httpCode < 300);
        } else {
            $opts = [
                'http' => [
                    'method'  => 'PUT',
                    'header'  => "Host: {$host}\r\n" .
                                 "Content-Type: {$mimeType}\r\n" .
                                 "x-amz-date: {$amzDate}\r\n" .
                                 "x-amz-content-sha256: {$payloadHash}\r\n" .
                                 "Authorization: {$authHeader}\r\n",
                    'content' => $fileContent,
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);
            $result = @file_get_contents($url, false, $context);
            if (isset($http_response_header) && count($http_response_header) > 0) {
                preg_match('{HTTP\/\S+\s+(\d+)}', $http_response_header[0], $m);
                $status = isset($m[1]) ? intval($m[1]) : 0;
                return ($status >= 200 && $status < 300);
            }
            return false;
        }
    }

    /**
     * Delete object from Cloudflare R2 bucket
     */
    public function deleteObject(string $key): bool {
        if (!$this->isConfigured()) return false;

        $key = $this->sanitizeKey($key);
        $region  = 'auto';
        $service = 's3';
        $host    = "{$this->accountId}.r2.cloudflarestorage.com";
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $key)));
        $path    = '/' . rawurlencode($this->bucket) . '/' . $encodedKey;
        $url     = "https://{$host}" . $path;

        $payloadHash = hash('sha256', '');
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $headers = [
            'host'                 => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate
        ];

        ksort($headers);
        $canonicalHeaders = "";
        $signedHeaders = implode(';', array_keys($headers));
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= "{$k}:{$v}\n";
        }

        $canonicalRequest = "DELETE\n{$path}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => 'DELETE',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Host: {$host}",
                    "x-amz-date: {$amzDate}",
                    "x-amz-content-sha256: {$payloadHash}",
                    "Authorization: {$authHeader}"
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($httpCode >= 200 && $httpCode < 300) || $httpCode === 404;
        } else {
            $opts = [
                'http' => [
                    'method'  => 'DELETE',
                    'header'  => "Host: {$host}\r\n" .
                                 "x-amz-date: {$amzDate}\r\n" .
                                 "x-amz-content-sha256: {$payloadHash}\r\n" .
                                 "Authorization: {$authHeader}\r\n",
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($opts);
            @file_get_contents($url, false, $context);
            return true;
        }
    }

    /**
     * List objects in R2 bucket under a prefix
     */
    public function listObjects(string $subPrefix = ''): array {
        if (!$this->isConfigured()) return [];

        $targetPrefix = $this->sanitizeKey($subPrefix);
        $region  = 'auto';
        $service = 's3';
        $host    = "{$this->accountId}.r2.cloudflarestorage.com";
        $endpoint = "https://{$host}/{$this->bucket}?prefix=" . rawurlencode($targetPrefix);

        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $queryParams = ['prefix' => $targetPrefix];
        ksort($queryParams);
        $canonicalQueryString = http_build_query($queryParams);

        $canonicalHeaders = "host:{$host}\n";
        $signedHeaders = "host";
        $payloadHash = hash('sha256', '');

        $canonicalRequest = "GET\n/" . rawurlencode($this->bucket) . "\n" .
                            $canonicalQueryString . "\n" .
                            $canonicalHeaders . "\n" .
                            $signedHeaders . "\n" .
                            $payloadHash;

        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Host: {$host}\r\n" .
                            "x-amz-date: {$amzDate}\r\n" .
                            "x-amz-content-sha256: {$payloadHash}\r\n" .
                            "Authorization: {$authHeader}\r\n",
                'ignore_errors' => true
            ]
        ]);

        $xmlString = @file_get_contents($endpoint, false, $context);
        if (!$xmlString) return [];

        $xml = @simplexml_load_string($xmlString);
        if (!$xml || !isset($xml->Contents)) return [];

        $keys = [];
        foreach ($xml->Contents as $item) {
            $keys[] = (string)$item->Key;
        }

        return $keys;
    }
}

if (!function_exists('getR2')) {
    function getR2(): R2Storage {
        static $r2 = null;
        if ($r2 === null) {
            $r2 = new R2Storage();
        }
        return $r2;
    }
}
