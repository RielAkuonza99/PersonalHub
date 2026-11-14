<?php
$defs = [
  'seo_title' => ['label' => 'Website title', 'max' => 120, 'required' => true], 'seo_description' => ['label' => 'Meta description', 'type' => 'textarea', 'max' => 300, 'full' => true],
  'site_url' => ['label' => 'Alamat website utama (canonical, https)', 'type' => 'media', 'max' => 200, 'full' => true, 'help' => 'Contoh: https://personalhub.my.id (tanpa garis miring di akhir). Dipakai untuk canonical dan data terstruktur Google.'],
  'alt_names' => ['label' => 'Nama lain / panggilan (pisahkan koma)', 'max' => 300, 'full' => true, 'help' => 'Contoh: Riel, Akuonza, Riel Akuonza Reborn. Membantu Google mengenali pencarian dengan variasi nama.'],
  'og_image_url' => ['label' => 'Gambar pratinjau share/pencarian (URL https)', 'type' => 'media', 'full' => true, 'help' => 'Disarankan 1200x630. Kosongkan untuk memakai foto profil.'],
  'og_title' => ['label' => 'Open Graph title', 'max' => 120], 'og_description' => ['label' => 'Open Graph description', 'type' => 'textarea', 'max' => 300, 'full' => true],
  'favicon_url' => ['label' => 'Favicon (URL atau path)', 'type' => 'media', 'full' => true], 'theme_color' => ['label' => 'Theme color (#rrggbb)', 'max' => 7, 'pattern' => '#[0-9a-fA-F]{6}', 'pattern_title' => 'Gunakan format warna #rrggbb.'],
  'google_site_verification' => ['label' => 'Google Search Console verification token', 'max' => 100, 'pattern' => '[A-Za-z0-9_\-]{10,100}', 'pattern_title' => 'Token hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.'],
];
$act = post_action();
if ($act !== null && $act !== 'save') back('settings', 'err', 'Aksi tidak valid.');
if ($act === 'save') {
    $err = null; $vals = [];
    foreach ($defs as $k => $f) { $vals[$k] = fld_value($f, $_POST[$k] ?? '', $err); if ($err) break; }
    if (!$err && $vals['theme_color'] !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $vals['theme_color'])) $err = 'Theme color harus format #rrggbb';
    if (!$err && $vals['google_site_verification'] !== '' && !preg_match('/^[A-Za-z0-9_-]{10,100}$/', $vals['google_site_verification'])) $err = 'Token verifikasi tidak valid';
    if (!$err && $vals['site_url'] !== '' && !preg_match('#^https://[A-Za-z0-9.-]+(?::\d+)?$#', $vals['site_url'])) $err = 'Alamat website harus berformat https://domain (tanpa path atau garis miring akhir)';
    if ($err) back('settings', 'err', $err);
    foreach ($vals as $k => $v) set_setting($k, (string)$v);
    audit('update_settings'); back('settings', 'ok', 'Pengaturan tersimpan.');
}
layout_head('Website Settings', 'settings');
echo '<div class="card"><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="save">';
foreach ($defs as $k => $f) echo fld_input($f, $k, setting($k));
echo '<div class="full"><button class="btn" type="submit">Simpan</button></div></form></div><p class="muted">Tautan sosial diatur di menu Contact.</p>'; media_datalist();
layout_foot();
