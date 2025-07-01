<?php
require_once __DIR__ . '/../includes/header.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = "Akses ditolak. Anda bukan Administrator.";
    redirect('/stride/auth/login.php');
}

// Ambil semua laporan beserta info user dan jumlah upvote
$reports = [];
$stmt = $conn->prepare("
    SELECT r.id, r.image_path, r.address_text, r.map_link, r.status, u.name AS reporter_name, COUNT(v.id) AS upvote_count, r.created_at
    FROM reports r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN upvotes v ON r.id = v.report_id
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<h2>Admin Dashboard - Kelola Laporan</h2>
<?php displayMessage(); ?>

<?php if (empty($reports)): ?>
    <p>Belum ada laporan yang masuk.</p>
<?php else: ?>
    <?php foreach ($reports as $report): ?>
        <div class="report-card">
            <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Gambar Kerusakan">
            <div class="report-content">
                <h3>Laporan #<?php echo htmlspecialchars($report['id']); ?></h3>
                <p><strong>Dilaporkan oleh:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                <p><strong>Tanggal Laporan:</strong> <?php echo date('d M Y H:i', strtotime($report['created_at'])); ?></p>
                <p><strong>Alamat:</strong> <?php echo nl2br(htmlspecialchars($report['address_text'])); ?></p>
                <p><strong>Upvotes:</strong> <?php echo htmlspecialchars($report['upvote_count']); ?></p>
                <p><strong>Link Peta:</strong> <a href="<?php echo htmlspecialchars($report['map_link']); ?>" target="_blank">Lihat di Peta</a></p>
                <p><strong>Status Saat Ini:</strong> <span style="font-weight: bold; color: <?php
                    if ($report['status'] == 'Pending') echo '#ffc107';
                    else if ($report['status'] == 'In Progress') echo '#17a2b8';
                    else if ($report['status'] == 'Completed') echo '#28a745';
                    else if ($report['status'] == 'Rejected') echo '#dc3545';
                    else echo '#6c757d';
                ?>;"><?php echo htmlspecialchars($report['status']); ?></span></p>

                <div class="admin-report-actions">
                    <form action="/stride/admin/update_report.php" method="POST">
                        <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label for="status_<?php echo $report['id']; ?>">Ubah Status:</label>
                            <select name="status" id="status_<?php echo $report['id']; ?>" style="width: auto; display: inline-block;">
                                <option value="Pending" <?php echo ($report['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo ($report['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo ($report['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Rejected" <?php echo ($report['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="action" value="update_status" class="btn" style="margin-left: 10px;">Update Status</button>
                        </div>
                    </form>
                    <form action="/stride/admin/update_report.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
                        <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                        <button type="submit" name="action" value="delete" class="btn btn-danger">Hapus Laporan</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>