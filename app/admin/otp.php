<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
send_security_headers(true); session_boot();
if (admin_logged_in()) redirect('index.php');
if (empty($_SESSION['pre'])) redirect('login.php');
$err = ''; $info = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'resend') {
        $err = otp_issue();
        if ($err === null) { $err = ''; $info = 'Kode baru sudah dikirim. Kode sebelumnya tidak berlaku lagi.'; }
        elseif (empty($_SESSION['pre'])) redirect('login.php');
    } else {
        $code = $_POST['code'] ?? null;
        if (is_string($code)) $err = login_step2($code);
        else { rate_hit('otp'); audit('otp_fail'); $err = 'Kode tidak valid.'; }
        if ($err === null) {
            // Optional security notice (once per successful login, never on refresh).
            if (cfg_bool('NOTIFY_ON_LOGIN', true) && brevo_configured()) brevo_send('login', 'Login dashboard portofolio', '<p>Login berhasil pada ' . e(now()) . ' UTC.</p><p>Jika bukan Anda, ganti password admin sekarang.</p>');
            redirect('index.php');
        }
        if (empty($_SESSION['pre'])) redirect('login.php');
    }
}
auth_head('Kode OTP', 'Verifikasi email', 'Kode 6 digit dikirim ke ' . otp_recipient_masked() . '. Periksa juga folder spam.');
?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<?php if ($err) echo '<p class="msg err" role="alert">' . e($err) . '</p>'; if ($info) echo '<p class="msg ok" role="status">' . e($info) . '</p>'; ?>
<label>Kode OTP<input class="otp-input" name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" minlength="6" placeholder="000000" autocomplete="one-time-code" data-autosubmit-digits="6" autofocus></label>
<button class="btn auth-submit" type="submit" data-loading-label="Memverifikasi..."><span>Masuk</span></button></form>
<form method="post" class="auth-secondary"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="btn sec" type="submit" data-loading-label="Mengirim...">Kirim ulang kode</button><a class="auth-link" href="login.php">Ganti akun / mulai ulang</a></form>
<?php auth_foot();
