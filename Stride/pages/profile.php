<?php
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Anda harus login untuk melihat profil.";
    redirect('/stride/auth/login.php'); // PERBAIKAN: path redirect
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
    <a href="/stride/pages/report_history.php" class="btn">Riwayat Laporan</a> <a href="/stride/auth/logout.php" class="btn btn-danger">Logout</a> </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>