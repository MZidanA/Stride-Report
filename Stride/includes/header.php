<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stride Report</title>
    <link rel="stylesheet" href="/stride/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <h1>Stride Report</h1>
    </div>
    <div class="navbar">
        <a href="/stride/pages/homepage.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'homepage.php') !== false) ? 'active' : ''; ?>">Beranda</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
                <a href="/stride/admin/dashboard.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin/dashboard.php') !== false) ? 'active' : ''; ?>">Admin Dashboard</a>
                <a href="/stride/auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="/stride/pages/report_form.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'report_form.php') !== false) ? 'active' : ''; ?>">Buat Laporan</a>
                <a href="/stride/pages/community.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'community.php') !== false) ? 'active' : ''; ?>">Komunitas</a>
                <a href="/stride/pages/notifications.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'notifications.php') !== false) ? 'active' : ''; ?>">Notifikasi</a>
                <a href="/stride/pages/profile.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'profile.php') !== false) ? 'active' : ''; ?>">Profil</a>
                <a href="/stride/auth/logout.php">Logout</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="/stride/auth/login.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'login.php') !== false) ? 'active' : ''; ?>">Login</a>
            <a href="/stride/auth/register.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'register.php') !== false) ? 'active' : ''; ?>">Daftar</a>
        <?php endif; ?>
    </div>
    <div class="container">