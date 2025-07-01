<?php
require_once __DIR__ . '/../includes/header.php';

$token = $_GET['token'] ?? '';
$user_id = null;
$valid_token = false;

if (empty($token)) {
    $_SESSION['error_message'] = "Token reset tidak ditemukan.";
    redirect('/stride/auth/forgot_password.php');
}

// 1. Verifikasi token
$stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    // 2. Cek apakah token sudah kedaluwarsa
    if (strtotime($user['reset_token_expires_at']) > time()) {
        $valid_token = true;
        $user_id = $user['id'];
    } else {
        $_SESSION['error_message'] = "Token reset telah kedaluwarsa. Silakan minta token baru.";
        redirect('/stride/auth/forgot_password.php');
    }
} else {
    $_SESSION['error_message'] = "Token reset tidak valid.";
    redirect('/stride/auth/forgot_password.php');
}

// Proses pengubahan password jika form disubmit dan token valid
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token && isset($_POST['password'])) {
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi password harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi password tidak cocok.";
    } elseif (strlen($new_password) < 6) { // Contoh: minimal 6 karakter
        $_SESSION['error_message'] = "Password baru minimal 6 karakter.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password dan hapus token dari database
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kata sandi Anda berhasil direset. Silakan login dengan kata sandi baru Anda.";
            redirect('/stride/auth/login.php');
        } else {
            $_SESSION['error_message'] = "Terjadi kesalahan saat mereset kata sandi Anda. Silakan coba lagi.";
        }
        $stmt->close();
    }
}
?>

<h2>Reset Kata Sandi</h2>
<?php displayMessage(); ?>

<?php if ($valid_token): ?>
    <p>Silakan masukkan kata sandi baru Anda.</p>
    <form action="" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
            <label for="password">Kata Sandi Baru:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Kata Sandi Baru:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn">Reset Kata Sandi</button>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>