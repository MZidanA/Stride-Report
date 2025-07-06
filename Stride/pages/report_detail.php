<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    $report_id = $_GET['id'] ?? null;

    if (!isset($report_id) || !is_numeric($report_id)) {
        apiResponse(['success' => false, 'error_message' => 'ID laporan tidak valid.'], 400);
    }

    $report = null;
    $stmt = $conn->prepare("
        SELECT r.id, r.image_path, r.address_text, r.map_link, r.status, u.name AS reporter_name, COUNT(v.id) AS upvote_count, r.created_at
        FROM reports r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN upvotes v ON r.id = v.report_id
        WHERE r.id = ?
        GROUP BY r.id
    ");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        // Bentuk URL lengkap gambar untuk respons API
        $report['full_image_url'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $report['image_path'];
        $report['created_at_formatted'] = date('d M Y H:i', strtotime($report['created_at']));
    }
    $stmt->close();

    if (!$report) {
        apiResponse(['success' => false, 'error_message' => 'Laporan tidak ditemukan.'], 404);
    } else {
        apiResponse(['success' => true, 'report' => $report]);
    }
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID laporan tidak valid.";
    redirect('/stride/stride-report/pages/community.php');
}

$report_id = $_GET['id'];

$report = null;
$stmt = $conn->prepare("
    SELECT r.id, r.image_path, r.address_text, r.map_link, r.status, u.name AS reporter_name, COUNT(v.id) AS upvote_count, r.created_at
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN upvotes v ON r.id = v.report_id
    WHERE r.id = ?
    GROUP BY r.id
");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
}
$stmt->close();

if (!$report) {
    $_SESSION['error_message'] = "Laporan tidak ditemukan.";
    redirect('/stride/stride-report/pages/community.php');
}
?>

<h2>Detail Laporan Kerusakan Jalan #<?php echo htmlspecialchars($report['id']); ?></h2>
<?php displayMessage(); ?>

<div class="report-card">
    <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Gambar Kerusakan">
    <div class="report-content">
        <h3>Laporan #<?php echo htmlspecialchars($report['id']); ?></h3>
        <p><strong>Dilaporkan oleh:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
        <p><strong>Tanggal Laporan:</strong> <?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></p>
        <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($report['address_text'])); ?></p>
        <p><strong>Status:</strong> <span style="font-weight: bold; color: <?php
            if ($report['status'] == 'Pending') echo '#ffc107';
            else if ($report['status'] == 'In Progress') echo '#17a2b8';
            else if ($report['status'] == 'Completed') echo '#28a745';
            else if ($report['status'] == 'Rejected') echo '#dc3545';
            else echo '#6c757d';
        ?>;"><?php echo htmlspecialchars($report['status']); ?></span></p>
        <p><strong>Upvotes:</strong> <?php echo htmlspecialchars($report['upvote_count']); ?></p>
        <div class="report-actions">
            <a href="<?php echo htmlspecialchars($report['map_link']); ?>" target="_blank" class="btn btn-secondary">Lihat Peta</a>
            <a href="/stride/stride-report/pages/community.php" class="btn">Kembali ke Komunitas</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>