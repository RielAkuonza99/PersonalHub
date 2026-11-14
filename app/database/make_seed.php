<?php
// CLI helper (run locally, not on the server): php database/make_seed.php > database/seed.sql
// Generates idempotent INSERT IGNORE statements from data/data.json. Existing rows are never overwritten.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$d = json_decode(file_get_contents(__DIR__ . '/../data/data.json'), true); $s = $d['site'];
$q = function ($v) { return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string)$v) . "'"; };
echo "-- Seed generated from data/data.json. Safe to re-run (INSERT IGNORE).\nSET NAMES utf8mb4;\n";
echo "INSERT IGNORE INTO site_profile (id,logo,title,name,tagline,photo,cv_url,email,about) VALUES (1,{$q($s['logo'])},{$q($s['title'])},{$q($s['name'])},{$q($s['tagline'])},{$q($s['photo'])},{$q($s['cv'])},{$q($s['email'])},{$q($s['about'])});\n";
foreach (['role' => 'roles', 'interest' => 'interests', 'goal' => 'goals'] as $k => $f) foreach ($s[$f] as $i => $t) echo "INSERT IGNORE INTO profile_items (kind,label,value,sort_order) VALUES ('$k',{$q($t)},'',".(($i+1)*10).");\n";
foreach ($s['bio'] as $i => $kv) echo "INSERT IGNORE INTO profile_items (kind,label,value,sort_order) VALUES ('bio',{$q($kv[0])},{$q($kv[1])},".(($i+1)*10).");\n";
foreach ($s['socials'] as $i => $so) echo "INSERT IGNORE INTO socials (provider_id,label,url,sort_order) VALUES ({$q($so['id'])},{$q($so['label'])},{$q($so['url'])},".(($i+1)*10).");\n";
$i = 0; foreach ($d['skillCategories'] as $c) { if ($c[0] === 'All') continue; $i++; echo "INSERT IGNORE INTO skill_categories (key_name,label,sort_order) VALUES ({$q($c[0])},{$q($c[1])},".($i*10).");\n"; }
foreach ($d['skills'] as $i => $k) echo "INSERT IGNORE INTO skills (name,category_id,level,sort_order) SELECT {$q($k['name'])}, id, {$k['level']}, ".(($i+1)*10)." FROM skill_categories WHERE key_name={$q($k['cat'])};\n";
foreach ($d['projects'] as $i => $p) echo "INSERT IGNORE INTO projects (name,type,category,summary,detail,tech,image_url,video_url,github_url,demo_url,sort_order) VALUES ({$q($p['name'])},{$q($p['type'])},{$q($p['cat'])},{$q($p['desc'])},{$q($p['detail'])},{$q(json_encode($p['tech'], JSON_UNESCAPED_UNICODE))},{$q($p['image'])},{$q($p['video'])},{$q($p['github'])},{$q($p['demo'])},".(($i+1)*10).");\n";
foreach ($d['journey'] as $i => $j) echo "INSERT IGNORE INTO journey (year,title,body,sort_order) VALUES ({$q($j['year'])},{$q($j['title'])},{$q($j['text'])},".(($i+1)*10).");\n";
$def = ['seo_title' => 'Riel Akuonza — Informatics Student & Developer', 'seo_description' => 'Personal portfolio of Riel Akuonza: biodata, skills, projects, and learning journey.', 'og_title' => 'Riel Akuonza — Portfolio', 'og_description' => 'Informatics student and developer. Projects, skills, and journey.', 'theme_color' => '#f6f7f5', 'favicon_url' => 'assets/images/favicon.svg', 'google_site_verification' => 'LU7APQYFrLwWOgprYc2R_SQsKXa_kDBpWbjGVyHI-K4'];
foreach ($def as $k => $v) echo "INSERT IGNORE INTO settings (skey,svalue) VALUES ({$q($k)},{$q($v)});\n";
