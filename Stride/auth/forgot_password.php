<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        apiResponse(['success' => false, 'error_message' => 'Silakan masukkan alamat email yang valid.'], 400);
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $reset_token, $expires_at, $user['id']);
            if ($stmt->execute()) {
                // Dalam PRODUKSI, link ini akan DIKIRIM VIA EMAIL ke user.
                // Jangan tampilkan link di respons API untuk keamanan.
                apiResponse(['success' => true, 'message' => 'Jika email Anda terdaftar, instruksi reset password telah dikirimkan.']);
            } else {
                apiResponse(['success' => false, 'error_message' => 'Terjadi kesalahan saat membuat token reset. Silakan coba lagi.'], 500);
            }
            $stmt->close();
        } else {
            // Untuk keamanan, berikan pesan yang sama apakah email terdaftar atau tidak
            apiResponse(['success' => true, 'message' => 'Jika email Anda terdaftar, instruksi reset password telah dikirimkan.']);
        }
    }
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Silakan masukkan alamat email yang valid.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $reset_token, $expires_at, $user['id']);
            if ($stmt->execute()) {
                // SIMULASI PENTING: Untuk demo web, kita tampilkan linknya langsung.
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/stride/stride-report/auth/reset_password.php?token=" . $reset_token;
                $_SESSION['success_message'] = "Jika email Anda terdaftar, instruksi reset password telah dikirimkan. <br> Untuk demo, klik link ini untuk reset: <a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a><br><small>(Token berlaku 1 jam)</small>";
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan saat membuat token reset. Silakan coba lagi.";
            }
            $stmt->close();
        } else {
            $_SESSION['success_message'] = "Jika email Anda terdaftar, instruksi reset password telah dikirimkan.";
        }
    }
}
?>

<h2>Lupa Kata Sandi?</h2>
<?php displayMessage(); ?>
<p>Masukkan alamat email Anda yang terdaftar, dan kami akan membantu Anda mereset kata sandi.</p>
<form action="" method="POST">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <button type="submit" class="btn">Kirim Instruksi Reset</button>
</form>
<p>Kembali ke <a href="/stride/stride-report/auth/login.php">Login</a>.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
