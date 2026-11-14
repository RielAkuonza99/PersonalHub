<?php
declare(strict_types=1);
// Core bootstrap: config, error handling, DB, helpers. Compatible with PHP 7.4+.
define('APP_ROOT', dirname(__DIR__));
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

function env_load(): array {
    static $env = null;
    if ($env !== null) return $env;
    $env = [];
    foreach ([APP_ROOT . '/config/.env', dirname(APP_ROOT) . '/.env'] as $f) {
        if (!is_file($f) || !is_readable($f)) continue;
        foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $v = trim($v);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) $v = substr($v, 1, -1);
            $env[trim($k)] = $v;
        }
        break;
    }
    return $env;
}
function cfg(string $key, $default = '') {
    $e = env_load();
    if (isset($e[$key]) && $e[$key] !== '') return $e[$key];
    $g = getenv($key);
    return ($g !== false && $g !== '') ? $g : $default;
}
function cfg_bool(string $key, bool $default = false): bool {
    $v = strtolower((string)cfg($key, $default ? 'true' : 'false'));
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
}
date_default_timezone_set((string)cfg('APP_TIMEZONE', 'Asia/Jakarta'));

function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
}

function fail_generic(int $code = 500): void {
    http_response_code($code);
    if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
    echo $code === 503 ? 'Service unavailable.' : 'Something went wrong.';
    exit;
}
set_exception_handler(function ($e) {
    error_log('[portfolio] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    if (defined('API_JSON')) { http_response_code(500); header('Content-Type: application/json'); echo '{"error":"server_error"}'; exit; }
    fail_generic(500);
});

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = (string)cfg('DB_HOST'); $name = (string)cfg('DB_NAME');
    if ($host === '' || $name === '') throw new RuntimeException('Database is not configured');
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4', (string)cfg('DB_USER'), (string)cfg('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function q_all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function q_one(string $sql, array $p = []) { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function q_val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function str_has(string $h, string $n): bool { return $n === '' || strpos($h, $n) !== false; }
function now(): string { return gmdate('Y-m-d H:i:s'); }

// ---- crypto (AES-256-GCM, key derived from APP_KEY) ----
function app_key(): string {
    $k = (string)cfg('APP_KEY');
    if (strlen($k) < 32) throw new RuntimeException('APP_KEY missing or too short');
    return hash('sha256', $k, true);
}
function enc(string $plain): string {
    $iv = random_bytes(12); $tag = '';
    $c = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($c === false) throw new RuntimeException('encrypt failed');
    return base64_encode($iv . $tag . $c);
}
function dec(string $b64): ?string {
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) < 29) return null;
    $p = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $p === false ? null : $p;
}
function ip_hash(): string { return hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), app_key()); }

// ---- audit & rate limit ----
function audit(string $action, string $detail = ''): void {
    try { q('INSERT INTO audit_log (action, detail, ip_hash, created_at) VALUES (?,?,?,?)', [$action, mb_substr($detail, 0, 480), substr(ip_hash(), 0, 16), now()]); } catch (Throwable $e) { error_log('audit failed'); }
}
function rate_count(string $bucket, int $windowSec): int {
    return (int)q_val('SELECT COUNT(*) FROM auth_attempts WHERE bucket=? AND ip_hash=? AND created_at > ?', [$bucket, ip_hash(), gmdate('Y-m-d H:i:s', time() - $windowSec)]);
}
function rate_hit(string $bucket): void {
    q('INSERT INTO auth_attempts (bucket, ip_hash, created_at) VALUES (?,?,?)', [$bucket, ip_hash(), now()]);
    if (mt_rand(1, 50) === 1) q('DELETE FROM auth_attempts WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 172800)]);
}
function rate_clear(string $bucket): void { q('DELETE FROM auth_attempts WHERE bucket=? AND ip_hash=?', [$bucket, ip_hash()]); }
function rate_blocked(string $bucket, int $max, int $windowSec): bool { return rate_count($bucket, $windowSec) >= $max; }

// ---- validation ----
function v_str($v, int $min, int $max): ?string {
    if (!is_string($v)) return null;
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v));
    if (preg_match('//u', $v) !== 1) return null;
    $l = mb_strlen($v);
    return ($l >= $min && $l <= $max) ? $v : null;
}
function v_email($v): ?string { $v = is_string($v) ? trim($v) : ''; return (strlen($v) <= 190 && filter_var($v, FILTER_VALIDATE_EMAIL)) ? $v : null; }
function v_url($v, array $schemes = ['http', 'https'], bool $allowRelative = false): ?string {
    $v = is_string($v) ? trim($v) : '';
    if ($v === '' || strlen($v) > 1000) return null;
    if ($allowRelative && preg_match('#^(?!//)[A-Za-z0-9_\-./%]+$#', $v) && strpos($v, '..') === false) return $v;
    $p = parse_url($v);
    if (!$p || empty($p['scheme']) || !in_array(strtolower($p['scheme']), $schemes, true)) return null;
    if (in_array(strtolower($p['scheme']), ['http', 'https'], true) && empty($p['host'])) return null;
    if (preg_match('/[\s<>"\'\\\\]/', $v)) return null;
    return $v;
}

/** Accepts only Google Drive/Docs share links and returns a clean https link, or null. Handles /file/d/ID, open?id=ID, uc?id=ID, folders and Docs/Slides/Sheets. */
function gdrive_normalize($v): ?string {
    $v = is_string($v) ? trim($v) : '';
    if ($v === '' || strlen($v) > 500 || preg_match('/[\s<>"\'\\\\]/', $v)) return null;
    $p = parse_url($v);
    if (!$p || strtolower((string)($p['scheme'] ?? '')) !== 'https') return null;
    $host = strtolower((string)($p['host'] ?? '')); $path = (string)($p['path'] ?? ''); parse_str((string)($p['query'] ?? ''), $qs);
    if ($host === 'drive.google.com') {
        if (preg_match('#^/file/d/([A-Za-z0-9_-]{10,100})#', $path, $m) || preg_match('#^/(?:u/\d+/)?(?:open|uc)$#', $path) && isset($qs['id']) && preg_match('/^[A-Za-z0-9_-]{10,100}$/', (string)$qs['id']) && ($m = [1 => $qs['id']])) return 'https://drive.google.com/file/d/' . $m[1] . '/view?usp=sharing';
        if (preg_match('#^/drive/(?:u/\d+/)?folders/([A-Za-z0-9_-]{10,100})#', $path, $m)) return 'https://drive.google.com/drive/folders/' . $m[1] . '?usp=sharing';
        return null;
    }
    if ($host === 'docs.google.com' && preg_match('#^/(document|presentation|spreadsheets)/d/([A-Za-z0-9_-]{10,100})#', $path, $m)) return 'https://docs.google.com/' . $m[1] . '/d/' . $m[2] . '/edit?usp=sharing';
    return null;
}
