<?php
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Selamat Datang di Stride!</h2>
<?php displayMessage(); ?>
<p>Stride adalah aplikasi inovatif yang dirancang untuk memberdayakan komunitas dalam melaporkan kerusakan jalan secara efisien. Tujuan kami adalah menciptakan jalan yang lebih aman dan terawat dengan memfasilitasi komunikasi yang cepat antara warga dan pihak berwenang.</p>

<div style="text-align: center; margin: 30px 0;">
    <img src="/stride/stride-report/img/logo.png" alt="Stride Logo" style="max-width: 200px; height: auto; border-radius: 10px;">
    <p style="font-style: italic; margin-top: 10px;">Report, Repair, Progress with Stride.</p>
</div>

<h3>Lihat Lokasi di Peta</h3>
<p>Anda bisa melihat area umum di peta di bawah ini. Untuk melihat laporan spesifik, kunjungi halaman Komunitas.</p>

<!-- Leaflet Map Container -->
<div id="mapid" style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 10px; overflow: hidden; margin-bottom: 20px;"></div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Initialize the map and set its view to Mataram, Indonesia
    // Latitude: -8.5833, Longitude: 116.1167, Zoom Level: 13
    var mymap = L.map('mapid').setView([-8.5833, 116.1167], 13);

    // Add an OpenStreetMap tile layer to the map
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        // Attribution is important for OpenStreetMap, acknowledging the data contributors
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>, Imagery &copy; <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18, // Set maximum zoom level
    }).addTo(mymap);

    // Optional: Add a marker at the center of Mataram
    // Uncomment the lines below if you want to display a marker
    /*
    L.marker([-8.5833, 116.1167]).addTo(mymap)
        .bindPopup("<b>Mataram</b><br>Capital of West Nusa Tenggara.").openPopup();
    */
</script>

<?php if (isLoggedIn()): ?>
    <div style="text-align: center; margin-top: 30px;">
        <a href="/stride/stride-report/pages/report_form.php" class="btn btn-secondary">Buat Laporan Sekarang</a>
    </div>
<?php else: ?>
    <div style="text-align: center; margin-top: 30px;">
        <p>Untuk membuat laporan, silakan <a href="/stride/stride-report/auth/login.php">Login</a> atau <a href="/stride/stride-report/auth/register.php">Daftar</a> terlebih dahulu.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
