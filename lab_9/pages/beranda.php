<?php
if (!function_exists('beranda_h')) {
    function beranda_h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Hanya izinkan URL http(s) atau path relatif untuk atribut src gambar.
if (!function_exists('beranda_src_aman')) {
    function beranda_src_aman($url) {
        $url = trim((string)$url);
        if ($url === '') return '';
        if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url) && !preg_match('#^https?://#i', $url)) return '';
        return $url;
    }
}

// Setiap record tabel `halaman` menjadi satu slide.
// Urutan eksplisit: konten ber-slug 'beranda' lebih dulu (jika ada), lalu dari yang terlama ke terbaru.
$daftarSlide = [];
$gagalMuat = false;
try {
    $resultSlide = mysqli_query($conn, "SELECT halaman_id, judul, foto, konten FROM halaman ORDER BY (slug = 'beranda') DESC, halaman_id ASC");
    if ($resultSlide) {
        while ($row = mysqli_fetch_assoc($resultSlide)) $daftarSlide[] = $row;
        mysqli_free_result($resultSlide);
    } else {
        $gagalMuat = true;
    }
} catch (mysqli_sql_exception $e) {
    $gagalMuat = true;
}
$totalSlide = count($daftarSlide);
?>
<div class="beranda">
<?php if ($gagalMuat): ?>
  <h2 class="beranda-judul">Beranda</h2>
  <div class="error">Konten beranda tidak dapat dimuat. Pastikan database sudah dibuat dan file <code>database_migration.sql</code> sudah dijalankan.</div>
<?php elseif ($totalSlide === 0): ?>
  <h2 class="beranda-judul">Selamat Datang di Beranda Utama</h2>
  <p>Belum ada konten yang dipublikasikan.
  <?php if (!empty($_SESSION['admin_id'])): ?>Tambahkan konten melalui menu <a href="index.php?modul=admin">Admin</a>, maka konten akan tampil di sini sebagai slide.<?php else: ?>Silakan kembali lagi nanti.<?php endif; ?></p>
<?php else: ?>
  <section class="slider" id="beranda-slider" aria-roledescription="carousel" aria-label="Konten beranda">
    <div class="slider-controls">
      <div class="slider-row">
        <button type="button" class="slider-btn slider-prev" aria-label="Slide sebelumnya">&#8249;</button>
        <div class="slider-dots" role="group" aria-label="Pilih slide"></div>
        <button type="button" class="slider-btn slider-next" aria-label="Slide berikutnya">&#8250;</button>
      </div>
      <div class="slider-counter" aria-live="polite"><span class="slider-current">1</span> / <span class="slider-total"><?= $totalSlide ?></span></div>
    </div>
    <div class="slider-viewport" tabindex="0">
      <div class="slides" aria-live="off">
      <?php foreach ($daftarSlide as $i => $slide): $src = beranda_src_aman($slide['foto'] ?? ''); ?>
        <article class="slide<?= $i === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="slide" aria-label="<?= ($i + 1) ?> dari <?= $totalSlide ?>" data-id="<?= (int)$slide['halaman_id'] ?>">
          <h2 class="slide-title"><?= beranda_h($slide['judul']) ?></h2>
          <?php if ($src !== ''): ?>
            <div class="slide-media"><img class="slide-image" src="<?= beranda_h($src) ?>" alt="<?= beranda_h($slide['judul']) ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>></div>
          <?php endif; ?>
          <div class="slide-text"><?= nl2br(beranda_h($slide['konten'])) ?></div>
        </article>
      <?php endforeach; ?>
      </div>
    </div>
  </section>
  <script>
  (function () {
    var slider = document.getElementById('beranda-slider');
    if (!slider) return;
    var slides = Array.prototype.slice.call(slider.querySelectorAll('.slide'));
    var total = slides.length;
    var current = 0;

    // Sembunyikan gambar yang gagal dimuat agar layout slide tetap rapi.
    Array.prototype.forEach.call(slider.querySelectorAll('.slide-image'), function (img) {
      var hide = function () { if (img.parentNode) img.parentNode.style.display = 'none'; };
      img.addEventListener('error', hide);
      if (img.complete && img.naturalWidth === 0) hide();
    });

    slider.classList.add('is-ready');
    if (total < 2) return; // satu slide: tanpa panah, titik, dan penghitung

    slider.classList.add('has-multiple');
    var dotsBox = slider.querySelector('.slider-dots');
    var counter = slider.querySelector('.slider-current');
    var dots = slides.map(function (_, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'slider-dot';
      b.setAttribute('aria-label', 'Buka slide ' + (i + 1));
      b.addEventListener('click', function () { show(i); });
      dotsBox.appendChild(b);
      return b;
    });

    function show(index) {
      current = (index + total) % total; // berputar: setelah slide terakhir kembali ke pertama
      slides.forEach(function (s, i) {
        var aktif = i === current;
        s.classList.toggle('is-active', aktif);
        s.setAttribute('aria-hidden', aktif ? 'false' : 'true');
        dots[i].classList.toggle('is-active', aktif);
        if (aktif) dots[i].setAttribute('aria-current', 'true'); else dots[i].removeAttribute('aria-current');
      });
      counter.textContent = String(current + 1);
    }

    slider.querySelector('.slider-prev').addEventListener('click', function () { show(current - 1); });
    slider.querySelector('.slider-next').addEventListener('click', function () { show(current + 1); });

    // Navigasi keyboard saat area slide difokuskan.
    slider.querySelector('.slider-viewport').addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { show(current - 1); e.preventDefault(); }
      else if (e.key === 'ArrowRight') { show(current + 1); e.preventDefault(); }
      else if (e.key === 'Home') { show(0); e.preventDefault(); }
      else if (e.key === 'End') { show(total - 1); e.preventDefault(); }
    });

    // Geser (swipe) di layar sentuh.
    var x0 = null, y0 = null;
    var vp = slider.querySelector('.slider-viewport');
    vp.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
    vp.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
      x0 = y0 = null;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(dx < 0 ? current + 1 : current - 1);
    }, { passive: true });

    show(0);
  })();
  </script>
<?php endif; ?>
</div>
