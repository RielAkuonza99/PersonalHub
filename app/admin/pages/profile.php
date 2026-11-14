<?php
$fields = [
  'logo' => ['label' => 'Logo teks', 'max' => 60, 'required' => true],
  'title' => ['label' => 'Judul hero (H1)', 'max' => 120, 'required' => true],
  'name' => ['label' => 'Nama lengkap', 'max' => 120, 'required' => true],
  'email' => ['label' => 'Email kontak publik', 'type' => 'email', 'max' => 190, 'required' => true],
  'tagline' => ['label' => 'Tagline', 'type' => 'textarea', 'max' => 600, 'required' => true, 'full' => true],
  'photo' => ['label' => 'Foto profil (URL ImgBB atau path)', 'type' => 'media', 'required' => true, 'full' => true],
  'cv_url' => ['label' => 'CV (tautan Google Drive)', 'type' => 'gdrive', 'full' => true, 'help' => 'Tempel tautan berbagi Google Drive (atur akses "Siapa saja yang memiliki link"). Kosongkan untuk menonaktifkan tombol CV. Dibuka di tab baru.'],
];
$act = post_action();
if ($act === 'save') {
    $vals = []; $err = null;
    foreach ($fields as $n => $f) { $vals[$n] = fld_value($f, $_POST[$n] ?? '', $err); if ($err) break; }
    if (!$err && !v_email($vals['email'])) $err = 'Email tidak valid';
    if ($err) back('profile', 'err', $err);
    if (!q_one('SELECT id FROM site_profile WHERE id=1')) back('profile', 'err', 'Data awal belum ada. Jalankan impor di menu Tools.');
    $set = implode(',', array_map(function ($k) { return "`$k`=?"; }, array_keys($vals)));
    q("UPDATE site_profile SET $set WHERE id=1", array_values($vals)); audit('update_profile'); back('profile', 'ok', 'Profil tersimpan.');
}
$row = q_one('SELECT * FROM site_profile WHERE id=1');
layout_head('Profile', 'profile');
if (!$row) echo '<p class="msg err">Database belum berisi profil. Buka Tools &rarr; Impor data.json.</p>';
else {
    echo '<div class="card"><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="save">';
    foreach ($fields as $n => $f) echo fld_input($f, $n, $row[$n]);
    echo '<div class="full"><button class="btn" type="submit">Simpan</button></div></form></div>'; media_datalist();
}
layout_foot();
