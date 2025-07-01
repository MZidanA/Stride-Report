<?php
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Semua kolom harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format email tidak valid.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
        } else {
            // Masukkan data user baru
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Pendaftaran berhasil! Silakan login.";
                redirect('/stride/auth/login.php');
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan saat pendaftaran. Silakan coba lagi.";
            }
        }
        $stmt->close();
    }
}
?>

<h2>Daftar Akun Baru</h2>
<?php displayMessage(); ?>
<form action="" method="POST">
    <div class="form-group">
        <label for="name">Nama:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn">Daftar</button>
</form>
<p>Sudah punya akun? <a href="/stride/auth/login.php">Login di sini</a>.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>