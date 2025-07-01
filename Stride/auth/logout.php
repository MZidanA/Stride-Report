<?php
session_start();
session_unset(); // Hapus semua variabel sesi
session_destroy(); // Hancurkan sesi
require_once __DIR__ . '/../includes/functions.php'; // Panggil functions.php setelah session_destroy
$_SESSION['success_message'] = "Anda telah berhasil logout.";
redirect('/stride/auth/login.php');
exit();
?>