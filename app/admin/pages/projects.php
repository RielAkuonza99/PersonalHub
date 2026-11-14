<?php
$act = post_action();
if ($act !== null && !in_array($act, ['save_project', 'delete_project'], true)) back('projects', 'err', 'Aksi tidak valid.');
if ($act === 'delete_project') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) back('projects', 'err', 'ID proyek tidak valid.');
    q('UPDATE projects SET deleted_at=? WHERE id=? AND deleted_at IS NULL', [now(), $id]); audit('delete_project', '#' . $id); back('projects', 'ok', 'Proyek dihapus.');
}
if ($act === 'save_project') {
    $id = filter_var($_POST['id'] ?? '0', FILTER_VALIDATE_INT);
    if ($id === false || $id < 0) back('projects', 'err', 'ID proyek tidak valid.');
    $name = v_str($_POST['name'] ?? '', 1, 150); $category = v_str($_POST['category'] ?? '', 1, 60);
    $summary = v_str($_POST['summary'] ?? '', 1, 500); $detail = v_str($_POST['detail'] ?? '', 0, 2000);
    $type = $_POST['type'] ?? 'web'; $status = $_POST['status'] ?? 'draft';
    if (!$name || !$category || !$summary || !in_array($type, ['web','design','app'], true) || !in_array($status, ['draft','published'], true)) back('projects', 'err', 'Nama, kategori, deskripsi, tipe, atau status tidak valid.');
    $tech = v_str($_POST['tech'] ?? '', 0, 1300); $tags = [];
    if ($tech !== null && $tech !== '') foreach (explode(',', $tech) as $tag) { $tag = v_str($tag, 1, 60); if ($tag) $tags[] = $tag; if (count($tags) >= 20) break; }
    $github = v_url($_POST['github_url'] ?? '', ['http','https'], true); $demo = v_url($_POST['demo_url'] ?? '', ['http','https'], true);
    if (($github === null && ($_POST['github_url'] ?? '') !== '') || ($demo === null && ($_POST['demo_url'] ?? '') !== '')) back('projects', 'err', 'Link repository atau demo tidak valid.');
    $existing = $id ? q_one('SELECT image_url, video_url, video_start, video_end, image_crop, video_crop FROM projects WHERE id=? AND deleted_at IS NULL', [$id]) : null;
    $image = $existing['image_url'] ?? ''; $video = $existing['video_url'] ?? '';
    try {
        if (!empty($_FILES['image_file']['name'])) $image = store_content_upload($_FILES['image_file'], 'image');
        if (!empty($_FILES['video_file']['name'])) $video = store_content_upload($_FILES['video_file'], 'video');
    } catch (RuntimeException $ex) { back('projects', 'err', $ex->getMessage()); }
    $videoUrl = v_url($_POST['video_url'] ?? '', ['https'], true);
    if (($videoUrl === null && ($_POST['video_url'] ?? '') !== '') && empty($_FILES['video_file']['name'])) back('projects', 'err', 'URL video tidak valid.');
    if (($videoUrl ?: '') !== '' && empty($_FILES['video_file']['name'])) $video = $videoUrl;
    $imageCrop = in_array($_POST['image_crop'] ?? '', ['center','top','bottom'], true) ? $_POST['image_crop'] : 'center';
    $videoCrop = in_array($_POST['video_crop'] ?? '', ['contain','cover','top','bottom'], true) ? $_POST['video_crop'] : 'contain';
    $videoStart = filter_var($_POST['video_start'] ?? '0', FILTER_VALIDATE_FLOAT); $videoEnd = filter_var($_POST['video_end'] ?? '0', FILTER_VALIDATE_FLOAT);
    if ($videoStart === false || $videoStart < 0 || $videoEnd === false || $videoEnd < 0) back('projects', 'err', 'Durasi potong video tidak valid.');
    $videoStart = (float)$videoStart; $videoEnd = (float)$videoEnd; if ($videoEnd > 0 && $videoEnd <= $videoStart) back('projects', 'err', 'Durasi akhir harus lebih besar dari awal.');
    $vals = [$name,$type,$category,$summary,$detail,json_encode($tags, JSON_UNESCAPED_UNICODE),$image,$video,$github ?: '',$demo ?: '',$status,$imageCrop,$videoStart,$videoEnd,$videoCrop];
    if ($id) {
        q('UPDATE projects SET name=?,type=?,category=?,summary=?,detail=?,tech=?,image_url=?,video_url=?,github_url=?,demo_url=?,status=?,image_crop=?,video_start=?,video_end=?,video_crop=?,updated_at=? WHERE id=? AND deleted_at IS NULL', array_merge($vals,[now(),$id])); audit('update_project', '#' . $id);
    } else {
        $sort = (int)q_val('SELECT COALESCE(MAX(sort_order),0)+10 FROM projects WHERE deleted_at IS NULL');
        q('INSERT INTO projects (name,type,category,summary,detail,tech,image_url,video_url,github_url,demo_url,status,image_crop,video_start,video_end,video_crop,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals,[$sort])); audit('create_project', '#' . db()->lastInsertId());
    }
    back('projects','ok','Proyek tersimpan.');
}
layout_head('Projects', 'projects');
$editId = filter_var($_GET['edit'] ?? '0', FILTER_VALIDATE_INT); $edit = ($editId && $editId > 0) ? q_one('SELECT * FROM projects WHERE id=? AND deleted_at IS NULL', [$editId]) : null;
$src = $edit ?: ['name'=>'','type'=>'web','category'=>'','summary'=>'','detail'=>'','tech'=>'[]','image_url'=>'','video_url'=>'','github_url'=>'','demo_url'=>'','status'=>'draft','image_crop'=>'center','video_crop'=>'contain','video_start'=>0,'video_end'=>0];
$tech = is_string($src['tech']) && json_decode($src['tech'],true) ? implode(', ', json_decode($src['tech'],true)) : '';
echo '<div class="card"><div class="section-heading"><div><p class="muted">Konten proyek</p><h2>' . ($edit ? 'Edit proyek' : 'Tambah proyek') . '</h2></div></div><form method="post" enctype="multipart/form-data" class="fields">'.csrf_field().'<input type="hidden" name="action" value="save_project"><input type="hidden" name="id" value="'.(int)($src['id'] ?? 0).'">';
echo '<label>Nama proyek<input name="name" required maxlength="150" value="'.e($src['name']).'"></label><label>Kategori<input name="category" required maxlength="60" value="'.e($src['category']).'"></label><label>Tipe<select name="type" required><option value="web"'.($src['type']==='web'?' selected':'').'>Web 16:9</option><option value="design"'.($src['type']==='design'?' selected':'').'>Design 4:5</option><option value="app"'.($src['type']==='app'?' selected':'').'>Aplikasi 9:16</option></select></label><label>Status<select name="status" required><option value="draft"'.($src['status']==='draft'?' selected':'').'>Draft</option><option value="published"'.($src['status']==='published'?' selected':'').'>Published</option></select></label>'; 
echo '<label class="full">Teknologi <span class="muted">Pisahkan dengan koma</span><input name="tech" maxlength="1300" value="'.e($tech).'" placeholder="PHP, JavaScript, MySQL"></label><label class="full">Deskripsi singkat<textarea name="summary" required maxlength="500" rows="3">'.e($src['summary']).'</textarea></label><label class="full">Detail<textarea name="detail" maxlength="2000" rows="5">'.e($src['detail']).'</textarea></label>'; 
echo '<label>Upload gambar<input type="file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp"><img id="project-image-preview" class="upload-preview" alt="Pratinjau gambar" hidden></label><label>Posisi gambar<select id="project-image-crop" name="image_crop"><option value="center"'.($src['image_crop']==='center'?' selected':'').'>Tengah</option><option value="top"'.($src['image_crop']==='top'?' selected':'').'>Atas</option><option value="bottom"'.($src['image_crop']==='bottom'?' selected':'').'>Bawah</option></select></label><label>Upload video<input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg"><video id="project-video-preview" class="upload-preview" controls muted hidden></video><small id="project-video-info" class="muted"></small></label><label>Atau URL video (TikTok / file)<input type="url" name="video_url" maxlength="1000" value="'.e($src['video_url']).'" placeholder="https://..."></label>'; 
echo '<label>Mulai video (detik)<input type="number" name="video_start" min="0" step="0.1" value="'.e($src['video_start']).'"></label><label>Durasi akhir (detik)<input type="number" name="video_end" min="0" step="0.1" value="'.e($src['video_end']).'"></label><label>Mode video<select name="video_crop"><option value="contain"'.($src['video_crop']==='contain'?' selected':'').'>Utuh</option><option value="cover"'.($src['video_crop']==='cover'?' selected':'').'>Isi frame</option><option value="top"'.($src['video_crop']==='top'?' selected':'').'>Potong atas</option><option value="bottom"'.($src['video_crop']==='bottom'?' selected':'').'>Potong bawah</option></select></label><label>Link repository<input type="url" name="github_url" maxlength="1000" value="'.e($src['github_url']).'"></label><label>Link demo<input type="url" name="demo_url" maxlength="1000" value="'.e($src['demo_url']).'"></label>'; 
echo '<div class="full editor-actions"><button class="btn" type="submit" data-confirm="Simpan proyek ini?">Simpan proyek</button>'.($edit?' <a class="btn sec" href="index.php?p=projects">Batal edit</a>':'').'</div></form></div>';
echo '<div class="card"><div class="section-heading"><div><p class="muted">Daftar proyek</p><h2>Proyek</h2></div><form method="get" class="content-search">'.csrf_field().'<input type="hidden" name="p" value="projects"><label class="sr-only">Cari proyek<input type="search" name="q" maxlength="100" value="'.e($_GET['q'] ?? '').'" placeholder="Cari nama atau kategori"></label><button class="btn sec" type="submit">Cari</button></form></div><div class="content-table">';
$q = v_str($_GET['q'] ?? '',0,100); $w='deleted_at IS NULL'; $par=[]; if ($q) { $w .= ' AND (name LIKE ? OR category LIKE ?)'; $like='%'.str_replace(['%','_'],['\\%','\\_'],$q).'%'; $par=[$like,$like]; }
$rows=q_all('SELECT id,name,type,category,status,image_url FROM projects WHERE '.$w.' ORDER BY sort_order,id DESC',$par);
if (!$rows) echo '<div class="empty-state"><strong>Belum ada proyek</strong><p>Tambahkan proyek pertama dengan upload gambar atau video langsung.</p></div>'; else { echo '<table><thead><tr><th>Nama</th><th>Media</th><th>Status</th><th>Aksi</th></tr></thead><tbody>'; foreach($rows as $r){ echo '<tr><td><strong>'.e($r['name']).'</strong><br><span class="muted">'.e($r['category']).'</span></td><td>'.($r['image_url']?'<img class="thumb" src="'.e($r['image_url']).'" alt="" width="90" height="60">':'<span class="muted">Belum ada gambar</span>').'</td><td><span class="pill '.($r['status']==='published'?'pub':'').'">'.e($r['status']).'</span></td><td><div class="row-actions"><a class="btn sm sec" href="index.php?p=projects&edit='.(int)$r['id'].'">Edit</a><form method="post">'.csrf_field().'<input type="hidden" name="action" value="delete_project"><input type="hidden" name="id" value="'.(int)$r['id'].'"><button class="btn sm danger" type="submit" data-confirm="Hapus proyek ini?">Hapus</button></form></div></td></tr>'; } echo '</tbody></table>'; }
echo '</div></div>'; layout_foot();
