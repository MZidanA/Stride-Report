<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################
    if (!isLoggedIn()) { // Perlu validasi token di sini untuk API yang sebenarnya
        apiResponse(['success' => false, 'error_message' => 'Anda harus login untuk melihat profil.'], 401);
    }

    $user_id = $_SESSION['user_id']; // Di API nyata, user_id akan diambil dari token
    $user_name = $_SESSION['user_name']; // Di API nyata, user_name akan diambil dari token
    $user_email = '';
    $is_admin = $_SESSION['is_admin']; // Di API nyata, is_admin akan diambil dari token

    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_email);
    $stmt->fetch();
    $stmt->close();

    apiResponse([
        'success' => true,
        'user_data' => [
            'name' => $user_name,
            'email' => $user_email,
            'is_admin' => (bool)$is_admin
        ]
    ]);
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Anda harus login untuk melihat profil.";
    redirect('/stride/stride-report/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = '';
$is_admin = $_SESSION['is_admin'];

$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_email);
$stmt->fetch();
$stmt->close();
?>

<h2>Profil Pengguna</h2>
<?php displayMessage(); ?>

<p><strong>Nama:</strong> <?php echo htmlspecialchars($user_name); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
<p><strong>Status Akun:</strong> <?php echo $is_admin ? 'Admin' : 'Pengguna Biasa'; ?></p>

<div class="report-actions" style="margin-top: 20px;">
    <a href="/stride/stride-report/pages/report_history.php" class="btn">Riwayat Laporan</a>
    <a href="/stride/stride-report/auth/logout.php" class="btn btn-danger">Logout</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>