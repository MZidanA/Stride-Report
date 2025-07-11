<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################
    if (!isLoggedIn()) { // Perlu validasi token di sini untuk API yang sebenarnya
        apiResponse(['success' => false, 'error_message' => 'Anda harus login untuk melihat riwayat laporan.'], 401);
    }

    $user_id = $_SESSION['user_id']; // Di API nyata, user_id akan diambil dari token
    $reports = [];

    $stmt = $conn->prepare("
        SELECT r.id, r.image_path, r.address_text, r.status, COUNT(v.id) AS upvote_count, r.created_at
        FROM reports r
        LEFT JOIN upvotes v ON r.id = v.report_id
        WHERE r.user_id = ?
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['full_image_url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $row['image_path'];
        $row['created_at_formatted'] = date('d M Y H:i', strtotime($row['created_at']));
        $reports[] = $row;
    }
    $stmt->close();
    apiResponse(['success' => true, 'reports' => $reports]);
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Anda harus login untuk melihat riwayat laporan.";
    redirect('/stride/stride-report/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$reports = [];

$stmt = $conn->prepare("
    SELECT r.id, r.image_path, r.address_text, r.status, COUNT(v.id) AS upvote_count, r.created_at
    FROM reports r
    LEFT JOIN upvotes v ON r.id = v.report_id
    WHERE r.user_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<h2>Riwayat Laporan Anda</h2>
<?php displayMessage(); ?>

<?php if (empty($reports)): ?>
    <p>Anda belum membuat laporan apa pun.</p>
<?php else: ?>
    <div class="bento-grid-container">
        <?php foreach ($reports as $report): ?>
            <div class="report-card">
                <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Gambar Kerusakan">
                <div class="report-content">
                    <h3>Laporan #<?php echo htmlspecialchars($report['id']); ?></h3>
                    <p><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></p>
                    <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($report['address_text'])); ?></p>
                    <p><strong>Upvotes:</strong> <?php echo htmlspecialchars($report['upvote_count']); ?></p>
                    <p><strong>Status:</strong> <span style="font-weight: bold; color: <?php
                        if ($report['status'] == 'Pending') echo '#ffc107';
                        else if ($report['status'] == 'In Progress') echo '#17a2b8';
                        else if ($report['status'] == 'Completed') echo '#28a745';
                        else if ($report['status'] == 'Rejected') echo '#dc3545';
                        else echo '#6c757d';
                    ?>;"><?php echo htmlspecialchars($report['status']); ?></span></p>
                    <div class="report-actions">
                        <a href="/stride/stride-report/pages/report_detail.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn btn-secondary">Lihat Detail</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>