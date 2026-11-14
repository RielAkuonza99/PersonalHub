<?php
// Image library (ImgBB). Videos now come from TikTok (menu "Video TikTok"); documents are plain links in the content forms.
function media_usage(array $m): int {
    $u = $m['url']; $n = 0;
    $n += (int)q_val('SELECT COUNT(*) FROM site_profile WHERE photo=? OR cv_url=?', [$u, $u]);
    $n += (int)q_val('SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND (image_url=? OR video_url=? OR demo_url=?)', [$u, $u, $u]);
    $n += (int)q_val('SELECT COUNT(*) FROM settings WHERE svalue=?', [$u]);
    return $n;
}
$act = post_action();
if ($act !== null) {
    if (!in_array($act, ['upload_image', 'rename', 'delete', 'show_delete'], true)) back('media', 'err', 'Aksi tidak valid.');
    try {
        if ($act === 'upload_image') {
            $alt = v_str($_POST['alt'] ?? '', 0, 255);
            if ($alt === null) back('media', 'err', 'Keterangan tidak valid.');
            $file = $_FILES['file'] ?? [];
            if (!is_array($file)) back('media', 'err', 'File tidak valid.');
            $id = imgbb_upload($file, $alt); audit('media_upload', 'imgbb #' . $id);
            back('media', 'ok', 'Gambar diunggah ke ImgBB. URL tersimpan di database.');
        } elseif ($act === 'rename') {
            $n = v_str($_POST['filename'] ?? null, 1, 200); $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$n || $id === false || $id < 1) back('media', 'err', 'Nama atau ID media tidak valid.');
            q('UPDATE media SET filename=? WHERE id=? AND deleted_at IS NULL', [$n, $id]); back('media', 'ok', 'Nama diperbarui (metadata di database).');
        } elseif ($act === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) back('media', 'err', 'ID media tidak valid.');
            $m = q_one('SELECT * FROM media WHERE id=? AND deleted_at IS NULL', [$id]);
            if (!$m) back('media', 'err', 'Media tidak ditemukan.');
            if (media_usage($m) > 0) back('media', 'err', 'Media sedang dipakai di konten. Lepaskan dulu dari profil/proyek/pengaturan.');
            $note = $m['provider'] === 'imgbb' ? 'ImgBB tidak menyediakan API hapus terdokumentasi: buka delete URL dari daftar jika ingin menghapus permanen di ImgBB.' : '';
            q('UPDATE media SET deleted_at=? WHERE id=?', [now(), $m['id']]); audit('media_delete', '#' . $m['id']); back('media', 'ok', 'Media dihapus dari library. ' . $note);
        } else { // show_delete
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) back('media', 'err', 'ID media tidak valid.');
            $m = q_one("SELECT delete_url_enc FROM media WHERE id=? AND provider='imgbb' AND deleted_at IS NULL", [$id]);
            $u = $m && $m['delete_url_enc'] ? dec($m['delete_url_enc']) : null; audit('media_delete_url_viewed', '#' . $id);
            back('media', $u ? 'ok' : 'err', $u ? 'Delete URL ImgBB (rahasia, jangan dibagikan): ' . $u : 'Delete URL tidak tersedia.');
        }
    } catch (RuntimeException $ex) { back('media', 'err', $ex->getMessage()); }
    catch (PDOException $ex) { error_log('[portfolio] media: ' . $ex->getMessage()); back('media', 'err', 'Operasi database gagal.'); }
    back('media');
}
layout_head('Media Library', 'media');
echo '<div class="card"><h2>Upload gambar (ImgBB: ' . e(imgbb_configured() ? 'configured' : 'belum dikonfigurasi') . ')</h2><form method="post" enctype="multipart/form-data" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="upload_image"><label>File (JPEG/PNG/GIF/WEBP, maks ' . e(cfg('MAX_IMAGE_MB', '5')) . ' MB)<input type="file" name="file" accept="image/jpeg,image/png,image/gif,image/webp" required></label><label>Alt text<input name="alt" maxlength="255"></label><div class="full"><button class="btn" type="submit" data-confirm="Unggah gambar ini ke ImgBB?">Upload</button></div></form></div>';
$prov = $_GET['provider'] ?? ''; $kind = $_GET['kind'] ?? ''; $query = $_GET['q'] ?? '';
if (!is_string($prov) || ($prov !== '' && !in_array($prov, ['imgbb', 'manual'], true)) || !is_string($kind) || ($kind !== '' && !in_array($kind, ['image', 'video', 'document'], true))) {
    http_response_code(400); echo 'Filter tidak valid.'; exit;
}
$qs = v_str($query, 0, 120);
if ($qs === null) { http_response_code(400); echo 'Kata pencarian tidak valid.'; exit; }
$w = ['deleted_at IS NULL']; $par = [];
if ($prov) { $w[] = 'provider=?'; $par[] = $prov; } if ($kind) { $w[] = 'kind=?'; $par[] = $kind; }
if ($qs !== '') { $w[] = '(filename LIKE ? OR url LIKE ? OR alt_text LIKE ?)'; $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $qs) . '%'; array_push($par, $like, $like, $like); }
$pg = filter_var($_GET['pg'] ?? '1', FILTER_VALIDATE_INT);
if ($pg === false || $pg < 1 || $pg > 100000) { http_response_code(400); echo 'Nomor halaman tidak valid.'; exit; }
$per = 20; $wh = implode(' AND ', $w);
$total = (int)q_val("SELECT COUNT(*) FROM media WHERE $wh", $par);
$rows = q_all("SELECT * FROM media WHERE $wh ORDER BY id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $par);
echo '<div class="card"><h2>Library (' . $total . ')</h2><form method="get" class="fields"><input type="hidden" name="p" value="media"><label>Cari<input name="q" value="' . e($qs) . '"></label><label>Provider<select name="provider"><option value="">Semua</option>';
foreach (['imgbb', 'manual'] as $o) echo '<option' . ($prov === $o ? ' selected' : '') . '>' . $o . '</option>';
echo '</select></label><label>Jenis<select name="kind"><option value="">Semua</option>'; foreach (['image', 'video', 'document'] as $o) echo '<option' . ($kind === $o ? ' selected' : '') . '>' . $o . '</option>';
echo '</select></label><div class="full"><button class="btn sec" type="submit">Filter</button></div></form><div class="content-table"><table><tr><th>Pratinjau</th><th>Metadata</th><th>Dipakai</th><th>Aksi</th></tr>';
foreach ($rows as $m) {
    $u = media_usage($m);
    $pv = $m['kind'] === 'image' ? '<img class="thumb" src="' . e($m['url']) . '" alt="" loading="lazy" referrerpolicy="no-referrer">' : ($m['kind'] === 'video' ? '<span class="pill">video</span>' : '<span class="pill">dokumen</span>');
    $bt = function ($a, $l, $c = 'sec', $conf = '') use ($m) { return '<form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$m['id'] . '"><input type="hidden" name="action" value="' . e($a) . '"><button class="btn sm ' . e($c) . '" type="submit"' . ($conf ? ' data-confirm="' . e($conf) . '"' : '') . '>' . e($l) . '</button></form>'; };
    echo '<tr><td>' . $pv . '</td><td><a href="' . e($m['url']) . '" target="_blank" rel="noopener noreferrer">' . e($m['filename'] !== '' ? $m['filename'] : $m['url']) . '</a><br><span class="muted">' . e($m['provider']) . ' · ' . e($m['mime_type']) . ($m['size_bytes'] ? ' · ' . round($m['size_bytes'] / 1024) . ' KB' : '') . ' · ' . e($m['created_at']) . ' UTC<br>' . e($m['url']) . '</span></td><td>' . ($u ? '<span class="pill pub">' . $u . '×</span>' : '<span class="pill">tidak dipakai</span>') . '</td><td><div class="row-actions">'
        . ($m['provider'] === 'imgbb' ? $bt('show_delete', 'Delete URL', 'sec', 'Tampilkan tautan penghapusan rahasia?') : '') . $bt('delete', 'Hapus', 'danger', 'Hapus media dari library?') . '</div></td></tr>';
}
echo '</table></div>' . ($total > $per ? '<p class="muted"><a href="index.php?p=media&pg=' . ($pg + 1) . '">Berikutnya</a></p>' : '') . '</div>';
layout_foot();
