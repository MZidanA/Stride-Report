<?php
// session_start() sudah dipanggil di functions.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// --- HEADER PEMISAH MODE ---
if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || isset($_GET['api']))) {
    // #############################################
    // ############# MODE: BACKEND API #############
    // #############################################
    if (!isAdmin()) { // Sekarang menggunakan isAdmin() yang diperbarui untuk token
        apiResponse(['success' => false, 'error_message' => 'Akses ditolak. Anda bukan Administrator.'], 403);
    }

    // Coba baca JSON body, jika tidak ada, fallback ke $_POST (untuk multipart/form-data)
    $input = json_decode(file_get_contents('php://input'), true);
    $report_id = $input['report_id'] ?? $_POST['report_id'] ?? null;
    $action = $input['action'] ?? $_POST['action'] ?? '';

    if (empty($report_id)) {
        apiResponse(['success' => false, 'error_message' => 'ID laporan tidak valid.'], 400);
    }

    if ($action == 'update_status') {
        $status = $input['status'] ?? $_POST['status'] ?? '';
        if (empty($status)) {
             apiResponse(['success' => false, 'error_message' => 'Status tidak boleh kosong.'], 400);
        }
        $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $report_id);
        if ($stmt->execute()) {
            apiResponse(['success' => true, 'message' => 'Status laporan berhasil diperbarui.']);
        } else {
            apiResponse(['success' => false, 'error_message' => 'Gagal memperbarui status laporan.'], 500);
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        // Ambil path gambar untuk dihapus dari server
        $stmt_img = $conn->prepare("SELECT image_path FROM reports WHERE id = ?");
        $stmt_img->bind_param("i", $report_id);
        $stmt_img->execute();
        $stmt_img->bind_result($image_path);
        $stmt_img->fetch();
        $stmt_img->close();

        $conn->begin_transaction(); // Mulai transaksi untuk memastikan konsistensi
        try {
            // Hapus upvotes terkait terlebih dahulu
            $stmt_upvotes = $conn->prepare("DELETE FROM upvotes WHERE report_id = ?");
            $stmt_upvotes->bind_param("i", $report_id);
            $stmt_upvotes->execute();
            $stmt_upvotes->close();

            // Hapus laporan
            $stmt_delete = $conn->prepare("DELETE FROM reports WHERE id = ?");
            $stmt_delete->bind_param("i", $report_id);
            if ($stmt_delete->execute()) {
                // Hapus file gambar dari server
                if ($image_path && file_exists(__DIR__ . '/..' . $image_path)) {
                    unlink(__DIR__ . '/..' . $image_path);
                }
                $conn->commit(); // Komit transaksi jika berhasil
                apiResponse(['success' => true, 'message' => 'Laporan berhasil dihapus.']);
            } else {
                $conn->rollback(); // Rollback jika ada kesalahan
                apiResponse(['success' => false, 'error_message' => 'Gagal menghapus laporan.'], 500);
            }
            $stmt_delete->close();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback(); // Rollback jika ada exception SQL
            apiResponse(['success' => false, 'error_message' => 'Terjadi kesalahan database saat menghapus laporan: ' . $exception->getMessage()], 500);
        }
    } else {
        apiResponse(['success' => false, 'error_message' => 'Aksi tidak valid.'], 400);
    }
    exit();
}

// #############################################
// ############# MODE: WEB FRONTEND ############
// #############################################
// Ini tidak memerlukan require_once header.php atau footer.php karena ini adalah script pemrosesan POST
// yang kemudian melakukan redirect.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php'; // db.php diperlukan di sini untuk koneksi database

if (!isAdmin()) {
    $_SESSION['error_message'] = "Akses ditolak. Anda bukan Administrator.";
    redirect('/stride/stride-report/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];

    if ($action == 'update_status' && isset($_POST['status'])) {
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $report_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Status laporan berhasil diperbarui.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui status laporan.";
        }
        $stmt->close();
    } elseif ($action == 'delete') {
        $stmt_img = $conn->prepare("SELECT image_path FROM reports WHERE id = ?");
        $stmt_img->bind_param("i", $report_id);
        $stmt_img->execute();
        $stmt_img->bind_result($image_path);
        $stmt_img->fetch();
        $stmt_img->close();

        $stmt_upvotes = $conn->prepare("DELETE FROM upvotes WHERE report_id = ?");
        $stmt_upvotes->bind_param("i", $report_id);
        $stmt_upvotes->execute();
        $stmt_upvotes->close();

        $stmt_delete = $conn->prepare("DELETE FROM reports WHERE id = ?");
        $stmt_delete->bind_param("i", $report_id);
        if ($stmt_delete->execute()) {
            if ($image_path && file_exists(__DIR__ . '/..' . $image_path)) {
                unlink(__DIR__ . '/..' . $image_path);
            }
            $_SESSION['success_message'] = "Laporan berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus laporan.";
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Aksi tidak valid.";
    }
} else {
    $_SESSION['error_message'] = "Permintaan tidak valid.";
}

redirect('/stride/stride-report/admin/dashboard.php');
?>
