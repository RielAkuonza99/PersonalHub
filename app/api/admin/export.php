<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/content.php';
send_security_headers(true);
require_admin_api();
$out = ['exported_at' => gmdate('c'), 'note' => 'Content only. Excludes admins, login OTPs, auth attempts, delete URLs, IP hashes.'];
foreach (['site_profile', 'profile_items', 'skill_categories', 'skills', 'projects', 'journey', 'socials', 'settings'] as $t) $out[$t] = q_all("SELECT * FROM `$t`");
if (tiktok_table_ready()) $out['tiktok_videos'] = q_all('SELECT * FROM tiktok_videos');
$out['messages'] = q_all('SELECT id,name,email,body,status,deleted_at,created_at FROM messages');
$out['media'] = q_all('SELECT id,provider,kind,url,provider_ref,filename,mime_type,size_bytes,alt_text,deleted_at,created_at FROM media');
audit('export_backup');
header('Content-Disposition: attachment; filename="portfolio-backup-' . gmdate('Ymd-His') . '.json"');
json_out($out);
