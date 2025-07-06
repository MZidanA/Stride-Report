<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    $new_password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';

    if (empty($token)) {
        apiResponse(['success' => false, 'error_message' => 'Token reset tidak ditemukan.'], 400);
    }

    $user_id = null;
    $valid_token = false;

    $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        if (strtotime($user['reset_token_expires_at']) > time()) {
            $valid_token = true;
            $user_id = $user['id'];
        } else {
            apiResponse(['success' => false, 'error_message' => 'Token reset telah kedaluwarsa. Silakan minta token baru.'], 400);
        }
    } else {
        apiResponse(['success' => false, 'error_message' => 'Token reset tidak valid.'], 400);
    }

    if ($valid_token) {
        if (empty($new_password) || empty($confirm_password)) {
            apiResponse(['success' => false, 'error_message' => 'Password baru dan konfirmasi password harus diisi.'], 400);
        } elseif ($new_password !== $confirm_password) {
            apiResponse(['success' => false, 'error_message' => 'Password baru dan konfirmasi password tidak cocok.'], 400);
        } elseif (strlen($new_password) < 6) {
            apiResponse(['success' => false, 'error_message' => 'Password baru minimal 6 karakter.'], 400);
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                apiResponse(['success' => true, 'message' => 'Kata sandi Anda berhasil direset. Silakan login dengan kata sandi baru Anda.']);
            } else {
                apiResponse(['success' => false, 'error_message' => 'Terjadi kesalahan saat mereset kata sandi Anda. Silakan coba lagi.'], 500);
            }
            $stmt->close();
        }
    }
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

$token = $_GET['token'] ?? '';
$user_id = null;
$valid_token = false;

if (empty($token)) {
    $_SESSION['error_message'] = "Token reset tidak ditemukan.";
    redirect('/stride/stride-report/auth/forgot_password.php');
}

$stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    if (strtotime($user['reset_token_expires_at']) > time()) {
        $valid_token = true;
        $user_id = $user['id'];
    } else {
        $_SESSION['error_message'] = "Token reset telah kedaluwarsa. Silakan minta token baru.";
        redirect('/stride/stride-report/auth/forgot_password.php');
    }
} else {
    $_SESSION['error_message'] = "Token reset tidak valid.";
    redirect('/stride/stride-report/auth/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token && isset($_POST['password'])) {
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi password harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Password baru dan konfirmasi password tidak cocok.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error_message'] = "Password baru minimal 6 karakter.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Kata sandi Anda berhasil direset. Silakan login dengan kata sandi baru Anda.";
            redirect('/stride/stride-report/auth/login.php');
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
