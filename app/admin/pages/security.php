<?php
$act = post_action();
if ($act !== null && $act !== 'change_password') back('security', 'err', 'Aksi tidak valid.');
if ($act === 'change_password') {
    $a = admin_row(); $cur = $_POST['current'] ?? null; $new = $_POST['new'] ?? null; $new2 = $_POST['new2'] ?? null;
    if (rate_blocked('pwchange', 5, 900)) back('security', 'err', 'Terlalu banyak percobaan.');
    if (!is_string($cur) || strlen($cur) > 200 || !is_string($new) || !is_string($new2) || strlen($new) > 200 || strlen($new2) > 200) back('security', 'err', 'Input tidak valid.');
    if (!$a || !password_verify($cur, $a['password_hash'])) { rate_hit('pwchange'); audit('password_change_fail'); back('security', 'err', 'Password saat ini salah.'); }
    if (!password_ok($new) || !hash_equals($new, $new2)) back('security', 'err', 'Password baru min. 8 karakter (huruf + angka) dan harus sama dengan konfirmasi.');
    q('UPDATE admins SET password_hash=? WHERE id=1', [password_hash($new, PASSWORD_DEFAULT)]);
    rate_clear('pwchange'); audit('password_changed'); session_regenerate_id(true); back('security', 'ok', 'Password diganti.');
}
layout_head('Security', 'security');
echo '<div class="card"><h2>Login dua langkah</h2><p>Login admin memakai password, lalu <strong>kode OTP 6 digit yang dikirim ke ' . e(otp_recipient_masked()) . '</strong>. Kode berlaku 10 menit, sekali pakai, dan dibatasi 5 kali salah.</p><p class="muted">Alamat tujuan diatur lewat <code>ADMIN_OTP_EMAIL</code> di config/.env. Pengiriman memakai Brevo, jadi pastikan uji koneksi di menu Tools berhasil.</p></div>';
echo '<div class="card"><h2>Ganti password</h2><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="change_password"><label class="full">Password saat ini<input type="password" name="current" required maxlength="200" autocomplete="current-password"></label><label>Password baru (min. 8, huruf + angka)<input type="password" name="new" required minlength="8" maxlength="200" autocomplete="new-password"></label><label>Ulangi password baru<input type="password" name="new2" required minlength="8" maxlength="200" autocomplete="new-password"></label><div class="full"><button class="btn" type="submit" data-confirm="Ganti password admin sekarang?">Ganti password</button></div></form></div>';
echo '<div class="card"><h2>Audit log (100 terbaru)</h2><div class="content-table"><table><tr><th>Waktu (UTC)</th><th>Aksi</th><th>Detail</th></tr>';
foreach (q_all('SELECT action, detail, created_at FROM audit_log ORDER BY id DESC LIMIT 100') as $r) echo '<tr><td>' . e($r['created_at']) . '</td><td>' . e($r['action']) . '</td><td>' . e($r['detail']) . '</td></tr>';
echo '</table></div></div><div class="card"><h2>Notifikasi email (30 terbaru)</h2><div class="content-table"><table><tr><th>Waktu</th><th>Event</th><th>Status</th><th>Detail</th></tr>';
foreach (q_all('SELECT * FROM notification_log ORDER BY id DESC LIMIT 30') as $r) echo '<tr><td>' . e($r['created_at']) . '</td><td>' . e($r['event']) . '</td><td><span class="pill ' . e($r['status']) . '">' . e($r['status']) . '</span></td><td>' . e($r['detail']) . '</td></tr>';
echo '</table></div></div>'; layout_foot();
