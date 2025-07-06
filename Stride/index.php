<?php
// session_start() sudah dipanggil di functions.php
require_once __DIR__ . '/includes/functions.php';

// Arahkan ke halaman beranda jika sudah login, atau ke login jika belum
if (isLoggedIn()) {
    redirect('/stride/stride-report/pages/homepage.php');
} else {
    redirect('/stride/stride-report/auth/login.php'); // Atau ke halaman selamat datang umum
}
?>