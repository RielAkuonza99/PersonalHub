<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/security.php';
require __DIR__ . '/../../includes/content.php';
send_security_headers();
public_api_cors();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') json_out(['error' => 'method_not_allowed'], 405);
try { $p = public_payload(); } catch (Throwable $e) { error_log('[portfolio] content: ' . $e->getMessage()); json_out(['error' => 'unavailable'], 503); }
if (!$p) json_out(['error' => 'empty'], 503);
header('Cache-Control: no-cache');
json_out($p);
