<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/providers/brevo.php';

const LOGIN_MAX = 5; const LOGIN_WINDOW = 900;
const OTP_TTL = 600;            // seconds a code stays valid
const OTP_MAX_ATTEMPTS = 5;     // wrong guesses per code
const OTP_RESEND_COOLDOWN = 45; // seconds between sends
const PRE_AUTH_TTL = 900;       // seconds between password step and OTP step

function admin_row(): ?array { return q_one('SELECT * FROM admins WHERE id=1'); }
function admin_active(): bool { $a = admin_row(); return $a && (string)$a['password_hash'] !== ''; }
function password_ok(string $pw): bool { return strlen($pw) >= 8 && strlen($pw) <= 200 && preg_match('/[A-Za-z]/', $pw) && preg_match('/\d/', $pw); }

/** Step 1: username + password. On success sends an OTP to the admin e-mail. Returns null on success or an error string. */
function login_step1(string $user, string $pass): ?string {
    session_boot();
    if (rate_blocked('login', LOGIN_MAX, LOGIN_WINDOW) || (int)q_val("SELECT COUNT(*) FROM audit_log WHERE action='login_fail' AND created_at > ?", [gmdate('Y-m-d H:i:s', time() - 3600)]) >= 30) {
        audit('login_blocked'); return 'Terlalu banyak percobaan. Coba lagi nanti.';
    }
    $a = admin_row(); $dummy = '$2y$10$usesomesillystringforsaltuQ1Zk3yH5mKfWl3dC9mQb1b9u1G8e2';
    $validUser = strlen($user) >= 3 && strlen($user) <= 64 && preg_match('/^[A-Za-z0-9_.-]+$/', $user);
    $validPass = $pass !== '' && strlen($pass) <= 200;
    $okUser = $validUser && $a && (string)$a['password_hash'] !== '' && hash_equals(strtolower($a['username']), strtolower($user));
    $okPass = password_verify($validPass ? $pass : '', $okUser ? $a['password_hash'] : $dummy);
    if (!($okUser && $okPass)) { rate_hit('login'); audit('login_fail'); return 'Username atau password salah.'; }
    if (!session_regenerate_id(true)) { audit('session_regenerate_fail', 'pre-auth'); return 'Tidak dapat membuat sesi login. Coba lagi.'; }
    $_SESSION = ['pre' => ['t' => time(), 'otp' => 0], 'csrf' => bin2hex(random_bytes(32))];
    $err = otp_issue();
    if ($err !== null) { unset($_SESSION['pre']); return $err; }
    return null;
}

