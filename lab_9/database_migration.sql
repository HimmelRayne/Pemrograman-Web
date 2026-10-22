USE db_akademik_mhs;

-- Migrasi tabel `halaman` untuk CRUD Admin dan slider beranda.
-- Aman dijalankan berulang kali: setiap langkah memeriksa kondisi tabel terlebih dahulu.
-- Kolom `id` lama tidak diubah/dihapus.

-- 1) Tambahkan kolom identitas unik `halaman_id` bila belum ada.
--    - Tabel belum punya PRIMARY KEY             -> halaman_id AUTO_INCREMENT PRIMARY KEY (kasus umum).
--    - Tabel sudah punya PRIMARY KEY AUTO_INCREMENT (mis. `id`) -> MariaDB hanya mengizinkan satu kolom
--      AUTO_INCREMENT, sehingga halaman_id dibuat sebagai kolom alias virtual dari `id` (data tidak diubah).
SET @ada_kolom := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'halaman' AND COLUMN_NAME = 'halaman_id');
SET @ada_pk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'halaman' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @sql := IF(@ada_kolom > 0,
  'SELECT ''Kolom halaman_id sudah ada, dilewati'' AS info',
  IF(@ada_pk > 0,
    'ALTER TABLE halaman ADD COLUMN halaman_id INT AS (id) VIRTUAL FIRST',
    'ALTER TABLE halaman ADD COLUMN halaman_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'));
PREPARE langkah1 FROM @sql;
EXECUTE langkah1;
DEALLOCATE PREPARE langkah1;

-- 1b) Kolom `id` lama yang NOT NULL tanpa default (bukan PK/AUTO_INCREMENT) membuat INSERT dari Admin
--     gagal di SQL strict mode ("Field 'id' doesn't have a default value"). Kolom id dilonggarkan
--     menjadi NULL DEFAULT NULL (tipe data dan isi tidak berubah). Aplikasi memakai halaman_id.
SET @sql := (SELECT CONCAT('ALTER TABLE halaman MODIFY id ', COLUMN_TYPE, ' NULL DEFAULT NULL')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'halaman' AND COLUMN_NAME = 'id'
    AND IS_NULLABLE = 'NO' AND COLUMN_DEFAULT IS NULL
    AND EXTRA NOT LIKE '%auto_increment%' AND COLUMN_KEY NOT IN ('PRI', 'UNI'));
SET @sql := IFNULL(@sql, 'SELECT ''Kolom id tidak perlu diubah, dilewati'' AS info');
PREPARE langkah1b FROM @sql;
EXECUTE langkah1b;
DEALLOCATE PREPARE langkah1b;

-- 2) Rapikan slug duplikat yang sudah ada agar indeks unik bisa dibuat.
UPDATE halaman h
JOIN (
  SELECT slug, MIN(halaman_id) AS keep_id
  FROM halaman
  GROUP BY slug
  HAVING COUNT(*) > 1
) d ON h.slug = d.slug
SET h.slug = CONCAT(h.slug, '-', h.halaman_id)
WHERE h.halaman_id <> d.keep_id;

-- 3) Indeks unik pada slug bila belum ada.
SET @ada_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'halaman' AND INDEX_NAME = 'uq_halaman_slug');
SET @sql := IF(@ada_idx > 0,
  'SELECT ''Indeks uq_halaman_slug sudah ada, dilewati'' AS info',
  'ALTER TABLE halaman ADD UNIQUE KEY uq_halaman_slug (slug)');
PREPARE langkah3 FROM @sql;
EXECUTE langkah3;
DEALLOCATE PREPARE langkah3;
