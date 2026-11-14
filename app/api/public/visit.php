<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/bootstrap.php';
require __DIR__ . '/../../includes/security.php';
send_security_headers();
public_api_cors();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') json_out(['error'=>'method_not_allowed'], 405);

$input = json_decode((string)file_get_contents('php://input'), true);
$path = is_array($input) && isset($input['path']) ? trim((string)$input['path']) : '/';
if ($path !== '' && $path[0] === '#') $path = '/' . ltrim(substr($path, 1), '/');
if ($path === '' || $path[0] !== '/' || strlen($path) > 120 || preg_match('/[\x00-\x20\x7f]/', $path)) $path = '/';
$path = substr(parse_url($path, PHP_URL_PATH) ?: '/', 0, 120);
if ($path === '' || $path[0] !== '/') $path = '/';

$ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
if (preg_match('/(?:bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headlesschrome)/i', $ua)) {
    http_response_code(204);
    header('Content-Type: application/json; charset=utf-8');
    exit;
}

$remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? 'cli');
try { $visitHash = ip_hash(); } catch (Throwable $e) { $visitHash = hash('sha256', $remoteAddr . '|personalhub-visit'); }

try {
    q('CREATE TABLE IF NOT EXISTS site_visits (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(120) NOT NULL, ip_hash CHAR(64) NOT NULL, user_agent VARCHAR(300) NOT NULL DEFAULT \'\', visited_at DATETIME NOT NULL, KEY idx_visit_time (visited_at), KEY idx_visit_path (path, visited_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $recent = q_one('SELECT id FROM site_visits WHERE path=? AND ip_hash=? AND visited_at>=DATE_SUB(UTC_TIMESTAMP(), INTERVAL 60 SECOND) ORDER BY id DESC LIMIT 1', [$path, $visitHash]);
    if ($recent) json_out(['ok'=>true,'duplicate'=>true], 200);
    q('INSERT INTO site_visits (path,ip_hash,user_agent,visited_at) VALUES (?,?,?,UTC_TIMESTAMP())', [$path, $visitHash, $ua]);
    json_out(['ok'=>true]);
} catch (Throwable $e) {
    error_log('[portfolio] visit failed: ' . $e->getMessage());
    json_out(['ok'=>false,'duplicate'=>false], 200);
}
