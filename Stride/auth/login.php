<?php
require_once __DIR__ . '/../includes/header.php';

if (isLoggedIn()) {
    redirect('/stride/pages/homepage.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email dan password harus diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $hashed_password, $is_admin);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_admin'] = (bool)$is_admin;
                $_SESSION['success_message'] = "Selamat datang, " . $name . "!";
                redirect('/stride/pages/homepage.php');
            } else {
                $_SESSION['error_message'] = "Email atau password salah.";
            }
        } else {
            $_SESSION['error_message'] = "Email atau password salah.";
        }
        $stmt->close();
    }
}
?>

<h2>Login</h2>
<?php displayMessage(); ?>
<form action="" method="POST">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn">Login</button>
</form>
<p>Belum punya akun? <a href="/stride/auth/register.php">Daftar di sini</a>.</p>
<p><a href="/stride/auth/forgot_password.php">Lupa Password?</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>