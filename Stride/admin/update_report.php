<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    $_SESSION['error_message'] = "Akses ditolak. Anda bukan Administrator.";
    redirect('/stride/auth/login.php');
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
        // Ambil path gambar untuk dihapus dari server
        $stmt_img = $conn->prepare("SELECT image_path FROM reports WHERE id = ?");
        $stmt_img->bind_param("i", $report_id);
        $stmt_img->execute();
        $stmt_img->bind_result($image_path);
        $stmt_img->fetch();
        $stmt_img->close();

        // Hapus upvotes terkait terlebih dahulu (karena FOREIGN KEY ON DELETE CASCADE mungkin tidak cukup untuk semua DB)
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

redirect('/stride/admin/dashboard.php');
?>