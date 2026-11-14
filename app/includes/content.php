<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/** TikTok video id from a full video URL (…/@user/video/ID, /embed/v2/ID, /player/v1/ID). Short links are resolved elsewhere. */
function tiktok_video_id(string $url): ?string {
    $url = trim($url);
    if (preg_match('#^https://(?:www\.|m\.)?tiktok\.com/@[A-Za-z0-9_.]{1,64}/video/(\d{10,25})(?:[/?\#].*)?$#i', $url, $m)) return $m[1];
    if (preg_match('#^https://(?:www\.)?tiktok\.com/(?:embed/v2|player/v1|v)/(\d{10,25})(?:[/?\#].*)?$#i', $url, $m)) return $m[1];
    return null;
}
/** Official TikTok Embed Player (https://developers.tiktok.com/doc/embed-player). The video streams from TikTok itself. */
function tiktok_player_url(string $id, bool $autoplay = true): string {
    if (!preg_match('/^\d{10,25}$/D', $id)) return '';
    return 'https://www.tiktok.com/player/v1/' . $id . '?controls=1&loop=1&rel=0&description=1&music_info=1&autoplay=' . ($autoplay ? '1&muted=1' : '0');
}
/** Project video_url that points to a TikTok post becomes an embed; any other https URL is treated as a direct video file. */
function video_embed(string $url): string { $id = tiktok_video_id($url); return $id ? tiktok_player_url($id) : ''; }

function public_asset_url(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== '') return in_array($scheme, ['http', 'https'], true) ? $url : '';
    if (strpos($url, '//') === 0 || strpos($url, '..') !== false) return '';
    $path = '/' . ltrim($url, '/');
    $base = strpos($path, '/assets/uploads/') === 0
        ? (string)cfg('APP_PUBLIC_URL', 'https://app.personalhub.my.id')
        : (string)cfg('PORTFOLIO_URL', 'https://personalhub.my.id');
    return rtrim($base, '/') . $path;
}

/** Builds the public content payload, including published content and current SEO settings. */
function public_payload(): ?array {
    $p = q_one('SELECT * FROM site_profile WHERE id=1');
    if (!$p) return null;
    $items = function (string $kind) {
        return q_all("SELECT label, value FROM profile_items WHERE kind=? AND status='published' AND deleted_at IS NULL ORDER BY sort_order, id", [$kind]);
    };
    $bio = []; foreach ($items('bio') as $r) $bio[] = [$r['label'], $r['value']];
    $list = function (string $k) use ($items) { return array_map(function ($r) { return $r['label']; }, $items($k)); };
    $socials = array_map(function ($r) { return ['id' => $r['provider_id'], 'label' => $r['label'], 'url' => $r['url']]; },
        q_all("SELECT provider_id, label, url FROM socials WHERE status='published' AND deleted_at IS NULL ORDER BY sort_order, id"));
    $cats = [['All', 'All']]; foreach (q_all('SELECT key_name, label FROM skill_categories ORDER BY sort_order, id') as $c) $cats[] = [$c['key_name'], $c['label']];
    $skills = array_map(function ($r) { return ['name' => $r['name'], 'cat' => $r['key_name'], 'level' => (int)$r['level']]; },
        q_all("SELECT s.name, c.key_name, s.level FROM skills s JOIN skill_categories c ON c.id=s.category_id WHERE s.status='published' AND s.deleted_at IS NULL ORDER BY s.sort_order, s.id"));
    $projects = [];
    foreach (q_all("SELECT * FROM projects WHERE status='published' AND deleted_at IS NULL ORDER BY sort_order, id") as $r) {
        $embed = video_embed($r['video_url']);
        $t = json_decode($r['tech'], true);
        $row = ['id' => (int)$r['id'], 'name' => $r['name'], 'type' => $r['type'], 'cat' => $r['category'], 'desc' => $r['summary'], 'detail' => $r['detail'],
            'tech' => is_array($t) ? array_values($t) : [], 'image' => public_asset_url((string)$r['image_url']), 'video' => $embed ? '' : $r['video_url'],
            'imageCrop' => $r['image_crop'] ?? 'center', 'videoCrop' => $r['video_crop'] ?? 'contain',
            'videoStart' => (float)($r['video_start'] ?? 0), 'videoEnd' => (float)($r['video_end'] ?? 0),
            'github' => $r['github_url'], 'demo' => $r['demo_url']];
        if ($embed) $row['videoEmbed'] = $embed;
        $projects[] = $row;
    }
    $videos = array_map(function ($r) { return ['id' => (int)$r['id'], 'videoId' => $r['video_id'], 'title' => $r['title'], 'caption' => $r['caption'],
        'author' => $r['author_name'], 'authorUrl' => $r['author_url'], 'thumb' => $r['thumbnail_url'], 'url' => $r['url']]; },
        tiktok_table_ready() ? q_all("SELECT id, video_id, title, caption, author_name, author_url, thumbnail_url, url FROM tiktok_videos WHERE status='published' AND deleted_at IS NULL ORDER BY sort_order, id") : []);
    $journey = array_map(function ($r) { return ['id' => (int)$r['id'], 'year' => $r['year'], 'title' => $r['title'], 'text' => $r['body'],
        'html' => $r['body_html'] ?: $r['body'], 'slug' => $r['slug'] ?: '', 'cover' => public_asset_url((string)$r['cover_url']),
        'document' => public_asset_url((string)($r['document_url'] ?? '')), 'link' => $r['link_url']]; },
        q_all("SELECT id, year, title, body, body_html, slug, cover_url, document_url, link_url FROM journey WHERE status='published' AND deleted_at IS NULL ORDER BY sort_order, id"));
    return [
        'site' => ['logo' => $p['logo'], 'title' => $p['title'], 'name' => $p['name'], 'roles' => $list('role'), 'tagline' => $p['tagline'],
            'photo' => public_asset_url((string)$p['photo']), 'cv' => (string)(gdrive_normalize((string)$p['cv_url']) ?? ''), 'email' => $p['email'], 'about' => $p['about'],
            'bio' => $bio, 'interests' => $list('interest'), 'goals' => $list('goal'), 'socials' => $socials],
        'meta' => [
            'title' => setting('seo_title', 'Riel Akuonza — Informatics Student & Developer'),
            'description' => setting('seo_description', 'Personal portfolio of Riel Akuonza: biodata, skills, projects, and learning journey.'),
            'og_title' => setting('og_title', 'Riel Akuonza — Portfolio'),
            'og_description' => setting('og_description', 'Informatics student and developer. Projects, skills, and journey.'),
            'theme_color' => setting('theme_color', '#f6f7f5'),
            'favicon' => public_asset_url(setting('favicon_url', 'assets/images/favicon.svg')),
            'site_url' => setting('site_url', (string)cfg('PORTFOLIO_URL', 'https://personalhub.my.id')),
            'alt_names' => array_values(array_filter(array_map('trim', explode(',', setting('alt_names', 'Riel, Akuonza, Riel Akuonza Reborn'))))),
            'og_image' => public_asset_url(setting('og_image_url', '')),
            'gsv' => setting('google_site_verification', 'LU7APQYFrLwWOgprYc2R_SQsKXa_kDBpWbjGVyHI-K4'),
        ],
        'skillCategories' => $cats, 'skills' => $skills, 'projects' => $projects, 'videos' => $videos, 'journey' => $journey,
    ];
}

