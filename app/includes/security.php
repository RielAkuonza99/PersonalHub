<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function send_security_headers(bool $admin = false): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    if ($admin) {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; frame-src https://www.tiktok.com; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
}
function session_boot(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('pf_sid');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => is_https() && cfg_bool('SESSION_SECURE', true),
        'httponly' => true, 'samesite' => (string)cfg('SESSION_SAME_SITE', 'Strict'),
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}
function csrf_token(): string {
    session_boot();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function same_origin(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
        if (!empty($_SERVER[$h])) { $p = parse_url($_SERVER[$h]); return isset($p['host']) && strcasecmp($p['host'] . (isset($p['port']) ? ':' . $p['port'] : ''), $host) === 0; }
    }
    return true; // no header sent (some privacy settings); CSRF token remains the primary control
}
function public_api_cors(): void {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    header('Vary: Origin');
    $allowed = array_filter(array_map('trim', explode(',', (string)cfg(
        'CORS_ALLOWED_ORIGINS',
        'https://personalhub.my.id,https://www.personalhub.my.id'
    ))));
    if ($origin !== '' && !in_array($origin, $allowed, true)) json_out(['error' => 'forbidden'], 403);
    if ($origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
function csrf_verify(): void {
    session_boot();
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf']) || !is_string($t) || !hash_equals($_SESSION['csrf'], $t) || !same_origin()) {
        http_response_code(403); echo 'Invalid request (CSRF).'; exit;
    }
}
function redirect(string $to): void { header('Location: ' . $to); exit; }

function sanitize_rich_html(string $html): string {
    $html = trim($html);
    if ($html === '') return '';
    if (!class_exists('DOMDocument')) {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1\s*>/is', '', $html) ?? $html;
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><blockquote><code><span>';
        $safe = strip_tags($html, $allowedTags);
        $safe = preg_replace_callback('/<a\b([^>]*)>/i', static function (array $m): string {
            if (preg_match('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $m[1], $href)) {
                $href = trim(html_entity_decode($href[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')); $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
                if (in_array($scheme, ['http','https','mailto'], true)) return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">';
            }
            return '<a target="_blank" rel="noopener noreferrer">';
        }, $safe) ?? $safe;
        $safe = preg_replace('/<(?!a\b)([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $safe) ?? $safe;
        return trim($safe);
    }

    $allowed = ['p','br','strong','b','em','i','u','ul','ol','li','a','h2','h3','blockquote','code','span'];
    $dom = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><div id="__rich_root__">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) return '';

    foreach (iterator_to_array($dom->getElementsByTagName('*')) as $node) {
        if (!$node instanceof DOMElement) continue;
        $tag = strtolower($node->tagName);
        if ($tag === 'script' || $tag === 'style') {
            if ($node->parentNode) $node->parentNode->removeChild($node);
            continue;
        }
        if (!in_array($tag, $allowed, true)) {
            while ($node->firstChild && $node->parentNode) $node->parentNode->insertBefore($node->firstChild, $node);
            if ($node->parentNode) $node->parentNode->removeChild($node);
            continue;
        }
        for ($i = $node->attributes->length - 1; $i >= 0; $i--) {
            $attr = $node->attributes->item($i);
            if ($tag === 'a' && strtolower($attr->name) === 'href') continue;
            if ($tag === 'a' && in_array(strtolower($attr->name), ['target','rel'], true)) continue;
            $node->removeAttributeNode($attr);
        }
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
            if ($href === '' || !in_array($scheme, ['http','https','mailto'], true)) {
                $node->removeAttribute('href');
            } else {
                $node->setAttribute('href', $href);
            }
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener noreferrer');
        }
    }

    $root = $dom->getElementById('__rich_root__');
    if (!$root) return '';
    $out = '';
    foreach ($root->childNodes as $child) $out .= $dom->saveHTML($child);
    return trim($out);
}

function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
