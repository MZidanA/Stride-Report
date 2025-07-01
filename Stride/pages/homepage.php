<?php
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Selamat Datang di Stride!</h2>
<?php displayMessage(); ?>
<p>Stride adalah aplikasi inovatif yang dirancang untuk memberdayakan komunitas dalam melaporkan kerusakan jalan secara efisien. Tujuan kami adalah menciptakan jalan yang lebih aman dan terawat dengan memfasilitasi komunikasi yang cepat antara warga dan pihak berwenang.</p>

<div style="text-align: center; margin: 30px 0;">
    <img src="/stride/img/logo.png" alt="Stride Logo" style="max-width: 200px; height: auto; border-radius: 10px;">
    <p style="font-style: italic; margin-top: 10px;">Report, Repair, Progress with Stride.</p>
</div>

<h3>Lihat Lokasi di Peta</h3>
<p>Anda bisa melihat area umum di peta di bawah ini. Untuk melihat laporan spesifik, kunjungi halaman Komunitas.</p>
<div style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
    <iframe
        width="100%"
        height="100%"
        frameborder="0" style="border:0; border-radius: 10px;"
        src="https://www.google.com/maps/embed/v1/place?key=YOUR_Maps_API_KEY&q=Indonesia"
        allowfullscreen>
    </iframe>
    <p style="text-align: center; font-size: 0.9em; color: #777; margin-top: 10px;">*Ganti 'YOUR_Maps_API_KEY' dengan kunci API Google Maps Anda.</p>
</div>

<?php if (isLoggedIn()): ?>
    <div style="text-align: center; margin-top: 30px;">
        <a href="/stride/pages/report_form.php" class="btn btn-secondary">Buat Laporan Sekarang</a>
    </div>
<?php else: ?>
    <div style="text-align: center; margin-top: 30px;">
        <p>Untuk membuat laporan, silakan <a href="/stride/auth/login.php">Login</a> atau <a href="/stride/auth/register.php">Daftar</a> terlebih dahulu.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>