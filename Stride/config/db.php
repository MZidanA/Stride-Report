<?php
// Konfigurasi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Ganti dengan username database Anda
define('DB_PASSWORD', '');     // Ganti dengan password database Anda
define('DB_NAME', 'stride_db'); // Ganti dengan nama database Anda

// Buat koneksi database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>