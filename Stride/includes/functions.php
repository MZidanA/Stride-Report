<?php
// Mulai sesi jika belum dimulai.
// Ini penting untuk fungsi web frontend yang masih bergantung pada $_SESSION.
// Di lingkungan API yang lebih matang, ini akan diganti dengan validasi token stateless (misalnya JWT).
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Tambahkan ini di awal functions.php untuk CORS ---
// Ini adalah konfigurasi CORS dasar. Untuk produksi, ganti '*' dengan domain spesifik aplikasi mobile Anda.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With"); // Tambahkan Authorization dan X-Requested-With
// Jika Anda mengirim kredensial (seperti cookie atau header otentikasi), uncomment baris di bawah:
// header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS requests for CORS
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
    http_response_code(204); // No Content
    exit();
}
// --- Akhir bagian CORS ---


/**
 * Fungsi untuk menghentikan eksekusi skrip dan mengembalikan respons JSON.
 * Digunakan untuk mode API.
 * @param array $data Data yang akan di-encode ke JSON.
 * @param int $statusCode Kode status HTTP (default 200 OK).
 */
function apiResponse($data, $statusCode = 200) {
    header('Content-Type: application/json'); // Tetapkan header agar klien tahu ini JSON
    http_response_code($statusCode); // Tetapkan kode status HTTP
    echo json_encode($data); // Encode data ke JSON dan cetak
    exit(); // Hentikan eksekusi skrip lebih lanjut
}

// --- Placeholder JWT Functions (Untuk Demo) ---
// Di produksi, gunakan library JWT yang teruji (misalnya firebase/php-jwt)
define('JWT_SECRET_KEY', 'your_super_secret_key_here_change_this_in_production'); // Ganti dengan kunci rahasia yang kuat!

function generateJwt($payload) {
    // Header JWT
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    // Payload JWT (data pengguna)
    $payload['exp'] = time() + (60 * 60 * 24); // Token berlaku 24 jam
    $payload_encoded = json_encode($payload);

    // Encode Header dan Payload ke Base64Url
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload_encoded));

    // Buat Signature (sederhana, tanpa library kriptografi penuh)
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET_KEY, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Gabungkan untuk membentuk JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function validateJwt($jwt) {
    list($header_encoded, $payload_encoded, $signature_provided) = explode('.', $jwt);

    // Verifikasi Signature (sederhana)
    $expected_signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, JWT_SECRET_KEY, true);
    $expected_signature_base64Url = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));

    if ($expected_signature_base64Url !== $signature_provided) {
        return false; // Signature tidak cocok
    }

    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload_encoded)), true);

    // Periksa kedaluwarsa
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false; // Token kedaluwarsa
    }

    return $payload; // Kembalikan payload jika valid
}

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// --- Akhir Placeholder JWT Functions ---


/**
 * Fungsi untuk mengecek apakah user sudah login.
 * Diperbarui untuk mendukung JWT di mode API.
 */
function isLoggedIn() {
    // Cek apakah ini permintaan API
    if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_GET['api'])) {
        $token = getBearerToken();
        if ($token) {
            $payload = validateJwt($token);
            if ($payload) {
                // Simpan data user dari token ke variabel global untuk akses mudah di API
                // Di aplikasi nyata, Anda bisa menyimpan ini di sebuah objek Request atau sejenisnya
                $GLOBALS['current_user_id'] = $payload['user_id'];
                $GLOBALS['current_user_name'] = $payload['user_name'];
                $GLOBALS['current_is_admin'] = $payload['is_admin'];
                return true;
            }
        }
        return false; // Tidak ada token atau token tidak valid
    } else {
        // Mode Web Frontend (tetap pakai sesi)
        return isset($_SESSION['user_id']);
    }
}

/**
 * Fungsi untuk mengecek apakah user adalah admin.
 * Diperbarui untuk mendukung JWT di mode API.
 */
function isAdmin() {
    // Cek apakah ini permintaan API
    if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_GET['api'])) {
        if (isLoggedIn()) { // Memastikan token valid dan data user dimuat
            return isset($GLOBALS['current_is_admin']) && $GLOBALS['current_is_admin'] === true;
        }
        return false;
    } else {
        // Mode Web Frontend (tetap pakai sesi)
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}

/**
 * Fungsi untuk menampilkan pesan (sukses/error) di halaman web.
 * Hanya digunakan untuk mode Web Frontend.
 */
function displayMessage() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
}

/**
 * Fungsi untuk mengarahkan ulang halaman browser.
 * Hanya digunakan untuk mode Web Frontend.
 * @param string $url URL tujuan redirect.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi helper untuk mendapatkan URL lengkap gambar
function getFullImageUrl($image_path) {
    // Asumsi aplikasi PHP berjalan di root domain atau subdomain
    // Sesuaikan jika struktur URL Anda berbeda
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host . $image_path;
}
