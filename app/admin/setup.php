<?php
declare(strict_types=1);
// One-time creation of the admin account. Disabled (404) once an account exists. Requires SETUP_KEY from the private config.
// Login uses password + e-mail OTP, so there is nothing to scan or enrol here.
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
send_security_headers(true); session_boot();
if (admin_active()) { http_response_code(404); echo 'Not found.'; exit; }
$setupKey = (string)cfg('SETUP_KEY');
if (strlen($setupKey) < 24) { http_response_code(503); echo 'SETUP_KEY belum dikonfigurasi (minimal 24 karakter) di file konfigurasi privat.'; exit; }
$err = ''; $done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (rate_blocked('setup', 5, 900)) { $err = 'Terlalu banyak percobaan. Coba lagi nanti.'; }
    else {
        $user = v_str($_POST['username'] ?? null, 3, 64);
        $pw = $_POST['password'] ?? null; $pw2 = $_POST['password2'] ?? null; $submittedKey = $_POST['setup_key'] ?? null;
        if (!is_string($submittedKey) || strlen($submittedKey) > 256 || !hash_equals($setupKey, $submittedKey)) { rate_hit('setup'); audit('setup_key_fail'); $err = 'Setup key salah.'; }
        elseif (!$user || !preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $user)) $err = 'Username 3-64 karakter (huruf, angka, _ . -).';
        elseif (!is_string($pw) || !password_ok($pw)) $err = 'Password minimal 8 karakter dan mengandung huruf dan angka.';
        elseif (!is_string($pw2) || !hash_equals($pw, $pw2)) $err = 'Konfirmasi password tidak sama.';
        elseif (!otp_recipient()) $err = 'Isi ADMIN_OTP_EMAIL (atau ADMIN_NOTIFICATION_EMAIL) di config/.env lebih dulu: kode OTP login dikirim ke sana.';
        else {
            q('INSERT INTO admins (id, username, password_hash, created_at) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash)', [$user, password_hash($pw, PASSWORD_DEFAULT), now()]);
            audit('setup_completed'); $done = true;
        }
    }
}
if ($done) {
    auth_head('Setup selesai', 'Admin access');
    echo '<p class="msg ok" role="status">Akun admin dibuat. Saat login, kode OTP dikirim ke <strong>' . e(otp_recipient_masked()) . '</strong>.</p><p><a class="btn" href="login.php">Ke halaman login</a></p>';
    auth_foot(); exit;
}
auth_head('Setup admin', 'Pengaturan awal', 'Buat akun admin. Login memakai password lalu kode OTP email.');
if ($err) echo '<p class="msg err" role="alert">' . e($err) . '</p>'; ?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<label>Setup key (dari file konfigurasi)<input name="setup_key" type="password" required autocomplete="off"></label>
<label>Username<input name="username" required minlength="3" maxlength="64" pattern="[A-Za-z0-9_.\-]+" title="Gunakan 3-64 huruf, angka, titik, garis bawah, atau tanda hubung." autocomplete="off" autocapitalize="none" value="<?= e((string)($_POST['username'] ?? '')) ?>"></label>
<label>Password (min. 8 karakter, huruf dan angka)<input name="password" type="password" required minlength="8" maxlength="200" autocomplete="new-password"></label>
<label>Ulangi password<input name="password2" type="password" required minlength="8" maxlength="200" autocomplete="new-password"></label>
<button class="btn auth-submit" type="submit" data-confirm="Buat akun admin dengan data ini?"><span>Buat akun</span></button></form>
<?php auth_foot();
