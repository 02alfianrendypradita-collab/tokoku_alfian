
<?php
session_start();
require_once 'koneksi.php';

// Cek admin
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

// Pastikan request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nota_admin.php");
    exit;
}

// Validasi token
if (
    !isset($_SESSION['csrf_pembayaran']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_pembayaran'], $_POST['csrf_token'])
) {
    die("Permintaan tidak valid. Silakan kembali ke halaman nota.");
}

// Ambil ID transaksi
$id_transaksi = filter_input(INPUT_POST, 'id_transaksi', FILTER_VALIDATE_INT);

if (!$id_transaksi || $id_transaksi < 1) {
    $_SESSION['pesan_pembayaran'] = "ID transaksi tidak valid.";
    header("Location: nota_admin.php");
    exit;
}

// Update status pembayaran
$query = "UPDATE transaksi
          SET status = 'dibayar'
          WHERE id_transaksi = ?
          AND status = 'menunggu'";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    $_SESSION['pesan_pembayaran'] = "Gagal memproses pembayaran.";
    header("Location: nota_admin.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_transaksi);

if (mysqli_stmt_execute($stmt)) {

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $_SESSION['pesan_pembayaran'] = "Pembayaran berhasil diselesaikan!";
    } else {
        $_SESSION['pesan_pembayaran'] = "Transaksi tidak ditemukan atau sudah dibayar.";
    }

} else {
    $_SESSION['pesan_pembayaran'] = "Gagal memperbarui status pembayaran.";
}

mysqli_stmt_close($stmt);

header("Location: nota_admin.php");
exit;
?>
