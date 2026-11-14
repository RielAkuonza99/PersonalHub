<?php
$act = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_t'] ?? '') === 'messages') ? post_action() : null;
if ($act !== null) {
    if (!in_array($act, ['read', 'archive', 'new', 'delete'], true)) back('contact', 'err', 'Aksi pesan tidak valid.');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) back('contact', 'err', 'ID pesan tidak valid.');
    if ($act === 'read' || $act === 'archive' || $act === 'new') q('UPDATE messages SET status=? WHERE id=? AND deleted_at IS NULL', [$act, $id]);
    elseif ($act === 'delete') { q('UPDATE messages SET deleted_at=? WHERE id=?', [now(), $id]); audit('delete_message', '#' . $id); }
    back('contact', 'ok', 'Pesan diperbarui.');
}
layout_head('Contact', 'contact');
echo '<p class="muted">Email kontak publik diubah di menu Profile. Berikut tautan sosial dan pesan masuk.</p><h2>Social media</h2>';
crud(['table' => 'socials', 'page' => 'contact', 'label' => 'social', 'soft' => true, 'order' => true, 'status' => true,
  'fields' => ['provider_id' => ['label' => 'Ikon', 'type' => 'select', 'options' => ['instagram' => 'Instagram', 'github' => 'GitHub', 'tiktok' => 'TikTok', 'x' => 'X', 'link' => 'Link umum']],
               'label' => ['label' => 'Label', 'max' => 120, 'required' => true], 'url' => ['label' => 'URL', 'type' => 'url', 'required' => true]],
  'list' => ['label' => 'Label', 'url' => 'URL'], 'trim' => ['url' => 50]]);
$st = in_array($_GET['s'] ?? '', ['new', 'read', 'archived'], true) ? $_GET['s'] : '';
$pg = max(1, (int)($_GET['pg'] ?? 1)); $per = 20;
$where = 'deleted_at IS NULL' . ($st ? ' AND status=?' : ''); $par = $st ? [$st] : [];
$total = (int)q_val("SELECT COUNT(*) FROM messages WHERE $where", $par);
$rows = q_all("SELECT * FROM messages WHERE $where ORDER BY id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $par);
echo '<div class="card"><h2>Pesan masuk (' . $total . ')</h2><p class="muted">Filter: <a href="index.php?p=contact">semua</a> · <a href="index.php?p=contact&s=new">baru</a> · <a href="index.php?p=contact&s=read">dibaca</a> · <a href="index.php?p=contact&s=archived">arsip</a></p><table><tr><th>Pengirim</th><th>Pesan</th><th>Aksi</th></tr>';
foreach ($rows as $m) {
    $b = function ($a, $l, $c = 'sec', $conf = '') use ($m) { return '<form method="post">' . csrf_field() . '<input type="hidden" name="_t" value="messages"><input type="hidden" name="id" value="' . (int)$m['id'] . '"><input type="hidden" name="action" value="' . $a . '"><button class="btn sm ' . $c . '" type="submit"' . ($conf ? ' data-confirm="' . e($conf) . '"' : '') . '>' . $l . '</button></form>'; };
    echo '<tr><td>' . e($m['name']) . '<br><a href="mailto:' . e($m['email']) . '">' . e($m['email']) . '</a><br><span class="muted">' . e($m['created_at']) . ' UTC</span> <span class="pill">' . e($m['status']) . '</span></td><td>' . nl2br(e($m['body'])) . '</td><td><div class="row-actions">' . $b('read', 'Dibaca') . $b('archive', 'Arsip') . $b('delete', 'Hapus', 'danger', 'Hapus pesan ini?') . '</div></td></tr>';
}
echo '</table>' . ($total > $per ? '<p class="muted"><a href="index.php?p=contact&pg=' . ($pg + 1) . '">Berikutnya</a></p>' : '') . '</div>';
layout_foot();
