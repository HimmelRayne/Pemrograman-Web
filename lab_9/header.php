<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Website Dinamis & MySQLi</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; color: #263238; }
    header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
    nav { background: #34495e; padding: 12px 10px; text-align: center; display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 8px 18px; }
    nav a { color: white; text-decoration: none; font-weight: bold; }
    nav a:hover { color: #1abc9c; text-decoration: underline; }
    .container { max-width: 1000px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); min-height: 250px; }
    footer { text-align: center; padding: 15px; background: #2c3e50; color: white; margin-top: 40px; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
    th { background-color: #2c3e50; color: white; }
    input, textarea { width: 100%; padding: 10px; border: 1px solid #ccd1d1; border-radius: 5px; font: inherit; margin-top: 5px; }
    textarea { min-height: 120px; resize: vertical; }
    label { display: block; margin: 14px 0; font-weight: bold; }
    .btn { display: inline-block; border: 0; border-radius: 5px; padding: 9px 13px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 14px; }
    .btn-primary { background: #2874a6; color: white; }
    .btn-success { background: #239b56; color: white; }
    .btn-danger { background: #c0392b; color: white; }
    .btn-muted { background: #e5e7e9; color: #263238; }
    .notice { padding: 12px 15px; border-radius: 5px; margin: 12px 0; background: #eafaf1; color: #196f3d; }
    .error { padding: 12px 15px; border-radius: 5px; margin: 12px 0; background: #fdedec; color: #922b21; }
    .actions { display: flex; flex-wrap: wrap; gap: 7px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
    .full { grid-column: 1 / -1; }
    .page-image { max-width: 100%; height: auto; border-radius: 6px; }
    /* ===== Beranda: konten terpusat + slider ===== */
    .beranda { text-align: center; }
    .beranda-judul { color: #2c3e50; margin: 0 0 18px; }
    .slider { max-width: 820px; margin: 0 auto; }
    .slider-viewport { outline: none; }
    .slider-viewport:focus-visible { outline: 2px solid #1abc9c; outline-offset: 6px; border-radius: 6px; }
    .slide { min-width: 0; padding: 4px 0 14px; }
    .slide + .slide { border-top: 1px solid #e5e7e9; padding-top: 22px; }
    .slide-title { margin: 0 0 16px; color: #2c3e50; font-size: 1.7rem; line-height: 1.25; overflow-wrap: anywhere; }
    .slide-media { margin: 0 auto 18px; }
    .slide-image { display: block; margin: 0 auto; max-width: 100%; max-height: 340px; width: auto; height: auto; object-fit: contain; border-radius: 6px; }
    .slide-text { max-width: 68ch; margin: 0 auto; padding: 0 6px; line-height: 1.7; overflow-wrap: anywhere; }
    .slider-controls { display: none; flex-direction: column; align-items: center; gap: 10px; margin: 0 0 22px; }
    .slider-row { display: flex; align-items: center; justify-content: center; gap: 14px; width: 100%; }
    .slider-btn { flex: none; width: 44px; height: 44px; border: 0; border-radius: 50%; background: #2c3e50; color: #fff; font-size: 24px; line-height: 1; cursor: pointer; }
    .slider-btn:hover { background: #1abc9c; }
    .slider-btn:focus-visible, .slider-dot:focus-visible { outline: 2px solid #1abc9c; outline-offset: 2px; }
    .slider-dots { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 10px; min-width: 0; }
    .slider-dot { width: 14px; height: 14px; padding: 0; border: 0; border-radius: 50%; background: #c5ccd3; cursor: pointer; }
    .slider-dot:hover { background: #95a5a6; }
    .slider-dot.is-active { background: #1abc9c; transform: scale(1.25); }
    .slider-counter { font-size: 14px; font-weight: bold; color: #566573; }
    /* Mode slider aktif (kelas ditambahkan oleh JavaScript; tanpa JS semua konten tampil berurutan).
       Hanya slide aktif yang tampil sehingga tinggi area mengikuti isi slide tersebut. */
    .slider.is-ready .slide { display: none; border-top: 0; padding-top: 4px; }
    .slider.is-ready .slide.is-active { display: block; animation: slide-masuk .35s ease; }
    .slider.is-ready .slide-text { max-height: 20em; overflow-y: auto; }
    .slider.is-ready.has-multiple .slider-controls { display: flex; }
    @keyframes slide-masuk { from { opacity: 0; } to { opacity: 1; } }
    @media (max-width: 650px) {
      .slide-title { font-size: 1.35rem; }
      .slide-image { max-height: 240px; }
      .slider-btn { width: 40px; height: 40px; }
    }
    @media (prefers-reduced-motion: reduce) {
      .slider.is-ready .slide.is-active { animation: none; }
    }
    @media (max-width: 650px) { .container { margin: 12px; padding: 16px; overflow-x: auto; } .form-grid { grid-template-columns: 1fr; } .full { grid-column: auto; } }
  </style>
</head>
<body>
  <header>
    <h1 style="margin:0;">Portal Akademik Kampus Nusantara</h1>
  </header>
  <nav>
    <a href="index.php?modul=beranda">Beranda</a>
    <a href="index.php?modul=profil">Profil Kampus</a>
    <a href="index.php?modul=mahasiswa">Data Mahasiswa</a>
    <a href="index.php?modul=kontak">Kontak</a>
    <?php if (!empty($_SESSION['admin_id'])): ?>
      <a href="index.php?modul=admin">Admin</a>
      <a href="index.php?modul=logout">Keluar (<?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>)</a>
    <?php else: ?>
      <a href="index.php?modul=login">Login Admin</a>
    <?php endif; ?>
  </nav>
  <div class="container">