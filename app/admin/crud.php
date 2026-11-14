<?php
declare(strict_types=1);
// Generic CRUD for content tables. All SQL identifiers come from code (never from input); all values are bound parameters.

function fld_value(array $f, $raw, ?string &$err) {
    $t = $f['type'] ?? 'text'; $req = !empty($f['required']); $lbl = $f['label'];
    if (!is_string($raw)) { $err = "$lbl tidak valid"; return null; }
    $raw = trim($raw);
    $max = (int)($f['max'] ?? 190);
    if (trim($raw) === '' && !$req && !in_array($t, ['number', 'select'], true)) return $t === 'tags' ? '[]' : '';
    switch ($t) {
        case 'number':
            $v = preg_match('/^-?\d+$/D', $raw) ? filter_var($raw, FILTER_VALIDATE_INT) : false;
            if ($v === false || $v < ($f['min'] ?? 0) || $v > ($f['max'] ?? 100)) { $err = "$lbl harus angka " . ($f['min'] ?? 0) . '-' . ($f['max'] ?? 100); return null; }
            return $v;
        case 'select': if (!array_key_exists($raw, $f['options'])) { $err = "$lbl tidak valid"; return null; } return $raw;
        case 'url': $v = v_url($raw, ['http', 'https'], true); if (!$v) { $err = "$lbl harus URL http(s) atau path relatif"; return null; } return $v;
        case 'gdrive': $v = gdrive_normalize($raw); if (!$v) { $err = "$lbl harus tautan Google Drive/Docs (https://drive.google.com/file/d/...)"; return null; } return $v;
        case 'media': $v = v_url($raw, ['https'], true); if (!$v) { $err = "$lbl harus URL https atau path relatif"; return null; } return $v;
        case 'tags':
            if (mb_strlen($raw) > 1300 || preg_match('//u', $raw) !== 1) { $err = "$lbl terlalu panjang atau formatnya tidak valid"; return null; }
            $a = array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
            if (count($a) > 20 || array_filter($a, function ($x) { return mb_strlen($x) > 60; })) { $err = "$lbl: maks 20 item, masing-masing 60 karakter"; return null; }
            $json = json_encode($a, JSON_UNESCAPED_UNICODE);
            if ($json === false) { $err = "$lbl berisi karakter yang tidak valid"; return null; }
            return $json;
        case 'email':
            $v = v_email($raw);
            if ($v === null) { $err = "$lbl harus berupa alamat email yang valid"; return null; }
            return $v;
        default:
            $v = v_str($raw, $req ? 1 : 0, $max);
            if ($v === null) { $err = $req && $raw === '' ? "$lbl wajib diisi" : "$lbl maksimal $max karakter atau berisi format yang tidak valid"; return null; }
            return $v;
    }
}
function fld_input(array $f, string $name, $val): string {
    $t = $f['type'] ?? 'text'; $l = e($f['label']); $req = !empty($f['required']) ? ' required' : ''; $cls = !empty($f['full']) ? ' class="full"' : '';
    $help = !empty($f['help']) ? '<span class="muted">' . e($f['help']) . '</span>' : '';
    if ($t === 'tags' && is_string($val) && $val !== '') { $d = json_decode($val, true); $val = is_array($d) ? implode(', ', $d) : $val; }
    $v = e($val);
    $name = e($name); $min = !empty($f['required']) ? ' minlength="1"' : '';
    if ($t === 'textarea') return "<label$cls>$l<textarea name=\"$name\" maxlength=\"" . (int)($f['max'] ?? 2000) . "\"$min$req>$v</textarea>$help</label>";
    if ($t === 'select') { $o = ''; foreach ($f['options'] as $k => $lab) $o .= '<option value="' . e((string)$k) . '"' . ((string)$k === (string)$val ? ' selected' : '') . '>' . e($lab) . '</option>'; return "<label$cls>$l<select name=\"$name\" required>$o</select>$help</label>"; }
    if ($t === 'number') return "<label$cls>$l<input type=\"number\" name=\"$name\" min=\"" . (int)($f['min'] ?? 0) . '" max="' . (int)($f['max'] ?? 100) . "\" step=\"1\" value=\"$v\"$req>$help</label>";
    $list = $t === 'media' ? ' list="media-images"' : '';
    $inputType = $t === 'email' ? 'email' : 'text';
    $max = $t === 'tags' ? 1300 : (int)($f['max'] ?? 1000);
    $inputMode = in_array($t, ['url', 'media'], true) ? ' inputmode="url"' : '';
    $pattern = !empty($f['pattern']) ? ' pattern="' . e($f['pattern']) . '"' : '';
    $title = !empty($f['pattern_title']) ? ' title="' . e($f['pattern_title']) . '"' : '';
    return "<label$cls>$l<input type=\"$inputType\" name=\"$name\" value=\"$v\" maxlength=\"$max\"$min$req$list$inputMode$pattern$title autocomplete=\"off\">$help</label>";
}
function media_datalist(): void {
    echo '<datalist id="media-images">';
    foreach (q_all("SELECT url FROM media WHERE kind='image' AND deleted_at IS NULL ORDER BY id DESC LIMIT 100") as $m) echo '<option value="' . e($m['url']) . '">';
    echo '</datalist>';
}
function crud(array $c): void {
    $crudTables = [
        'profile_items' => ['id','kind','label','value','sort_order','status','deleted_at'],
        'skill_categories' => ['id','key_name','label','sort_order','deleted_at','created_at','updated_at'],
        'skills' => ['id','name','category_id','level','sort_order','status','deleted_at','created_at','updated_at'],
        'socials' => ['id','provider_id','label','url','sort_order','status','deleted_at','created_at','updated_at'],
    ];
    $T = (string)($c['table'] ?? '');
    if (!isset($crudTables[$T])) back((string)($c['page'] ?? 'overview'), 'err', 'Tabel CRUD tidak diizinkan.');
    foreach (array_keys($c['fields']) as $column) {
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', (string)$column) || !in_array((string)$column, $crudTables[$T], true)) back((string)$c['page'], 'err', 'Kolom CRUD tidak diizinkan.');
    }
    foreach (array_keys($c['list'] ?? []) as $column) {
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/D', (string)$column) || !in_array((string)$column, $crudTables[$T], true)) back((string)$c['page'], 'err', 'Kolom daftar tidak diizinkan.');
    }
    $page = $c['page']; $fields = $c['fields']; $soft = !empty($c['soft']); $order = !empty($c['order']); $status = !empty($c['status']);
    $where = ($soft ? 'deleted_at IS NULL' : '1=1') . (isset($c['where']) ? ' AND ' . $c['where'] : '');
    $ob = $order ? 'sort_order, id' : (string)($c['order_by'] ?? 'id DESC');
    $allowedOrderBy = ['sort_order, id','id DESC',"FIELD(kind,'bio','role','interest','goal'), sort_order, id"];
    if (!in_array($ob, $allowedOrderBy, true)) back($page, 'err', 'Urutan CRUD tidak diizinkan.');
    $act = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['_t'] ?? '') === $T) ? post_action() : null;
    if ($act !== null) {
        $allowed = ['save', 'delete'];
        if ($status) $allowed[] = 'toggle';
        if ($order) array_push($allowed, 'up', 'down');
        if (!in_array($act, $allowed, true)) back($page, 'err', 'Aksi tidak valid.');
        $id = filter_var($_POST['id'] ?? '0', FILTER_VALIDATE_INT);
        if ($id === false || $id < 0) back($page, 'err', 'ID data tidak valid.');
        try {
            if ($act === 'save') {
                $vals = []; $err = null;
                foreach ($fields as $n => $f) { $vals[$n] = fld_value($f, $_POST[$n] ?? '', $err); if ($err) break; }
                if ($err) { $_SESSION['old'] = $_POST; back($page, 'err', $err . ($id ? '' : '')); }
                if (!empty($c['validate'])) { $err = $c['validate']($vals); if ($err) { back($page, 'err', $err); } }
                if ($status) {
                    $state = $_POST['status'] ?? null;
                    if (!is_string($state) || !in_array($state, ['published', 'draft'], true)) back($page, 'err', 'Status tidak valid.');
                    $vals['status'] = $state;
                }
                if ($id) {
                    $set = implode(',', array_map(function ($k) { return "`$k`=?"; }, array_keys($vals)));
                    if (q("UPDATE `$T` SET $set WHERE id=? AND $where", array_merge(array_values($vals), [$id]))->rowCount() >= 0) audit('update_' . $T, '#' . $id);
                } else {
                    if ($order) $vals['sort_order'] = (int)q_val("SELECT COALESCE(MAX(sort_order),0)+10 FROM `$T`");
                    $cols = implode(',', array_map(function ($k) { return "`$k`"; }, array_keys($vals)));
                    q("INSERT INTO `$T` ($cols) VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ')', array_values($vals));
                    audit('create_' . $T, '#' . db()->lastInsertId());
                }
                back($page, 'ok', 'Tersimpan.');
            } elseif ($act === 'delete' && $id) {
                if ($soft) q("UPDATE `$T` SET deleted_at=? WHERE id=? AND deleted_at IS NULL", [now(), $id]); else q("DELETE FROM `$T` WHERE id=?", [$id]);
                audit('delete_' . $T, '#' . $id); back($page, 'ok', 'Dihapus' . ($soft ? ' (soft delete; dapat dipulihkan via database).' : '.'));
            } elseif ($act === 'toggle' && $id && $status) {
                q("UPDATE `$T` SET status=IF(status='published','draft','published') WHERE id=?", [$id]); audit('toggle_' . $T, '#' . $id); back($page, 'ok', 'Status diubah.');
            } elseif (($act === 'up' || $act === 'down') && $id && $order) {
                $ids = array_map('intval', array_column(q_all("SELECT id FROM `$T` WHERE $where ORDER BY $ob"), 'id'));
                $i = array_search($id, $ids, true);
                if ($i !== false) { $j = $act === 'up' ? $i - 1 : $i + 1; if (isset($ids[$j])) { $x = $ids[$i]; $ids[$i] = $ids[$j]; $ids[$j] = $x; } }
                $u = db()->prepare("UPDATE `$T` SET sort_order=? WHERE id=?"); foreach ($ids as $k => $v) $u->execute([($k + 1) * 10, $v]);
                back($page, 'ok', 'Urutan diperbarui.');
            }
        } catch (PDOException $ex) {
            $m = $ex->getCode() === '23000' ? 'Data duplikat atau masih dipakai data lain.' : 'Operasi database gagal.';
            error_log('[portfolio] crud ' . $T . ': ' . $ex->getMessage()); back($page, 'err', $m);
        }
        back($page);
    }
    $edit = null; $eid = (($_GET['t'] ?? '') === $T) ? (int)($_GET['edit'] ?? 0) : 0;
    if ($eid) $edit = q_one("SELECT * FROM `$T` WHERE id=? AND $where", [$eid]);
    $old = $_SESSION['old'] ?? null; unset($_SESSION['old']);
    $src = $old ?: ($edit ?: ($c['defaults'] ?? []));
    echo '<div class="card"><h2>' . ($edit ? 'Edit' : 'Tambah') . ' ' . e($c['label']) . '</h2><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="save"><input type="hidden" name="_t" value="' . e($T) . '"><input type="hidden" name="id" value="' . (int)($edit['id'] ?? 0) . '">';
    foreach ($fields as $n => $f) echo fld_input($f, $n, $src[$n] ?? '');
    if ($status) echo '<label>Status<select name="status" required><option value="published"' . (($src['status'] ?? 'published') === 'published' ? ' selected' : '') . '>Published</option><option value="draft"' . (($src['status'] ?? '') === 'draft' ? ' selected' : '') . '>Draft</option></select></label>';
    echo '<div class="full"><button class="btn" type="submit" data-confirm="Simpan data ini?">Simpan</button> ' . ($edit ? '<a class="btn sec" href="index.php?p=' . e($page) . '">Batal</a>' : '') . '</div></form></div>';
    $pg = max(1, (int)($_GET['pg'] ?? 1)); $per = 50;
    $total = (int)q_val("SELECT COUNT(*) FROM `$T` WHERE $where");
    $rows = q_all("SELECT * FROM `$T` WHERE $where ORDER BY $ob LIMIT $per OFFSET " . (($pg - 1) * $per));
    echo '<div class="card"><h2>' . e($c['label']) . ' (' . $total . ')</h2><table><tr>';
    foreach ($c['list'] as $col => $lab) echo '<th>' . e($lab) . '</th>';
    echo ($status ? '<th>Status</th>' : '') . '<th>Aksi</th></tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($c['list'] as $col => $lab) { $v = (string)$r[$col]; if (!empty($c['trim'][$col])) $v = mb_strimwidth($v, 0, (int)$c['trim'][$col], '…'); echo '<td>' . e($v) . '</td>'; }
        if ($status) echo '<td><span class="pill ' . ($r['status'] === 'published' ? 'pub' : '') . '">' . e($r['status']) . '</span></td>';
        $table = $T;
        $f = function (string $a, string $label, string $cls = 'sec', string $conf = '') use ($r, $table) { return '<form method="post">' . csrf_field() . '<input type="hidden" name="_t" value="' . e($table) . '"><input type="hidden" name="id" value="' . (int)$r['id'] . '"><input type="hidden" name="action" value="' . e($a) . '"><button class="btn sm ' . e($cls) . '" type="submit"' . ($conf ? ' data-confirm="' . e($conf) . '"' : '') . '>' . e($label) . '</button></form>'; };
        echo '<td><div class="row-actions"><a class="btn sm sec" href="index.php?p=' . e($page) . '&t=' . $T . '&edit=' . (int)$r['id'] . '">Edit</a>';
        if ($order) echo $f('up', '↑') . $f('down', '↓');
        if ($status) echo $f('toggle', $r['status'] === 'published' ? 'Unpublish' : 'Publish', 'sec', 'Ubah status publikasi data ini?');
        echo $f('delete', 'Hapus', 'danger', 'Hapus data ini?') . '</div></td></tr>';
    }
    echo '</table>';
    if ($total > $per) echo '<p class="muted">Halaman ' . $pg . ' · <a href="index.php?p=' . e($page) . '&pg=' . ($pg + 1) . '">berikutnya</a> · <a href="index.php?p=' . e($page) . '&pg=' . max(1, $pg - 1) . '">sebelumnya</a></p>';
    echo '</div>';
}
