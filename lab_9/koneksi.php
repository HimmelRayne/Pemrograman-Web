<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "db_akademik_mhs";

// PHP 8.1+ secara bawaan melempar exception pada error MySQLi. Kode proyek memeriksa
// nilai balik (false) dan menampilkan pesan ramah, jadi mode ini disamakan di semua versi PHP.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>