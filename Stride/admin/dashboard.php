<?php
// Include necessary configuration and functions files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER SEPARATION MODE ---
// This block handles API requests (e.g., from a mobile app or AJAX calls)
// It checks if the request method is GET and if the client accepts JSON or has an 'api' GET parameter.
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################

    // API access control: Only allow administrators to view reports via API
    if (!isAdmin()) { // Uses the isAdmin() function to check user's admin status
        apiResponse(['success' => false, 'error_message' => 'Akses ditolak. Anda bukan Administrator.'], 403); // Forbidden HTTP status
    }

    $reports = [];
    // SQL query to fetch all reports for the admin view.
    // It joins 'reports' with 'users' to get the reporter's name and 'upvotes' to count upvotes.
    // Results are grouped by report ID and ordered by creation date (newest first).
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

    // Process fetched reports for API response
    while ($row = $result->fetch_assoc()) {
        // Generate full image URL for API clients, using the helper function
        $row['full_image_url'] = getFullImageUrl($row['image_path']);
        // Format creation date for consistency in API response
        $row['created_at_formatted'] = date('d M Y H:i', strtotime($row['created_at']));
        $reports[] = $row;
    }
    $stmt->close();
    apiResponse(['success' => true, 'reports' => $reports]); // Send JSON response
    exit(); // Terminate script execution after API response
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
// Include the common header for web pages
require_once __DIR__ . '/../includes/header.php';

// Web frontend access control: Redirect non-admin users if they try to access this page
if (!isAdmin()) {
    $_SESSION['error_message'] = "Akses ditolak. Anda bukan Administrator.";
    redirect('/stride/stride-report/auth/login.php'); // Redirect to login page
}

$reports = [];
// SQL query to fetch all reports for display on the admin dashboard web page.
// This query is similar to the API one, but the frontend will handle image paths and date formatting directly in HTML.
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

// Fetch all reports into an array for easy templating in the HTML section
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<h2>Admin Dashboard - Kelola Laporan</h2>
<?php displayMessage(); // Display success or error messages from session ?>

<?php if (empty($reports)): ?>
    <p>Belum ada laporan yang masuk.</p>
<?php else: ?>
    <div class="bento-grid-container">
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
                        // Dynamically set text color based on report status for visual indication
                        if ($report['status'] == 'Pending') echo '#ffc107'; // Amber for Pending
                        else if ($report['status'] == 'In Progress') echo '#17a2b8'; // Cyan for In Progress
                        else if ($report['status'] == 'Completed') echo '#28a745'; // Green for Completed
                        else if ($report['status'] == 'Rejected') echo '#dc3545'; // Red for Rejected
                        else echo '#6c757d'; // Gray for any other status (default)
                    ?>;"><?php echo htmlspecialchars($report['status']); ?></span></p>

                    <div class="admin-report-actions">
                        <!-- Form to update report status -->
                        <form action="/stride/stride-report/admin/update_report.php" method="POST">
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
                        <!-- Form to delete report with a JavaScript confirmation dialog -->
                        <form action="/stride/stride-report/admin/update_report.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus laporan ini?');">
                            <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                            <button type="submit" name="action" value="delete" class="btn btn-danger">Hapus Laporan</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
