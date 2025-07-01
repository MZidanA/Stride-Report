<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Silakan masukkan alamat email yang valid.";
    } else {
        // 1. Cek apakah email terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // 2. Buat token unik
            $reset_token = bin2hex(random_bytes(32)); // Token 64 karakter heksadesimal
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token berlaku 1 jam

            // 3. Simpan token dan waktu kadaluwarsa di database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $reset_token, $expires_at, $user['id']);
            if ($stmt->execute()) {
                // SIMULASI PENTING: Untuk demo, kita tampilkan linknya langsung.
                // Dalam aplikasi nyata, link ini akan DIKIRIM VIA EMAIL.
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/stride/auth/reset_password.php?token=" . $reset_token;
                $_SESSION['success_message'] = "Jika email Anda terdaftar, instruksi reset password telah dikirimkan. <br> Untuk demo, klik link ini untuk reset: <a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a><br><small>(Token berlaku 1 jam)</small>";
                // Dalam produksi, ini bisa redirect ke halaman konfirmasi tanpa menampilkan link
                // redirect('/stride/auth/forgot_password.php');
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan saat membuat token reset. Silakan coba lagi.";
            }
            $stmt->close();
        } else {
            // Untuk keamanan, berikan pesan yang sama apakah email terdaftar atau tidak
            $_SESSION['success_message'] = "Jika email Anda terdaftar, instruksi reset password telah dikirimkan.";
            // redirect('/stride/auth/forgot_password.php'); // Atau redirect ke halaman konfirmasi
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
<p>Kembali ke <a href="/stride/auth/login.php">Login</a>.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>