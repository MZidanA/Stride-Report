<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################
    if (!isLoggedIn()) { // Menggunakan isLoggedIn() yang diperbarui
        apiResponse(['success' => false, 'error_message' => 'Anda harus login untuk membuat laporan.'], 401);
    }

    $user_id = $GLOBALS['current_user_id']; // Ambil user_id dari payload token
    $address_text = trim($_POST['address_text'] ?? ''); // Input dari multipart/form-data
    $map_link = trim($_POST['map_link'] ?? '');
    $image = $_FILES['image'] ?? null;

    if (empty($address_text) || empty($map_link) || empty($image['name'])) {
        apiResponse(['success' => false, 'error_message' => 'Semua kolom (alamat, link peta, dan gambar) harus diisi.'], 400);
    } else {
        $target_dir = __DIR__ . "/../img/uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Buat folder jika tidak ada
        }

        $image_name = uniqid() . "_" . basename($image["name"]);
        $target_file = $target_dir . $image_name;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($image["tmp_name"]);
        if ($check === false) {
            apiResponse(['success' => false, 'error_message' => 'File bukan gambar.'], 400);
        }

        if ($image["size"] > 5000000) { // 5MB
            apiResponse(['success' => false, 'error_message' => 'Maaf, ukuran file terlalu besar. Maksimal 5MB.'], 400);
        }

        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            apiResponse(['success' => false, 'error_message' => 'Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan.'], 400);
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($image["tmp_name"], $target_file)) {
                $image_path_db = '/stride/stride-report/img/uploads/' . $image_name; // Path relatif untuk DB
                $full_image_url = getFullImageUrl($image_path_db); // Gunakan fungsi helper

                $stmt = $conn->prepare("INSERT INTO reports (user_id, image_path, address_text, map_link, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("isss", $user_id, $image_path_db, $address_text, $map_link);

                if ($stmt->execute()) {
                    apiResponse([
                        'success' => true,
                        'message' => 'Laporan berhasil dibuat!',
                        'report_id' => $conn->insert_id,
                        'image_url' => $full_image_url // Berikan URL gambar lengkap
                    ]);
                } else {
                    unlink($target_file); // Hapus file jika insert DB gagal
                    apiResponse(['success' => false, 'error_message' => 'Terjadi kesalahan saat menyimpan laporan ke database.'], 500);
                }
                $stmt->close();
            } else {
                apiResponse(['success' => false, 'error_message' => 'Maaf, terjadi kesalahan saat mengunggah file Anda.'], 500);
            }
        }
    }
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Anda harus login untuk membuat laporan.";
    redirect('/stride/stride-report/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $address_text = trim($_POST['address_text']);
    $map_link = trim($_POST['map_link']);
    $image = $_FILES['image'];

    if (empty($address_text) || empty($map_link) || empty($image['name'])) {
        $_SESSION['error_message'] = "Semua kolom (alamat, link peta, dan gambar) harus diisi.";
    } else {
        $target_dir = __DIR__ . "/../img/uploads/";
        $image_name = uniqid() . "_" . basename($image["name"]);
        $target_file = $target_dir . $image_name;
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($image["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['error_message'] = "File bukan gambar.";
            $uploadOk = 0;
        }

        if ($image["size"] > 5000000) { // 5MB
            $_SESSION['error_message'] = "Maaf, ukuran file terlalu besar. Maksimal 5MB.";
            $uploadOk = 0;
        }

        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            $_SESSION['error_message'] = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            // Pesan error sudah diatur di atas
        } else {
            if (move_uploaded_file($image["tmp_name"], $target_file)) {
                $image_path_db = '/stride/stride-report/img/uploads/' . $image_name; // Path untuk disimpan di database

                $stmt = $conn->prepare("INSERT INTO reports (user_id, image_path, address_text, map_link, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("isss", $user_id, $image_path_db, $address_text, $map_link);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Laporan berhasil dibuat!";
                    redirect('/stride/stride-report/pages/notifications.php');
                } else {
                    $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan laporan ke database.";
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Maaf, terjadi kesalahan saat mengunggah file Anda.";
            }
        }
    }
}
?>

<h2>Buat Laporan Kerusakan Jalan</h2>
<?php displayMessage(); ?>
<form action="" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="image">Unggah Gambar Kerusakan:</label>
        <input type="file" id="image" name="image" accept="image/*" required>
    </div>
    <div class="form-group">
        <label for="address_text">Deskripsi Lokasi / Alamat Lengkap:</label>
        <textarea id="address_text" name="address_text" rows="4" placeholder="Contoh: Lubang besar di Jl. Sudirman No. 12, dekat lampu merah." required></textarea>
    </div>

    <!-- Map Picker Section -->
    <div class="form-group">
        <label>Pilih Lokasi dari Peta:</label>
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" id="locationSearchInput" placeholder="Cari lokasi (contoh: Jalan Sudirman, Mataram)" style="flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            <button type="button" id="searchLocationButton" class="btn btn-secondary">Cari</button>
        </div>
        <div id="map-picker" style="height: 300px; width: 100%; border: 1px solid #ddd; border-radius: 10px; margin-bottom: 10px;"></div>
        <p style="font-size: 0.9em; color: #555;">Klik pada peta untuk menandai lokasi kerusakan. Koordinat akan otomatis dimasukkan.</p>
        <button type="button" id="getCurrentLocationButton" class="btn btn-secondary" style="margin-top: 5px;">Gunakan Lokasi Saat Ini</button>
        <div id="mapPickerMessage" style="margin-top: 10px; font-size: 0.9em; color: #555;"></div>
    </div>

    <div class="form-group">
        <label for="map_link">Link Google Maps (URL):</label>
        <input type="text" id="map_link" name="map_link" placeholder="Contoh: https://maps.app.goo.gl/abcdefg" required readonly>
    </div>
    <!-- End Map Picker Section -->

    <button type="submit" class="btn">Kirim Laporan</button>
</form>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<!-- Leaflet JavaScript -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Initialize the map for picking location
    // Centered initially on Mataram, Indonesia
    var mapPicker = L.map('map-picker').setView([-8.5833, 116.1167], 13); // Mataram coordinates

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
        maxZoom: 18,
    }).addTo(mapPicker);

    var currentMarker = null; // To store the single marker on the map

    // Function to update the map_link input field
    function updateMapLink(lat, lng) {
        const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}&zoom=15`;
        document.getElementById('map_link').value = googleMapsUrl;
        document.getElementById('mapPickerMessage').style.color = '#28a745';
        document.getElementById('mapPickerMessage').innerHTML = `Lokasi dipilih: ${lat}, ${lng}. Link peta otomatis ditambahkan.`;
    }

    // Event listener for map clicks
    mapPicker.on('click', function(e) {
        if (currentMarker) {
            mapPicker.removeLayer(currentMarker); // Remove existing marker
        }
        // Add a new marker at the clicked location
        currentMarker = L.marker(e.latlng).addTo(mapPicker)
            .bindPopup("Lokasi dipilih.").openPopup();

        updateMapLink(e.latlng.lat, e.latlng.lng);
    });

    // Event listener for "Gunakan Lokasi Saat Ini" button
    document.getElementById('getCurrentLocationButton').addEventListener('click', function() {
        const mapPickerMessage = document.getElementById('mapPickerMessage');
        mapPickerMessage.style.color = '#17a2b8';
        mapPickerMessage.innerHTML = 'Mencoba mendapatkan lokasi Anda saat ini...';

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Center the map on the current location
                mapPicker.setView([lat, lng], 15);

                if (currentMarker) {
                    mapPicker.removeLayer(currentMarker);
                }
                currentMarker = L.marker([lat, lng]).addTo(mapPicker)
                    .bindPopup("Lokasi Anda saat ini.").openPopup();

                updateMapLink(lat, lng);
            }, function(error) {
                let errorMessage = 'Gagal mendapatkan lokasi saat ini.';
                mapPickerMessage.style.color = '#dc3545';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = "Anda menolak izin lokasi.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = "Informasi lokasi tidak tersedia.";
                        break;
                    case error.TIMEOUT:
                        errorMessage = "Waktu habis saat mencoba mendapatkan lokasi.";
                        break;
                    case error.UNKNOWN_ERROR:
                        errorMessage = "Terjadi kesalahan tidak diketahui.";
                        break;
                }
                mapPickerMessage.innerHTML = errorMessage + ' Silakan klik pada peta untuk memilih lokasi secara manual.';
            });
        } else {
            mapPickerMessage.style.color = '#dc3545';
            mapPickerMessage.innerHTML = 'Browser Anda tidak mendukung Geolocation API. Silakan klik pada peta untuk memilih lokasi secara manual.';
        }
    });

    // Event listener for "Cari" button
    document.getElementById('searchLocationButton').addEventListener('click', function() {
        const query = document.getElementById('locationSearchInput').value;
        const mapPickerMessage = document.getElementById('mapPickerMessage');

        if (query.trim() === '') {
            mapPickerMessage.style.color = '#dc3545';
            mapPickerMessage.innerHTML = 'Harap masukkan nama lokasi untuk mencari.';
            return;
        }

        mapPickerMessage.style.color = '#17a2b8';
        mapPickerMessage.innerHTML = 'Mencari lokasi...';

        // Using Nominatim for geocoding (OpenStreetMap's geocoding service)
        // Ensure to respect Nominatim's Usage Policy (e.g., 1 request per second max)
        const nominatimUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;

        fetch(nominatimUrl)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const firstResult = data[0];
                    const lat = parseFloat(firstResult.lat);
                    const lng = parseFloat(firstResult.lon);

                    // Center the map on the found location
                    mapPicker.setView([lat, lng], 15); // Zoom level 15 for a closer view

                    if (currentMarker) {
                        mapPicker.removeLayer(currentMarker);
                    }
                    currentMarker = L.marker([lat, lng]).addTo(mapPicker)
                        .bindPopup(`Lokasi ditemukan: ${firstResult.display_name}`).openPopup();

                    updateMapLink(lat, lng);
                } else {
                    mapPickerMessage.style.color = '#dc3545';
                    mapPickerMessage.innerHTML = `Lokasi "${query}" tidak ditemukan. Coba kata kunci lain atau pilih dari peta.`;
                }
            })
            .catch(error => {
                console.error('Error searching location:', error);
                mapPickerMessage.style.color = '#dc3545';
                mapPickerMessage.innerHTML = 'Terjadi kesalahan saat mencari lokasi. Silakan coba lagi.';
            });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
