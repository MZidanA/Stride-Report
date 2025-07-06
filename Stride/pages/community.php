<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
// Cek apakah ini permintaan API (GET atau POST dengan Accept: application/json)
if ((strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' || strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    // Logika untuk UPVOTE (API POST)
    if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && isset($_POST['upvote_report_id'])) {
        if (!isLoggedIn()) { // Menggunakan isLoggedIn() yang diperbarui
            apiResponse(['success' => false, 'error_message' => 'Anda harus login untuk melakukan upvote.'], 401);
        }

        $report_id = $_POST['upvote_report_id'];
        $user_id = $GLOBALS['current_user_id']; // Ambil user_id dari payload token

        $stmt = $conn->prepare("SELECT id FROM upvotes WHERE report_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $report_id, $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            apiResponse(['success' => false, 'error_message' => 'Anda sudah pernah upvote laporan ini.'], 409); // Conflict
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO upvotes (report_id, user_id) VALUES (?, ?)");
            $stmt_insert->bind_param("ii", $report_id, $user_id);
            if ($stmt_insert->execute()) {
                apiResponse(['success' => true, 'message' => 'Upvote berhasil!']);
            } else {
                apiResponse(['success' => false, 'error_message' => 'Gagal melakukan upvote.'], 500);
            }
            $stmt_insert->close();
        }
        $stmt->close();
        exit();
    }
    // Logika untuk GET Daftar Laporan Komunitas (API GET)
    else if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
        $reports = [];
        $stmt = $conn->prepare("
            SELECT r.id, r.image_path, r.address_text, r.map_link, r.status, u.name AS reporter_name, COUNT(v.id) AS upvote_count, r.created_at
            FROM reports r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN upvotes v ON r.id = v.report_id
            GROUP BY r.id
            ORDER BY upvote_count DESC, r.created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['full_image_url'] = getFullImageUrl($row['image_path']); // Gunakan fungsi helper
            $row['created_at_formatted'] = date('d M Y H:i', strtotime($row['created_at']));
            $reports[] = $row;
        }
        $stmt->close();
        apiResponse(['success' => true, 'reports' => $reports]);
        exit();
    }
    // Jika ada permintaan API yang tidak cocok (misalnya, method yang salah)
    else {
        apiResponse(['success' => false, 'error_message' => 'Metode permintaan atau aksi tidak valid.'], 405); // Method Not Allowed
        exit();
    }
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
require_once __DIR__ . '/../includes/header.php';

// Handle upvote (versi web, menggunakan redirect)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upvote_report_id'])) {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = "Anda harus login untuk melakukan upvote.";
        redirect('/stride/stride-report/auth/login.php');
    }

    $report_id = $_POST['upvote_report_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT id FROM upvotes WHERE report_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Anda sudah pernah upvote laporan ini.";
    } else {
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
    redirect('/stride/stride-report/pages/community.php');
}

// Ambil semua laporan beserta jumlah upvote untuk ditampilkan di web
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
    <div class="bento-grid-container">
        <?php foreach ($reports as $report): ?>
            <div class="report-card">
                <img src="<?php echo htmlspecialchars($report['image_path']); ?>" alt="Gambar Kerusakan">
                <div class="report-content">
                    <h3>Laporan #<?php echo htmlspecialchars($report['id']); ?></h3>
                    <p><strong>Dilaporkan oleh:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
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
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="upvote_report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                            <button type="submit" class="btn">Upvote</button>
                        </form>
                        <a href="/stride/stride-report/pages/report_detail.php?id=<?php echo htmlspecialchars($report['id']); ?>" class="btn">Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
