<?php
declare(strict_types=1);
/** Minimal cURL wrapper. Never logs URLs/headers (they may contain secrets). Returns ['status','body','error']. */
function http_request(string $method, string $url, array $headers = [], $body = null, int $timeout = 15): array {
    if (!function_exists('curl_init')) return ['status' => 0, 'body' => '', 'error' => 'cURL extension unavailable'];
    if (strpos($url, 'https://') !== 0) return ['status' => 0, 'body' => '', 'error' => 'HTTPS required'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USERAGENT => 'PortfolioCMS/1.0']);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $out = curl_exec($ch); $err = $out === false ? curl_error($ch) : ''; $st = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    return ['status' => $st, 'body' => $out === false ? '' : (string)$out, 'error' => $err];
}

function max_upload_bytes(string $key, int $defMb): int { return (int)cfg($key, (string)$defMb) * 1048576; }
