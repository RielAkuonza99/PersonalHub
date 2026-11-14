<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/content.php';
send_security_headers(true);
require_admin_api();
json_out(['projects' => (int)q_val('SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL'), 'skills' => (int)q_val('SELECT COUNT(*) FROM skills WHERE deleted_at IS NULL'), 'media' => (int)q_val('SELECT COUNT(*) FROM media WHERE deleted_at IS NULL'), 'messages_new' => (int)q_val("SELECT COUNT(*) FROM messages WHERE status='new' AND deleted_at IS NULL")]);
