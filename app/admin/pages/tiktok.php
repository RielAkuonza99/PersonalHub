<?php
// Post a TikTok link here; the video then plays on the public website straight from TikTok (Embed Player).
$act = post_action();
if ($act !== null && !in_array($act, ['add', 'toggle', 'up', 'down', 'refresh', 'delete'], true)) back('tiktok', 'err', 'Aksi tidak valid.');
if ($act !== null) {
    try {
        if ($act === 'add') {
            $url = v_str($_POST['url'] ?? null, 1, 500);
            $caption = v_str($_POST['caption'] ?? '', 0, 255);
            $status = $_POST['status'] ?? 'published';
            if (!$url) back('tiktok', 'err', 'Tautan TikTok wajib diisi.');
            if ($caption === null) back('tiktok', 'err', 'Keterangan tidak valid.');
            if (!is_string($status) || !in_array($status, ['published', 'draft'], true)) back('tiktok', 'err', 'Status tidak valid.');
            $r = tiktok_add($url, $caption, $status);
            audit('tiktok_add', '#' . $r['id']);
            back('tiktok', 'ok', 'Video diposting' . ($r['verified'] ? ' dan terverifikasi oleh TikTok.' : ' (TikTok belum dapat dijangkau; gunakan Segarkan nanti untuk melengkapi info).') . ($status === 'published' ? ' Video langsung tampil di website.' : ' Status draft: belum tampil di website.'));
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) back('tiktok', 'err', 'ID video tidak valid.');
        if ($act === 'toggle') {
            q("UPDATE tiktok_videos SET status=IF(status='published','draft','published') WHERE id=? AND deleted_at IS NULL", [$id]);
            audit('tiktok_toggle', '#' . $id); back('tiktok', 'ok', 'Status video diubah.');
        } elseif ($act === 'up' || $act === 'down') {
            $ids = array_map('intval', array_column(q_all('SELECT id FROM tiktok_videos WHERE deleted_at IS NULL ORDER BY sort_order, id'), 'id'));
            $i = array_search($id, $ids, true);
            if ($i !== false) { $j = $act === 'up' ? $i - 1 : $i + 1; if (isset($ids[$j])) { $x = $ids[$i]; $ids[$i] = $ids[$j]; $ids[$j] = $x; } }
            $u = db()->prepare('UPDATE tiktok_videos SET sort_order=? WHERE id=?');
            foreach ($ids as $k => $v) $u->execute([($k + 1) * 10, $v]);
            back('tiktok', 'ok', 'Urutan diperbarui.');
        } elseif ($act === 'refresh') {
            $ok = tiktok_refresh($id); audit('tiktok_refresh', '#' . $id);
            back('tiktok', $ok ? 'ok' : 'err', $ok ? 'Info video diperbarui dari TikTok.' : 'TikTok tidak dapat dijangkau sekarang, atau video ditambahkan dari tautan embed tanpa nama akun.');
        } elseif ($act === 'delete') {
            q('UPDATE tiktok_videos SET deleted_at=? WHERE id=? AND deleted_at IS NULL', [now(), $id]);
            audit('tiktok_delete', '#' . $id); back('tiktok', 'ok', 'Video dihapus dari website. Video asli di TikTok tidak terpengaruh.');
        }
    } catch (RuntimeException $ex) { back('tiktok', 'err', $ex->getMessage()); }
    catch (PDOException $ex) { error_log('[portfolio] tiktok: ' . $ex->getMessage()); back('tiktok', 'err', 'Operasi database gagal. Pastikan database/upgrade_v3_otp_tiktok.sql sudah dijalankan.'); }
    back('tiktok');
}

layout_head('Video TikTok', 'tiktok');
echo '<div class="card"><div class="section-heading"><div><p class="muted">Posting baru</p><h2>Tambah video dari TikTok</h2></div></div>'
    . '<p class="muted">Tempel tautan video TikTok milikmu. Video diputar langsung dari TikTok di halaman Videos website, tidak diunggah ulang.</p>'
    . '<form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="add">'
    . '<label class="full">Tautan video TikTok<input name="url" type="text" inputmode="url" required maxlength="500" placeholder="https://www.tiktok.com/@akunmu/video/1234567890123456789" autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="off"><span class="muted">Tautan lengkap atau tautan pendek (vm.tiktok.com). Hanya video; postingan foto belum didukung.</span></label>'
    . '<label class="full">Keterangan (opsional)<input name="caption" maxlength="255" placeholder="Tampil di bawah video"></label>'
    . '<label>Status<select name="status"><option value="published">Published (langsung tampil)</option><option value="draft">Draft (disembunyikan)</option></select></label>'
    . '<div class="full editor-actions"><button class="btn" type="submit" data-loading-label="Memeriksa ke TikTok...">Posting video</button></div></form></div>';

$rows = q_all('SELECT * FROM tiktok_videos WHERE deleted_at IS NULL ORDER BY sort_order, id LIMIT 200');
echo '<div class="card"><div class="section-heading"><div><p class="muted">Daftar video</p><h2>Video (' . count($rows) . ')</h2></div></div>';
if (!$rows) echo '<div class="empty-state"><strong>Belum ada video</strong><p>Tempel tautan TikTok di atas untuk mulai memutar video di website.</p></div>';
else {
    echo '<div class="vid-list">';
    $n = count($rows);
    foreach ($rows as $i => $r) {
        $btn = function (string $a, string $label, string $cls = 'sec', string $conf = '') use ($r) {
            return '<form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="action" value="' . e($a) . '"><button class="btn sm ' . e($cls) . '" type="submit"' . ($conf !== '' ? ' data-confirm="' . e($conf) . '"' : '') . '>' . e($label) . '</button></form>';
        };
        $thumb = $r['thumbnail_url'] !== '' ? '<img src="' . e($r['thumbnail_url']) . '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '<span class="muted">Tanpa thumbnail</span>';
        $title = $r['title'] !== '' ? mb_strimwidth($r['title'], 0, 140, '…') : 'Video ' . $r['video_id'];
        echo '<article class="vid-card"><div class="vid-thumb" data-embed="' . e(tiktok_player_url($r['video_id'], true)) . '">' . $thumb
            . '<button class="vid-play" type="button" data-preview aria-label="Putar pratinjau video">&#9654;<span>Putar</span></button></div>'
            . '<div class="vid-body"><h3>' . e($title) . '</h3>'
            . '<p class="muted">' . ($r['author_name'] !== '' ? e($r['author_name']) . ' · ' : '') . '<a href="' . e($r['url']) . '" target="_blank" rel="noopener noreferrer">Buka di TikTok</a></p>'
            . ($r['caption'] !== '' ? '<p>' . e($r['caption']) . '</p>' : '')
            . '<p><span class="pill ' . ($r['status'] === 'published' ? 'pub' : '') . '">' . e($r['status']) . '</span> <span class="pill ' . ($r['verified'] ? 'success' : '') . '">' . ($r['verified'] ? 'terverifikasi' : 'belum diverifikasi') . '</span></p>'
            . '<div class="row-actions">' . ($i > 0 ? $btn('up', '↑') : '') . ($i < $n - 1 ? $btn('down', '↓') : '')
            . $btn('toggle', $r['status'] === 'published' ? 'Sembunyikan' : 'Tampilkan', 'sec', 'Ubah status tampil video ini?') . $btn('refresh', 'Segarkan') . $btn('delete', 'Hapus', 'danger', 'Hapus video ini dari website?') . '</div></div></article>';
    }
    echo '</div>';
}
echo '</div>';
layout_foot();
