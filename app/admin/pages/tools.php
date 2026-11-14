<?php
$act = post_action(); $report = null;
if ($act !== null && !in_array($act, ['import', 'verify', 'test_provider', 'test_email'], true)) back('tools', 'err', 'Aksi tidak valid.');
if ($act === 'import') {
    try { $rep = import_data_json(APP_ROOT . '/data/data.json'); audit('import_json', json_encode($rep)); $report = ['Impor selesai (baris baru ditambahkan; data yang sudah ada TIDAK ditimpa)', $rep]; } catch (Throwable $ex) { error_log('[portfolio] import: ' . $ex->getMessage()); back('tools', 'err', 'Impor gagal: data tidak diubah (transaksi dibatalkan).'); }
} elseif ($act === 'verify') {
    $d = verify_against_json(APP_ROOT . '/data/data.json'); $report = [$d ? 'Ada perbedaan (wajar jika Anda sudah mengedit lewat dashboard)' : 'Database identik dengan data.json', $d];
} elseif ($act === 'test_provider') {
    $p = $_POST['provider'] ?? null;
    if (!is_string($p)) back('tools', 'err', 'Provider tidak dikenal.');
    $fn = ['brevo' => 'brevo_test', 'tiktok' => 'tiktok_test'][$p] ?? null;
    if ($p === 'imgbb') $res = imgbb_configured() ? ['configured', 'API key terisi. ImgBB tidak punya endpoint uji; keberhasilan terbukti saat upload pertama.'] : ['unconfigured', 'IMGBB_API_KEY belum diisi'];
    elseif ($fn) $res = $fn(); else back('tools', 'err', 'Provider tidak dikenal.');
    q('INSERT INTO provider_status (provider,status,message,checked_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), message=VALUES(message), checked_at=VALUES(checked_at)', [$p, $res[0], mb_substr($res[1], 0, 290), now()]);
    audit('provider_test', $p . ':' . $res[0]); back('tools', $res[0] === 'failed' ? 'err' : 'ok', strtoupper($p) . ': ' . $res[0] . ' — ' . $res[1]);
} elseif ($act === 'test_email') {
    $ok = brevo_send('test', 'Tes notifikasi portofolio', '<p>Email uji dari dashboard.</p>'); back('tools', $ok ? 'ok' : 'err', $ok ? 'Brevo menerima email (messageId diterima).' : 'Email tidak terkirim. Lihat log notifikasi di menu Security.');
}
layout_head('Tools', 'tools');
if ($report) { echo '<div class="card"><h2>' . e($report[0]) . '</h2><pre class="codes">' . e(json_encode($report[1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></div>'; }
echo '<div class="card"><h2>Migrasi data.json &rarr; database</h2><p class="muted">Aman dijalankan ulang: baris yang sudah ada dilewati. Gunakan "Verifikasi" untuk membandingkan isi.</p><form method="post" class="row-actions">' . csrf_field() . '<button class="btn" name="action" value="import" type="submit" data-confirm="Jalankan impor?">Impor data.json</button><button class="btn sec" name="action" value="verify" type="submit">Verifikasi</button></form></div>';
echo '<div class="card"><h2>Uji koneksi provider</h2><form method="post" class="row-actions" data-confirm="Uji koneksi provider pilihan?">' . csrf_field() . '<input type="hidden" name="action" value="test_provider">';
foreach (['imgbb', 'tiktok', 'brevo'] as $p) echo '<button class="btn sec" name="provider" value="' . $p . '" type="submit">' . $p . '</button>';
echo '</form><form method="post">' . csrf_field() . '<p><button class="btn sec" name="action" value="test_email" type="submit" data-confirm="Kirim email uji ke alamat notifikasi admin?">Kirim email uji (Brevo)</button></p><p class="muted">Login admin memakai kode OTP yang dikirim lewat Brevo ke ADMIN_OTP_EMAIL. Jika uji ini gagal, login tidak bisa mengirim kode.</p></form></div>';
echo '<div class="card"><h2>Backup</h2><p><a class="btn" href="../api/admin/export.php">Unduh backup konten (JSON)</a></p><p class="muted">Backup berisi konten saja (tanpa kredensial, TOTP, recovery code, atau delete URL). Untuk dump lengkap gunakan phpMyAdmin &rarr; Export.</p></div>';
layout_foot();