/** False until database/upgrade_v3_otp_tiktok.sql has been applied, so the public API keeps working during the upgrade. */
function tiktok_table_ready(): bool {
    static $ok = null;
    if ($ok === null) { try { q('SELECT 1 FROM tiktok_videos LIMIT 1'); $ok = true; } catch (Throwable $e) { $ok = false; } }
    return $ok;
}

function setting(string $key, string $default = ''): string {
    static $all = null;
    if ($all === null) { $all = []; try { foreach (q_all('SELECT skey, svalue FROM settings') as $r) $all[$r['skey']] = $r['svalue']; } catch (Throwable $e) { $all = []; } }
    return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
}
function set_setting(string $key, string $value): void {
    q('INSERT INTO settings (skey, svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)', [$key, $value]);
}

/** Idempotent import from data/data.json. Existing rows are NOT overwritten (INSERT IGNORE) so dashboard edits survive re-runs. */
function import_data_json(string $path): array {
    $d = json_decode((string)@file_get_contents($path), true);
    if (!is_array($d) || !isset($d['site'])) throw new RuntimeException('data.json unreadable');
    $s = $d['site']; $rep = []; $pdo = db();
    $pdo->beginTransaction();
    try {
        $n = q('INSERT IGNORE INTO site_profile (id, logo, title, name, tagline, photo, cv_url, email, about) VALUES (1,?,?,?,?,?,?,?,?)',
            [$s['logo'], $s['title'], $s['name'], $s['tagline'], $s['photo'], $s['cv'], $s['email'], $s['about']])->rowCount();
        $rep['profile'] = $n;
        $ins = db()->prepare('INSERT IGNORE INTO profile_items (kind,label,value,sort_order) VALUES (?,?,?,?)');
        $c = 0;
        foreach (['role' => 'roles', 'interest' => 'interests', 'goal' => 'goals'] as $k => $f) foreach ($s[$f] as $i => $t) { $ins->execute([$k, $t, '', ($i + 1) * 10]); $c += $ins->rowCount(); }
        foreach ($s['bio'] as $i => $kv) { $ins->execute(['bio', $kv[0], $kv[1], ($i + 1) * 10]); $c += $ins->rowCount(); }
        $rep['profile_items'] = $c;
        $c = 0; $i = 0;
        foreach ($s['socials'] as $so) { $i++; $c += q('INSERT IGNORE INTO socials (provider_id,label,url,sort_order) VALUES (?,?,?,?)', [$so['id'], $so['label'], $so['url'], $i * 10])->rowCount(); }
        $rep['socials'] = $c;
        $c = 0; $i = 0;
        foreach ($d['skillCategories'] as $cat) { if ($cat[0] === 'All') continue; $i++; $c += q('INSERT IGNORE INTO skill_categories (key_name,label,sort_order) VALUES (?,?,?)', [$cat[0], $cat[1], $i * 10])->rowCount(); }
        $rep['skill_categories'] = $c;
        $c = 0; $i = 0;
        foreach ($d['skills'] as $sk) {
            $i++; $cid = q_val('SELECT id FROM skill_categories WHERE key_name=?', [$sk['cat']]);
            if (!$cid) throw new RuntimeException('Unknown skill category ' . $sk['cat']);
            $c += q('INSERT IGNORE INTO skills (name,category_id,level,sort_order) VALUES (?,?,?,?)', [$sk['name'], $cid, (int)$sk['level'], $i * 10])->rowCount();
        }
        $rep['skills'] = $c;
        $c = 0; $i = 0;
        foreach ($d['projects'] as $p) {
            $i++;
            $c += q('INSERT IGNORE INTO projects (name,type,category,summary,detail,tech,image_url,video_url,github_url,demo_url,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$p['name'], $p['type'], $p['cat'], $p['desc'], $p['detail'], json_encode($p['tech'], JSON_UNESCAPED_UNICODE), $p['image'], $p['video'], $p['github'], $p['demo'], $i * 10])->rowCount();
        }
        $rep['projects'] = $c;
        $c = 0; $i = 0;
        foreach ($d['journey'] as $j) { $i++; $c += q('INSERT IGNORE INTO journey (year,title,body,sort_order) VALUES (?,?,?,?)', [$j['year'], $j['title'], $j['text'], $i * 10])->rowCount(); }
        $rep['journey'] = $c;
        $def = ['seo_title' => 'Riel Akuonza — Informatics Student & Developer', 'seo_description' => 'Personal portfolio of Riel Akuonza: biodata, skills, projects, and learning journey.',
            'og_title' => 'Riel Akuonza — Portfolio', 'og_description' => 'Informatics student and developer. Projects, skills, and journey.', 'theme_color' => '#f6f7f5',
            'favicon_url' => 'assets/images/favicon.svg', 'google_site_verification' => 'LU7APQYFrLwWOgprYc2R_SQsKXa_kDBpWbjGVyHI-K4'];
        $c = 0; foreach ($def as $k => $v) $c += q('INSERT IGNORE INTO settings (skey,svalue) VALUES (?,?)', [$k, $v])->rowCount();
        $rep['settings'] = $c;
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    return $rep;
}

/** Compares DB-derived payload with data.json. Returns list of human-readable differences (empty = identical). */
function verify_against_json(string $path): array {
    $d = json_decode((string)@file_get_contents($path), true); $db = public_payload(); $diff = [];
    if (!$d) return ['data.json unreadable']; if (!$db) return ['database has no profile row'];
    foreach (['skills', 'projects', 'journey'] as $k) if (count($d[$k]) !== count($db[$k])) $diff[] = "$k: json=" . count($d[$k]) . " db=" . count($db[$k]);
    foreach (['roles', 'interests', 'goals', 'bio', 'socials'] as $k) if (count($d['site'][$k]) !== count($db['site'][$k])) $diff[] = "site.$k count differs";
    foreach ($d['site'] as $k => $v) if (!is_array($v) && ($db['site'][$k] ?? null) !== $v) $diff[] = "site.$k differs";
    foreach (['site.roles', 'site.interests', 'site.goals', 'site.bio', 'site.socials'] as $dot) { list(, $k) = explode('.', $dot); if ($d['site'][$k] != $db['site'][$k]) $diff[] = "$dot content differs"; }
    foreach ($d['skills'] as $i => $s) if (($db['skills'][$i] ?? null) != $s) $diff[] = 'skill #' . ($i + 1) . ' differs';
    foreach ($d['journey'] as $i => $s) if (($db['journey'][$i] ?? null) != $s) $diff[] = 'journey #' . ($i + 1) . ' differs';
    foreach ($d['projects'] as $i => $p) { $x = $db['projects'][$i] ?? []; unset($x['id'], $x['videoEmbed']); $y = $p; unset($y['id']); if ($x != $y) $diff[] = 'project #' . ($i + 1) . ' differs'; }
    if ($d['skillCategories'] != $db['skillCategories']) $diff[] = 'skillCategories differ';
    return $diff;
}
