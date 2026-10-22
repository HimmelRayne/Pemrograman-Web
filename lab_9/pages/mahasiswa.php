<?php
$query = "SELECT * FROM biodata_mhs ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$listMhs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $listMhs[] = $row;
}
?>
<h2 style="color:#2c3e50; margin-top:0;">Daftar Mahasiswa Aktif</h2>
<p>Data mahasiswa diambil dari database secara dinamis.</p>
<table>
  <tr><th>No</th><th>NIM</th><th>Nama Lengkap</th><th>Program Studi</th><th>IPK</th></tr>
  <?php if (!empty($listMhs)): ?>
    <?php $no = 1; foreach ($listMhs as $m): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><strong><?= htmlspecialchars($m['nim'], ENT_QUOTES, 'UTF-8') ?></strong></td>
        <td><?= htmlspecialchars($m['nama'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['prodi'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['ipk'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?><tr><td colspan="5" style="text-align:center;">Belum ada rekaman data mahasiswa.</td></tr><?php endif; ?>
</table>