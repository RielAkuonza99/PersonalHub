<?php
declare(strict_types=1);
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../content.php';
// TikTok integration = official oEmbed API (metadata) + Embed Player (playback straight from TikTok).
// Docs: https://developers.tiktok.com/doc/embed-videos and https://developers.tiktok.com/doc/embed-player
// No API key is needed. Listing a creator's videos automatically would need the TikTok Display API (OAuth app review) and is not used here.

const TIKTOK_SAMPLE_URL = 'https://www.tiktok.com/@scout2015/video/6718335390845095173'; // example from TikTok's own docs

function tiktok_host_ok(string $host): bool {
    $host = strtolower($host);
    return $host === 'tiktok.com' || (strlen($host) > 11 && substr($host, -11) === '.tiktok.com' && preg_match('/^[a-z0-9-]+\.tiktok\.com$/', $host) === 1);
}
function tiktok_canonical(string $user, string $id): string { return 'https://www.tiktok.com/@' . $user . '/video/' . $id; }

/** Follows tiktok.com short links (vm./vt./tiktok.com/t/) manually, only through TikTok hosts over HTTPS. Returns the final URL or null. */
function tiktok_resolve_short(string $url): ?string {
    if (!function_exists('curl_init')) return null;
    for ($hop = 0; $hop < 4; $hop++) {
        $p = parse_url($url);
        if (!$p || strtolower((string)($p['scheme'] ?? '')) !== 'https' || empty($p['host']) || !tiktok_host_ok((string)$p['host'])) return null;
        if (tiktok_video_id($url) !== null || preg_match('#/@[A-Za-z0-9_.]{1,64}/video/\d{10,25}#', (string)($p['path'] ?? ''))) return $url;
        $loc = ''; $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PersonalHubBot/1.0)',
            CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$loc) { if (stripos($h, 'location:') === 0) $loc = trim(substr($h, 9)); return strlen($h); },
            CURLOPT_WRITEFUNCTION => function ($c, $d) { return 0; }]); // headers are enough; abort the body
        curl_exec($ch); $st = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
        if ($st < 300 || $st > 399 || $loc === '') return null;
        if (strpos($loc, 'http') !== 0) { $loc = 'https://' . $p['host'] . '/' . ltrim($loc, '/'); }
        $url = $loc;
    }
    return null;
}

/** Accepts any TikTok video link the owner pastes. Returns ['id','user','canonical'] or throws a user-facing RuntimeException. */
function tiktok_parse(string $input): array {
    $input = trim($input);
    $bad = 'Tautan TikTok tidak valid. Contoh: https://www.tiktok.com/@akunmu/video/1234567890123456789';
    if ($input === '' || strlen($input) > 500 || preg_match('/[\s<>"\']/', $input)) throw new RuntimeException($bad);
    if (!preg_match('#^https?://#i', $input)) $input = 'https://' . $input;
    $input = preg_replace('#^http://#i', 'https://', $input);
    $p = parse_url($input);
    if (!$p || empty($p['host']) || !tiktok_host_ok((string)$p['host'])) throw new RuntimeException('Hanya tautan tiktok.com yang diterima.');
    if (preg_match('#/photo/\d+#', (string)($p['path'] ?? ''))) throw new RuntimeException('Postingan foto TikTok belum didukung. Gunakan tautan video.');
    $isShort = in_array(strtolower((string)$p['host']), ['vm.tiktok.com', 'vt.tiktok.com'], true) || strpos((string)($p['path'] ?? ''), '/t/') === 0;
    if ($isShort) {
        $full = tiktok_resolve_short($input);
        if (!$full) throw new RuntimeException('Tautan pendek tidak dapat dibuka. Salin tautan lengkap (tiktok.com/@akun/video/…) dari browser.');
        $input = $full;
    }
    $id = tiktok_video_id($input);
    if (!$id) throw new RuntimeException($bad);
    $user = preg_match('#/@([A-Za-z0-9_.]{1,64})/video/#', $input, $m) ? $m[1] : '';
    return ['id' => $id, 'user' => $user, 'canonical' => $user !== '' ? tiktok_canonical($user, $id) : 'https://www.tiktok.com/player/v1/' . $id];
}

