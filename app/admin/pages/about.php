<?php
$act = post_action();
if ($act !== null && $act !== 'save_about') back('about', 'err', 'Aksi tidak valid.');
if ($act === 'save_about') {
    $err = null;
    $v = fld_value(['label' => 'Teks About', 'type' => 'textarea', 'max' => 3000, 'required' => true], $_POST['about'] ?? '', $err);
    if ($err) back('about', 'err', $err);
    q('UPDATE site_profile SET about=? WHERE id=1', [$v]); audit('update_about'); back('about', 'ok', 'About tersimpan.');
}
$row = q_one('SELECT about FROM site_profile WHERE id=1');
layout_head('About', 'about');
echo '<div class="card"><form method="post">' . csrf_field() . '<input type="hidden" name="action" value="save_about"><label>Teks About<textarea name="about" required maxlength="3000">' . e($row['about'] ?? '') . '</textarea></label><p><button class="btn" type="submit">Simpan</button></p></form></div>';
crud(['table' => 'profile_items', 'page' => 'about', 'label' => 'item (role / minat / tujuan / bio)', 'soft' => true, 'order' => true, 'status' => true,
  'order_by' => "FIELD(kind,'bio','role','interest','goal'), sort_order, id",
  'fields' => ['kind' => ['label' => 'Jenis', 'type' => 'select', 'options' => ['bio' => 'Bio (kunci → nilai)', 'role' => 'Role / Focus area (efek mengetik)', 'interest' => 'Minat', 'goal' => 'Tujuan pengembangan diri']],
               'label' => ['label' => 'Teks / kunci', 'max' => 190, 'required' => true], 'value' => ['label' => 'Nilai (khusus Bio)', 'max' => 500]],
  'list' => ['kind' => 'Jenis', 'label' => 'Teks / kunci', 'value' => 'Nilai'], 'trim' => ['label' => 70, 'value' => 70]]);
layout_foot();
