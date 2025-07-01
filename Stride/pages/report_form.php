<?php
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Anda harus login untuk membuat laporan.";
    redirect('/stride/auth/login.php'); // PERBAIKAN: path redirect
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

        // Cek apakah file gambar asli atau palsu
        $check = getimagesize($image["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['error_message'] = "File bukan gambar.";
            $uploadOk = 0;
        }

        // Cek ukuran file
        if ($image["size"] > 5000000) { // 5MB
            $_SESSION['error_message'] = "Maaf, ukuran file terlalu besar. Maksimal 5MB.";
            $uploadOk = 0;
        }

        // Izinkan format file tertentu
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            $_SESSION['error_message'] = "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
            $uploadOk = 0;
        }

        // Cek jika $uploadOk adalah 0 karena error
        if ($uploadOk == 0) {
            // Pesan error sudah diatur di atas
        } else {
            if (move_uploaded_file($image["tmp_name"], $target_file)) {
                // PERBAIKAN: Path untuk disimpan di database harus sesuai dengan nama folder proyek 'stride'
                $image_path_db = '/stride/img/uploads/' . $image_name;

                $stmt = $conn->prepare("INSERT INTO reports (user_id, image_path, address_text, map_link, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("isss", $user_id, $image_path_db, $address_text, $map_link);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Laporan berhasil dibuat!";
                    redirect('/stride/pages/notifications.php'); // PERBAIKAN: path redirect
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
    <div class="form-group">
        <label for="map_link">Link Google Maps (URL):</label>
        <input type="text" id="map_link" name="map_link" placeholder="Contoh: https://maps.app.goo.gl/abcdefg" required>
    </div>
    <button type="submit" class="btn">Kirim Laporan</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>