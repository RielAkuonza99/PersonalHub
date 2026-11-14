<?php
declare(strict_types=1);
require_once __DIR__ . '/../http.php';

const IMG_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
function imgbb_configured(): bool { return cfg('IMGBB_API_KEY') !== ''; }

/** Validates an uploaded file by content (finfo + getimagesize), uploads via backend to ImgBB, returns media row id. Throws RuntimeException(user-safe message). */
function imgbb_upload(array $file, string $alt = ''): int {
    if (!imgbb_configured()) throw new RuntimeException('IMGBB_API_KEY belum dikonfigurasi di server.');
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal (kode ' . (int)($file['error'] ?? -1) . ').');
    $tmp = $file['tmp_name'] ?? null; $name = $file['name'] ?? null;
    if (!is_string($tmp) || $tmp === '' || !is_file($tmp) || (!is_uploaded_file($tmp) && !defined('TEST_ALLOW_LOCAL_UPLOAD')) || !is_string($name)) throw new RuntimeException('File tidak valid.');
    $size = (int)filesize($tmp);
    if ($size <= 0 || $size > max_upload_bytes('MAX_IMAGE_MB', 5)) throw new RuntimeException('Ukuran gambar harus 1 byte sampai ' . cfg('MAX_IMAGE_MB', '5') . ' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset(IMG_MIMES[$mime]) || @getimagesize($tmp) === false) throw new RuntimeException('Hanya JPEG, PNG, GIF, atau WEBP yang diizinkan.');
    $orig = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name)); $orig = mb_substr($orig, 0, 120);
    $internal = bin2hex(random_bytes(8)) . '.' . IMG_MIMES[$mime];           // safe internal name sent to provider
    $cf = new CURLFile($tmp, $mime, $internal);
    $r = http_request('POST', 'https://api.imgbb.com/1/upload?key=' . rawurlencode((string)cfg('IMGBB_API_KEY')), ['Accept: application/json'], ['image' => $cf, 'name' => pathinfo($internal, PATHINFO_FILENAME)], 40);
    if ($r['error'] !== '') throw new RuntimeException('ImgBB tidak dapat dihubungi (jaringan/timeout).');
    $j = json_decode($r['body'], true);
    if ($r['status'] !== 200 || !is_array($j) || empty($j['success']) || empty($j['data']['url'])) {
        $msg = is_array($j) && isset($j['error']['message']) ? mb_substr((string)$j['error']['message'], 0, 120) : 'respons tidak valid';
        throw new RuntimeException('ImgBB menolak upload: ' . $msg . ' (HTTP ' . $r['status'] . ').');
    }
    $d = $j['data'];
    $url = v_url($d['url'], ['https']); if (!$url) throw new RuntimeException('ImgBB mengembalikan URL tidak valid.');
    $del = isset($d['delete_url']) ? v_url($d['delete_url'], ['https']) : null;
    q('INSERT INTO media (provider,kind,url,provider_ref,delete_url_enc,filename,mime_type,size_bytes,alt_text) VALUES (?,?,?,?,?,?,?,?,?)',
        ['imgbb', 'image', $url, mb_substr((string)($d['id'] ?? ''), 0, 190), $del ? enc($del) : null, $orig, $mime, $size, mb_substr($alt, 0, 255)]);
    return (int)db()->lastInsertId();
}
