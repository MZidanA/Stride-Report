<?php
require_once __DIR__ . '/../includes/header.php';

// Handle upvote
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upvote_report_id'])) {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = "Anda harus login untuk melakukan upvote.";
        redirect('/stride/auth/login.php');
    }

    $report_id = $_POST['upvote_report_id'];
    $user_id = $_SESSION['user_id'];

    // Cek apakah user sudah pernah upvote laporan ini
    $stmt = $conn->prepare("SELECT id FROM upvotes WHERE report_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Anda sudah pernah upvote laporan ini.";
    } else {
        // Tambahkan upvote
        $stmt_insert = $conn->prepare("INSERT INTO upvotes (report_id, user_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $report_id, $user_id);
        if ($stmt_insert->execute()) {
            $_SESSION['success_message'] = "Upvote berhasil!";
        } else {
            $_SESSION['error_message'] = "Gagal melakukan upvote.";
        }
        $stmt_insert->close();
    }
    $stmt->close();
    redirect('/stride/pages/community.php'); // Redirect untuk menghindari resubmission
}

// Ambil semua laporan beserta jumlah upvote
$reports = [];
$stmt = $conn->prepare("
    SELECT r.id, r.image_path, r.address_text, r.map_link, r.status, u.name AS reporter_name, COUNT(v.id) AS upvote_count
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN upvotes v ON r.id = v.report_id
    GROUP BY r.id
    ORDER BY upvote_count DESC, r.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<h2>Komunitas Laporan Kerusakan Jalan</h2>
<?php displayMessage(); ?>

<?php if (empty($reports)): ?>
    <p>Belum ada laporan yang tersedia di komunitas. Jadilah yang pertama melaporkan!</p>
<?php else: ?>
    <?php foreach ($reports as $report): ?>
        <div class="report-card">
            <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Gambar Kerusakan">
            <div class="report-content">
                <h3>Laporan #<?php echo htmlspecialchars($report['id']); ?></h3>
                <p><strong>Dilaporkan oleh:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($report['address_text'])); ?></p>
                <p><strong>Status:</strong> <span style="font-weight: bold; color: <?php
                    if ($report['status'] == 'Pending') echo '#ffc107'; // yellow
                    else if ($report['status'] == 'In Progress') echo '#17a2b8'; // info blue
                    else if ($report['status'] == 'Completed') echo '#28a745'; // green
                    else if ($report['status'] == 'Rejected') echo '#dc3545'; // red
                    else echo '#6c757d'; // secondary gray
                ?>;"><?php echo htmlspecialchars($report['status']); ?></span></p>
                <p><strong>Upvotes:</strong> <?php echo htmlspecialchars($report['upvote_count']); ?></p>
                <div class="report-actions">
                    <a href="<?php echo htmlspecialchars($report['map_link']); ?>" target="_blank" class="btn btn-secondary">Lihat Peta</a>
                    <form action="" method="POST" style="display: inline;">
                        <input type="hidden" name="upvote_report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                        <button type="submit" class="btn">Upvote</button>
                    </form>
                    <a href="/stride/pages/report_detail.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn">Bagikan</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>