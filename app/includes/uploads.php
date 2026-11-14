<?php
declare(strict_types=1);

function store_content_upload(array $file, string $kind): string {
    $allowed = $kind === 'image'
        ? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp']
        : ($kind === 'video'
            ? ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv']
            : ['application/pdf' => 'pdf']);
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal.');
    $tmp = $file['tmp_name'] ?? null;
    if (!is_string($tmp) || !is_file($tmp) || (!is_uploaded_file($tmp) && !defined('TEST_ALLOW_LOCAL_UPLOAD'))) throw new RuntimeException('File upload tidak valid.');
    $maxMb = $kind === 'image' ? 8 : ($kind === 'video' ? 100 : 20);
    $size = (int)filesize($tmp);
    if ($size < 1 || $size > $maxMb * 1048576) throw new RuntimeException('Ukuran file harus antara 1 byte dan ' . $maxMb . ' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset($allowed[$mime])) throw new RuntimeException('Tipe file tidak didukung untuk kolom ini.');
    if ($kind === 'image' && @getimagesize($tmp) === false) throw new RuntimeException('Berkas gambar rusak atau tidak dikenali.');
    $dir = APP_ROOT . '/assets/uploads/' . ($kind === 'image' ? 'images' : ($kind === 'video' ? 'videos' : 'files'));
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, $dir . '/' . $name) && !(@copy($tmp, $dir . '/' . $name))) throw new RuntimeException('File tidak dapat disimpan.');
    return 'assets/uploads/' . ($kind === 'image' ? 'images' : ($kind === 'video' ? 'videos' : 'files')) . '/' . $name;
}
