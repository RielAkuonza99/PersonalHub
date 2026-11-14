<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/security.php';
require __DIR__ . '/../../includes/content.php';
require __DIR__ . '/../../includes/providers/brevo.php';
send_security_headers();
public_api_cors();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') json_out(['error' => 'method_not_allowed'], 405);
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') !== 0) json_out(['error' => 'unsupported_media_type'], 415);
$raw = file_get_contents('php://input', false, null, 0, 20000);
$in = json_decode((string)$raw, true);
if (!is_array($in)) json_out(['error' => 'invalid_json'], 400);
$name = v_str($in['name'] ?? null, 2, 120); $email = v_email($in['email'] ?? null); $msg = v_str($in['message'] ?? null, 10, 5000);
if (!$name || !$email || !$msg) json_out(['error' => 'validation'], 422);
if (rate_blocked('contact', (int)cfg('CONTACT_PER_HOUR', '5'), 3600)) json_out(['error' => 'rate_limited'], 429);
if ((int)q_val('SELECT COUNT(*) FROM messages WHERE created_at > ?', [gmdate('Y-m-d H:i:s', time() - 86400)]) >= (int)cfg('CONTACT_PER_DAY_GLOBAL', '100')) json_out(['error' => 'rate_limited'], 429);
rate_hit('contact');
q('INSERT INTO messages (name,email,body,ip_hash,created_at) VALUES (?,?,?,?,?)', [$name, $email, $msg, ip_hash(), now()]);
$id = (int)db()->lastInsertId();
// Notification is attempted once per stored message (no resend on refresh). Failure never blocks the visitor.
brevo_send('contact', 'Pesan baru dari portofolio: ' . $name,
    '<p><strong>' . e($name) . '</strong> (' . e($email) . ')</p><p>' . nl2br(e($msg)) . '</p><p>Pesan #' . $id . ' tersimpan di dashboard.</p>', $email);
json_out(['ok' => true], 201);