/** Creates and e-mails a fresh OTP for the pending login. Older codes stop working. Returns null on success or an error string. */
function otp_issue(): ?string {
    session_boot();
    if (empty($_SESSION['pre'])) return 'Sesi login kedaluwarsa. Mulai ulang.';
    if (rate_blocked('otp_send', 5, LOGIN_WINDOW) || (int)q_val("SELECT COUNT(*) FROM login_otps WHERE created_at > ?", [gmdate('Y-m-d H:i:s', time() - 3600)]) >= 10) {
        audit('otp_send_blocked'); return 'Terlalu banyak permintaan kode. Coba lagi nanti.';
    }
    $last = q_val('SELECT created_at FROM login_otps WHERE admin_id=1 ORDER BY id DESC LIMIT 1');
    if ($last && time() - strtotime($last . ' UTC') < OTP_RESEND_COOLDOWN) return 'Tunggu sebentar sebelum meminta kode baru.';
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    q('UPDATE login_otps SET used_at=? WHERE admin_id=1 AND used_at IS NULL', [now()]);
    q('INSERT INTO login_otps (admin_id, code_hash, attempts, expires_at, created_at) VALUES (1,?,0,?,?)', [otp_hash($code), gmdate('Y-m-d H:i:s', time() + OTP_TTL), now()]);
    $id = (int)db()->lastInsertId();
    if (mt_rand(1, 20) === 1) q('DELETE FROM login_otps WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 172800)]);
    rate_hit('otp_send');
    if (!brevo_send_otp($code, intdiv(OTP_TTL, 60))) {
        q('UPDATE login_otps SET used_at=? WHERE id=?', [now(), $id]);
        audit('otp_send_fail'); return 'Kode OTP tidak dapat dikirim ke email. Coba lagi beberapa saat.';
    }
    $_SESSION['pre']['otp'] = $id; $_SESSION['pre']['t'] = time();
    audit('otp_sent'); return null;
}
function otp_hash(string $code): string { return hash_hmac('sha256', $code, app_key()); }

/** Masked address for the OTP screen, e.g. ri****@gmail.com */
function otp_recipient_masked(): string {
    $to = otp_recipient(); if (!$to) return 'email admin';
    list($u, $d) = explode('@', $to, 2);
    return mb_substr($u, 0, 2) . str_repeat('*', 4) . '@' . $d;
}

/** Step 2: verify the e-mailed OTP. Returns null on success, error string otherwise. */
function login_step2(string $code): ?string {
    session_boot();
    if (empty($_SESSION['pre']) || time() - (int)$_SESSION['pre']['t'] > PRE_AUTH_TTL) { unset($_SESSION['pre']); return 'Sesi login kedaluwarsa. Mulai ulang.'; }
    if (rate_blocked('otp', 10, LOGIN_WINDOW)) { audit('otp_blocked'); return 'Terlalu banyak percobaan. Coba lagi nanti.'; }
    $code = preg_replace('/\s+/', '', $code);
    $row = q_one('SELECT * FROM login_otps WHERE id=? AND admin_id=1', [(int)($_SESSION['pre']['otp'] ?? 0)]);
    if (!$row || $row['used_at'] !== null || strtotime($row['expires_at'] . ' UTC') < time()) return 'Kode kedaluwarsa. Minta kode baru.';
    if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) { q('UPDATE login_otps SET used_at=? WHERE id=?', [now(), $row['id']]); return 'Terlalu banyak salah. Minta kode baru.'; }
    // Count the guess before comparing so parallel requests cannot exceed the limit.
    q('UPDATE login_otps SET attempts=attempts+1 WHERE id=?', [$row['id']]);
    if (!preg_match('/^\d{6}$/', $code) || !hash_equals((string)$row['code_hash'], otp_hash($code))) { rate_hit('otp'); audit('otp_fail'); return 'Kode tidak valid.'; }
    if (q('UPDATE login_otps SET used_at=? WHERE id=? AND used_at IS NULL', [now(), $row['id']])->rowCount() !== 1) return 'Kode sudah dipakai. Minta kode baru.';
    rate_clear('login'); rate_clear('otp'); rate_clear('otp_send');
    if (!session_regenerate_id(true)) { audit('session_regenerate_fail', 'authenticated'); return 'Tidak dapat membuat sesi login. Coba lagi.'; }
    $_SESSION = ['uid' => 1, 'created' => time(), 'last' => time(), 'ua' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 'csrf' => bin2hex(random_bytes(32)), 'login_id' => bin2hex(random_bytes(16))];
    q('UPDATE admins SET last_login_at=? WHERE id=1', [now()]);
    audit('login_ok');
    return null;
}
function admin_logged_in(): bool {
    session_boot();
    if (empty($_SESSION['uid'])) return false;
    if (!isset($_SESSION['last'], $_SESSION['created'], $_SESSION['ua'], $_SESSION['csrf'], $_SESSION['login_id'])) { admin_logout(false); return false; }
    $idle = (int)cfg('SESSION_IDLE_MIN', '30') * 60; $abs = (int)cfg('SESSION_MAX_HOURS', '8') * 3600;
    if (time() - (int)$_SESSION['last'] > $idle || time() - (int)$_SESSION['created'] > $abs || !hash_equals((string)$_SESSION['ua'], hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''))) { admin_logout(false); return false; }
    $_SESSION['last'] = time();
    return true;
}
function require_admin(): void { if (!admin_logged_in()) redirect('login.php'); send_security_headers(true); }
function require_admin_api(): void { if (!admin_logged_in()) json_out(['error' => 'unauthorized'], 401); }
function admin_logout(bool $log = true): void {
    session_boot(); if ($log && !empty($_SESSION['uid'])) audit('logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']); }
    if (!session_destroy()) throw new RuntimeException('Unable to destroy the current session.');
}
