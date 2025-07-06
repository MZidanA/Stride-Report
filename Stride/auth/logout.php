<?php
// session_start() sudah dipanggil di functions.php
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################
    // Untuk API, logout hanya perlu mengembalikan respons sukses.
    // Penghapusan token dilakukan di sisi klien (aplikasi mobile).
    apiResponse(['success' => true, 'message' => 'Anda telah berhasil logout.']);
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
session_unset(); // Hapus semua variabel sesi
session_destroy(); // Hancurkan sesi
$_SESSION['success_message'] = "Anda telah berhasil logout."; // Set pesan untuk web
redirect('/stride/stride-report/auth/login.php'); // Redirect untuk web
exit();
?>
