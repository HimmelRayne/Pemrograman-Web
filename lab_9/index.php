<?php
// Buffer keluaran agar header('Location: ...') di pages/*.php tetap bisa dipakai
// walaupun header.php sudah menghasilkan HTML (tidak bergantung pada output_buffering di php.ini).
ob_start();
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once 'koneksi.php';

$modul = $_GET['modul'] ?? 'beranda';
$protectedModules = ['admin'];
$allowedModules = ['beranda', 'profil', 'mahasiswa', 'kontak', 'login', 'logout', 'admin'];

if (in_array($modul, $protectedModules, true) && empty($_SESSION['admin_id'])) {
    header('Location: index.php?modul=login');
    exit;
}

include 'header.php';

if (in_array($modul, $allowedModules, true)) {
    $fileTujuan = "pages/" . $modul . ".php";
    if (file_exists($fileTujuan)) {
        include $fileTujuan;
    } else {
        echo '<h2 style="color:#c0392b;">Halaman belum tersedia.</h2>';
    }
} else {
    echo '<h2 style="color:#c0392b;">Error 404: Halaman Tidak Ditemukan!</h2>';
    echo '<p>Modul yang Anda minta tidak terdaftar dalam sistem.</p>';
}

include 'footer.php';
?>