/** oEmbed lookup. Returns metadata array, or null when TikTok could not be reached (caller stores the video as unverified). */
function tiktok_oembed(string $canonicalUrl, string $expectId): ?array {
    $r = http_request('GET', 'https://www.tiktok.com/oembed?url=' . rawurlencode($canonicalUrl), ['Accept: application/json'], null, 10);
    if ($r['error'] !== '') return null;
    if (in_array($r['status'], [400, 404], true)) throw new RuntimeException('Video tidak ditemukan di TikTok (dihapus, privat, atau tautan salah).');
    if ($r['status'] !== 200) return null;
    $j = json_decode($r['body'], true);
    if (!is_array($j) || ($j['embed_type'] ?? 'video') !== 'video') throw new RuntimeException('Konten ini bukan video TikTok yang dapat diputar.');
    if (!empty($j['embed_product_id']) && (string)$j['embed_product_id'] !== $expectId) throw new RuntimeException('ID video tidak cocok dengan respons TikTok.');
    return [
        'title' => mb_substr(trim((string)($j['title'] ?? '')), 0, 500),
        'author_name' => mb_substr(trim((string)($j['author_name'] ?? '')), 0, 120),
        'author_url' => (string)(v_url($j['author_url'] ?? '', ['https']) ?? ''),
        'thumbnail_url' => (string)(v_url($j['thumbnail_url'] ?? '', ['https']) ?? ''),
    ];
}

/** Adds (or restores) a video. Returns ['id','verified']. */
function tiktok_add(string $input, string $caption = '', string $status = 'published'): array {
    $t = tiktok_parse($input);
    if (!in_array($status, ['draft', 'published'], true)) $status = 'published';
    $existing = q_one('SELECT id, deleted_at FROM tiktok_videos WHERE video_id=?', [$t['id']]);
    if ($existing && $existing['deleted_at'] === null) throw new RuntimeException('Video ini sudah pernah diposting.');
    $meta = null;
    if (cfg_bool('TIKTOK_VALIDATE', true) && $t['user'] !== '') $meta = tiktok_oembed($t['canonical'], $t['id']);
    $m = $meta ?? ['title' => '', 'author_name' => $t['user'], 'author_url' => $t['user'] !== '' ? 'https://www.tiktok.com/@' . $t['user'] : '', 'thumbnail_url' => ''];
    $cap = mb_substr($caption, 0, 255); $verified = $meta ? 1 : 0;
    if ($existing) {
        q('UPDATE tiktok_videos SET url=?, author_name=?, author_url=?, title=?, caption=?, thumbnail_url=?, verified=?, status=?, deleted_at=NULL, sort_order=(SELECT m FROM (SELECT COALESCE(MAX(sort_order),0)+10 AS m FROM tiktok_videos) x) WHERE id=?',
            [$t['canonical'], $m['author_name'], $m['author_url'], $m['title'], $cap, $m['thumbnail_url'], $verified, $status, $existing['id']]);
        return ['id' => (int)$existing['id'], 'verified' => $verified === 1];
    }
    $sort = (int)q_val('SELECT COALESCE(MAX(sort_order),0)+10 FROM tiktok_videos');
    q('INSERT INTO tiktok_videos (video_id,url,author_name,author_url,title,caption,thumbnail_url,verified,sort_order,status) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$t['id'], $t['canonical'], $m['author_name'], $m['author_url'], $m['title'], $cap, $m['thumbnail_url'], $verified, $sort, $status]);
    return ['id' => (int)db()->lastInsertId(), 'verified' => $verified === 1];
}

/** Re-reads title/author/thumbnail from TikTok (thumbnail links expire after a while). */
function tiktok_refresh(int $id): bool {
    $row = q_one('SELECT video_id, url FROM tiktok_videos WHERE id=? AND deleted_at IS NULL', [$id]);
    if (!$row) throw new RuntimeException('Video tidak ditemukan.');
    if (strpos((string)$row['url'], '/@') === false) return false; // added from an embed link: no author in the URL, oEmbed cannot look it up
    $meta = tiktok_oembed((string)$row['url'], (string)$row['video_id']);
    if (!$meta) return false;
    q('UPDATE tiktok_videos SET author_name=?, author_url=?, title=?, thumbnail_url=?, verified=1 WHERE id=?', [$meta['author_name'], $meta['author_url'], $meta['title'], $meta['thumbnail_url'], $id]);
    return true;
}

function tiktok_test(): array {
    $r = http_request('GET', 'https://www.tiktok.com/oembed?url=' . rawurlencode(TIKTOK_SAMPLE_URL), ['Accept: application/json'], null, 10);
    if ($r['error'] !== '') return ['failed', 'Jaringan: ' . mb_substr($r['error'], 0, 120)];
    return $r['status'] === 200 ? ['success', 'oEmbed TikTok dapat dijangkau; pemutar via tiktok.com/player/v1'] : ['failed', 'HTTP ' . $r['status']];
}
