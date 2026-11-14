<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $confirm = $_POST['confirm'] ?? null;
    if ($confirm !== 'yes') redirect('index.php');
    admin_logout();
    redirect('login.php');
}
auth_head('Keluar', 'Konfirmasi', 'Anda yakin ingin keluar dari dashboard admin?');
echo '<form method="post">' . csrf_field() . '<button class="btn danger auth-submit" type="submit" name="confirm" value="yes"><span>Ya, keluar</span></button><a class="btn sec auth-submit" href="index.php"><span>Batal</span></a></form>';
auth_foot();
