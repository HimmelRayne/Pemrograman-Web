<?php
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php?modul=login');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$pesan = '';
$error = '';
$editData = null;

function admin_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_ringkas($teks, $panjang = 100) {
    $teks = (string)$teks;
    if (function_exists('mb_strimwidth')) return mb_strimwidth($teks, 0, $panjang, '...', 'UTF-8');
    return strlen($teks) > $panjang ? substr($teks, 0, $panjang - 3) . '...' : $teks;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'simpan') {
            $halamanId = (int)($_POST['halaman_id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $foto = trim($_POST['foto'] ?? '');
            $konten = trim($_POST['konten'] ?? '');
            $slug = trim($_POST['slug'] ?? '');

            if ($judul === '' || $slug === '' || $konten === '') {
                $error = 'Judul, slug, dan konten wajib diisi.';
            } elseif (!preg_match('/^[a-zA-Z0-9-]+$/', $slug)) {
                $error = 'Slug hanya boleh berisi huruf, angka, dan tanda hubung.';
            } elseif ($foto !== '' && (!filter_var($foto, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $foto))) {
                $error = 'URL foto harus berupa URL http:// atau https:// yang valid, atau dikosongkan.';
            } else {
                if ($halamanId > 0) {
                    $stmt = mysqli_prepare($conn, "UPDATE halaman SET judul = ?, foto = ?, konten = ?, slug = ? WHERE halaman_id = ?");
                    mysqli_stmt_bind_param($stmt, 'ssssi', $judul, $foto, $konten, $slug, $halamanId);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    if ($ok) {
                        header('Location: index.php?modul=admin&status=updated');
                        exit;
                    }
                    $error = 'Gagal memperbarui halaman. Pastikan slug belum digunakan.';
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO halaman (judul, foto, konten, slug) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'ssss', $judul, $foto, $konten, $slug);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    if ($ok) {
                        header('Location: index.php?modul=admin&status=created');
                        exit;
                    }
                    $error = 'Gagal menambahkan halaman. Pastikan slug belum digunakan dan migrasi database sudah dijalankan.';
                }
            }
            $editData = ['halaman_id' => $halamanId, 'judul' => $judul, 'foto' => $foto, 'konten' => $konten, 'slug' => $slug];
        } elseif ($aksi === 'hapus') {
            $halamanId = (int)($_POST['halaman_id'] ?? 0);
            if ($halamanId > 0) {
                $stmt = mysqli_prepare($conn, "DELETE FROM halaman WHERE halaman_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $halamanId);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                if ($ok) {
                    header('Location: index.php?modul=admin&status=deleted');
                    exit;
                }
                $error = 'Gagal menghapus halaman.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $idEdit = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT halaman_id, judul, foto, konten, slug FROM halaman WHERE halaman_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idEdit);
    mysqli_stmt_execute($stmt);
    $resultEdit = mysqli_stmt_get_result($stmt);
    $editData = mysqli_fetch_assoc($resultEdit) ?: null;
    mysqli_stmt_close($stmt);
}

$status = $_GET['status'] ?? '';
if ($status === 'created') $pesan = 'Halaman berhasil ditambahkan.';
if ($status === 'updated') $pesan = 'Halaman berhasil diperbarui.';
if ($status === 'deleted') $pesan = 'Halaman berhasil dihapus.';

$result = mysqli_query($conn, "SELECT halaman_id, judul, foto, konten, slug FROM halaman ORDER BY halaman_id DESC");
$daftarHalaman = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $daftarHalaman[] = $row;
} else {
    $error = 'Gagal mengambil data halaman. Pastikan file migrasi database sudah dijalankan.';
}
?>
<h2>Admin: Kelola Halaman</h2>
<p>Halo, <strong><?= admin_h($_SESSION['admin_nama'] ?? 'Admin') ?></strong>. Gunakan formulir ini untuk membuat, mengedit, dan menghapus halaman website.</p>
<?php if ($pesan !== ''): ?><div class="notice"><?= admin_h($pesan) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?= admin_h($error) ?></div><?php endif; ?>

<h3><?= $editData ? 'Edit Halaman' : 'Tambah Halaman Baru' ?></h3>
<form method="post" action="index.php?modul=admin">
  <input type="hidden" name="csrf_token" value="<?= admin_h($csrf) ?>">
  <input type="hidden" name="aksi" value="simpan">
  <input type="hidden" name="halaman_id" value="<?= admin_h($editData['halaman_id'] ?? 0) ?>">
  <div class="form-grid">
    <label>Judul
      <input name="judul" maxlength="200" value="<?= admin_h($editData['judul'] ?? '') ?>" required>
    </label>
    <label>Slug URL
      <input name="slug" maxlength="200" placeholder="contoh: profil-kampus" value="<?= admin_h($editData['slug'] ?? '') ?>" required>
    </label>
    <label class="full">URL Foto (opsional)
      <input type="url" name="foto" placeholder="https://contoh.com/foto.jpg" value="<?= admin_h($editData['foto'] ?? '') ?>">
    </label>
    <label class="full">Konten
      <textarea name="konten" required><?= admin_h($editData['konten'] ?? '') ?></textarea>
    </label>
  </div>
  <div class="actions">
    <button class="btn btn-success" type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah Halaman' ?></button>
    <?php if ($editData): ?><a class="btn btn-muted" href="index.php?modul=admin">Batal Edit</a><?php endif; ?>
  </div>
</form>

<h3 style="margin-top:35px;">Daftar Halaman</h3>
<div style="overflow-x:auto;">
<table>
  <thead><tr><th>ID</th><th>Judul</th><th>Slug</th><th>Foto</th><th>Konten</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php if ($daftarHalaman): foreach ($daftarHalaman as $hal): ?>
    <tr>
      <td><?= (int)$hal['halaman_id'] ?></td>
      <td><?= admin_h($hal['judul']) ?></td>
      <td><?= admin_h($hal['slug']) ?></td>
      <td><?php if (!empty($hal['foto'])): ?><a href="<?= admin_h($hal['foto']) ?>" target="_blank" rel="noopener noreferrer">Lihat foto</a><?php else: ?>- <?php endif; ?></td>
      <td><?= admin_h(admin_ringkas($hal['konten'], 100)) ?></td>
      <td><div class="actions">
        <a class="btn btn-primary" href="index.php?modul=admin&edit=<?= (int)$hal['halaman_id'] ?>">Edit</a>
        <form method="post" action="index.php?modul=admin" onsubmit="return confirm('Yakin ingin menghapus halaman ini?');">
          <input type="hidden" name="csrf_token" value="<?= admin_h($csrf) ?>">
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="halaman_id" value="<?= (int)$hal['halaman_id'] ?>">
          <button class="btn btn-danger" type="submit">Hapus</button>
        </form>
      </div></td>
    </tr>
  <?php endforeach; else: ?>
    <tr><td colspan="6" style="text-align:center;">Belum ada halaman. Tambahkan halaman melalui formulir di atas.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>