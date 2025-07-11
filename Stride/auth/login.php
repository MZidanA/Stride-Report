<?php
require_once __DIR__ . '/../config/db.php'; // Koneksi DB
require_once __DIR__ . '/../includes/functions.php'; // Fungsi API dan Web

// --- HEADER PEMISAH MODE ---
// Cek apakah ini permintaan API (mencari header Accept: application/json)
// atau permintaan POST dan meminta JSON.
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    // Ambil input JSON dari body request jika ada, atau fallback ke POST biasa
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? $_POST['email'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        apiResponse(['success' => false, 'error_message' => 'Email dan password harus diisi.'], 400); // Bad Request
    }

    $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $hashed_password, $is_admin);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Data payload untuk JWT
            $payload = [
                'user_id' => $id,
                'user_name' => $name,
                'is_admin' => (bool)$is_admin
            ];
            $token = generateJwt($payload); // Buat JWT

            apiResponse([
                'success' => true,
                'message' => 'Login berhasil!',
                'user' => [
                    'user_id' => $id,
                    'user_name' => $name,
                    'is_admin' => (bool)$is_admin
                ],
                'token' => $token // Kirim token ke aplikasi mobile
            ]);
        } else {
            apiResponse(['success' => false, 'error_message' => 'Email atau password salah.'], 401); // Unauthorized
        }
    } else {
        apiResponse(['success' => false, 'error_message' => 'Email atau password salah.'], 401); // Unauthorized
    }
    $stmt->close();
    exit(); // Hentikan eksekusi setelah respons API
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php'; // Sertakan header HTML

if (isLoggedIn()) {
    redirect('/stride/stride-report/pages/homepage.php');
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
                redirect('/stride/stride-report/pages/homepage.php');
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
<?php displayMessage(); // Tampilkan pesan sesi untuk web ?>
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
<p>Belum punya akun? <a href="/stride/stride-report/auth/register.php">Daftar di sini</a>.</p>
<p><a href="/stride/stride-report/auth/forgot_password.php">Lupa Password?</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; // Sertakan footer HTML ?>
