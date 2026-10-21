<?php
/**
 * Cloudflare R2 Storage Client (Pure PHP - AWS Signature V4)
 * For syncing datasets, transcripts, and checkpoints to bucket 'notak2'
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class R2Client {
    private string $accountId;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $region = 'auto';

    public function __construct() {
        $this->accountId = CF_R2_ACCOUNT_ID;
        $this->accessKey = CF_R2_ACCESS_KEY_ID;
        $this->secretKey = CF_R2_SECRET_ACCESS_KEY;
        $this->bucket = CF_R2_BUCKET;
    }

    public function isConfigured(): bool {
        return !empty($this->accountId) && !empty($this->accessKey) && !empty($this->secretKey);
    }

    /**
     * Upload an object to R2 bucket
     */
    public function putObject(string $key, string $data, string $contentType = 'application/json'): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'R2 credentials not fully configured in .env'];
        }

        $host = "{$this->accountId}.r2.cloudflarestorage.com";
        $url = "https://{$host}/{$this->bucket}/{$key}";
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $payloadHash = hash('sha256', $data);

        // Canonical Headers
        $canonicalHeaders = "content-type:{$contentType}\n" .
                            "host:{$host}\n" .
                            "x-amz-content-sha256:{$payloadHash}\n" .
                            "x-amz-date:{$timestamp}\n";
        $signedHeaders = "content-type;host;x-amz-content-sha256;x-amz-date";

        // Canonical Request
        $canonicalRequest = "PUT\n" .
                            "/{$this->bucket}/{$key}\n\n" .
                            $canonicalHeaders . "\n" .
                            $signedHeaders . "\n" .
                            $payloadHash;

        // String to sign
        $credentialScope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n" .
                        "{$timestamp}\n" .
                        "{$credentialScope}\n" .
                        hash('sha256', $canonicalRequest);

        // Signing key calculation
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$credentialScope}, " .
                         "SignedHeaders={$signedHeaders}, Signature={$signature}";

        $headers = [
            "Content-Type: {$contentType}",
            "Host: {$host}",
            "x-amz-date: {$timestamp}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authorization}",
            "Content-Length: " . strlen($data)
        ];

        // Send HTTP PUT via stream context or curl if available
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($code >= 200 && $code < 300) {
                return ['success' => true, 'key' => $key, 'url' => CF_R2_PUBLIC_URL . '/' . $key];
            }
            return ['success' => false, 'code' => $code, 'error' => $err ?: $response];
        }

        $opts = [
            'http' => [
                'method' => 'PUT',
                'header' => implode("\r\n", $headers),
                'content' => $data,
                'timeout' => 15,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        if (str_contains($statusLine, '200') || str_contains($statusLine, '204')) {
            return ['success' => true, 'key' => $key, 'url' => CF_R2_PUBLIC_URL . '/' . $key];
        }
        return ['success' => false, 'status' => $statusLine, 'error' => $result];
    }
}
