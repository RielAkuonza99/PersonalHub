<?php
declare(strict_types=1);

/** Tiny inline icon set (24x24, stroke). Output is static markup, so it is safe under the strict CSP. */
function icon(string $name): string {
    $paths = [
        'home'  => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'video' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9 5 3-5 3z"/>',
        'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'menu'  => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? '') . '</svg>';
}

/** Shared shell for login, OTP, setup and logout screens. Mobile: brand strip on top; wide screens: two columns. */
function auth_head(string $title, string $kicker = 'Admin access', string $lead = ''): void {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="color-scheme" content="light dark"><title>' . e($title) . ' · Personal HUB</title><link rel="stylesheet" href="assets/admin.css"></head><body class="auth-page">';
    echo '<main class="auth-shell"><aside class="auth-brand"><a class="auth-logo" href="../" aria-label="Kembali ke website"><span class="auth-logo-mark" aria-hidden="true">PH</span><span>Personal HUB</span></a>'
        . '<div class="auth-brand-copy"><p>Dasbor administrasi</p><h2>Kelola portfolio dan konten dari satu tempat.</h2></div>'
        . '<div class="auth-trust"><span class="auth-trust-line" aria-hidden="true"></span><span>Login dua langkah: password + kode OTP email</span></div></aside>'
        . '<section class="auth-panel"><a class="auth-back" href="../">Kembali ke website</a><header><p class="auth-kicker">' . e($kicker) . '</p><h1>' . e($title) . '</h1>' . ($lead !== '' ? '<p class="muted">' . e($lead) . '</p>' : '') . '</header>';
}
function auth_foot(): void { echo '</section></main><script src="assets/admin.js"></script></body></html>'; }

function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function flash_show(): void { if (!empty($_SESSION['flash'])) { list($t, $m) = $_SESSION['flash']; unset($_SESSION['flash']); echo '<p class="msg ' . ($t === 'ok' ? 'ok' : 'err') . '" role="' . ($t === 'ok' ? 'status' : 'alert') . '">' . e($m) . '</p>'; } }

function layout_head(string $title, string $page): void {
    $groups = [
        'Ringkasan' => ['overview' => 'Overview'],
        'Konten' => ['profile' => 'Profile', 'about' => 'About', 'skills' => 'Skills', 'projects' => 'Projects', 'tiktok' => 'Video TikTok', 'journey' => 'Journey', 'contact' => 'Contact'],
        'Analitik & Sistem' => ['settings' => 'Settings', 'security' => 'Security', 'tools' => 'Tools'],
    ];
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="color-scheme" content="light dark"><title>' . e($title) . ' · Dashboard</title><link rel="stylesheet" href="assets/admin.css"></head><body class="app">';
    // Top bar: only visible on small screens.
    echo '<header class="topbar"><button class="icon-btn" type="button" data-drawer-toggle aria-controls="drawer" aria-expanded="false" aria-label="Buka menu">' . icon('menu') . '</button><strong class="topbar-title">' . e($title) . '</strong><a class="topbar-site" href="../" target="_blank" rel="noopener noreferrer">Lihat web</a></header>';
    echo '<div class="scrim" data-drawer-close hidden></div>';
    echo '<div class="wrap"><nav class="side" id="drawer" aria-label="Menu dashboard"><div class="side-brand"><strong>Personal HUB</strong><span class="muted">Dashboard admin</span><button class="icon-btn drawer-close" type="button" data-drawer-close aria-label="Tutup menu">' . icon('close') . '</button></div>';
    foreach ($groups as $group => $nav) {
        echo '<div class="nav-group"><p>' . e($group) . '</p>';
        foreach ($nav as $k => $l) echo '<a class="' . ($k === $page ? 'on' : '') . '"' . ($k === $page ? ' aria-current="page"' : '') . ' href="index.php?p=' . $k . '">' . e($l) . '</a>';
        echo '</div>';
    }
    echo '<form class="logout-form" method="post" action="logout.php">' . csrf_field() . '<input type="hidden" name="confirm" value="yes"><button class="btn sec" type="submit" data-confirm="Keluar dari dashboard?">Keluar</button></form></nav>';
    echo '<main id="content"><h1 class="page-title">' . e($title) . '</h1>';
    flash_show();
    layout_page($page);
}
/** Remembers which page layout_head() rendered so layout_foot() can mark the matching tab. */
function layout_page(?string $set = null): string { static $page = ''; if ($set !== null) $page = $set; return $page; }
/** Fixed bottom tab bar for phones (hidden on wide screens by CSS). */
function tabbar(string $page): string {
    $tabs = ['overview' => ['Overview', 'home'], 'projects' => ['Projects', 'folder'], 'tiktok' => ['TikTok', 'video'], 'contact' => ['Contact', 'mail']];
    $h = '<nav class="tabbar" aria-label="Pintasan">';
    foreach ($tabs as $k => $t) $h .= '<a class="' . ($k === $page ? 'on' : '') . '"' . ($k === $page ? ' aria-current="page"' : '') . ' href="index.php?p=' . $k . '">' . icon($t[1]) . '<span>' . e($t[0]) . '</span></a>';
    return $h . '<button type="button" data-drawer-toggle aria-controls="drawer" aria-expanded="false">' . icon('menu') . '<span>Menu</span></button></nav>';
}
function layout_foot(): void { echo '</main></div>' . tabbar(layout_page()) . '<script src="assets/admin.js"></script></body></html>'; }

function post_action(): ?string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    csrf_verify();
    $action = $_POST['action'] ?? null;
    if (!is_string($action) || !preg_match('/^[a-z][a-z0-9_]{0,39}$/D', $action)) {
        http_response_code(400); echo 'Aksi tidak valid.'; exit;
    }
    return $action;
}
function back(string $page, string $type = 'ok', string $msg = ''): void { if ($msg !== '') flash($type, $msg); redirect('index.php?p=' . $page); }
