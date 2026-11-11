
<?php
session_start();
require_once 'koneksi.php';

// =====================================
// 1. CEK LOGIN USER
// =====================================

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'user') {
    header("Location: login.php");
    exit;
}

// =====================================
// 2. CEK METHOD REQUEST
// =====================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

// =====================================
// 3. VALIDASI TOKEN KEAMANAN
// =====================================

$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    die("Permintaan tidak valid. Silakan kembali ke halaman checkout.");
}

// =====================================
// 4. AMBIL DATA CHECKOUT
// =====================================

$id_user = (int) $_SESSION['id_user'];

$nama_penerima = trim($_POST['nama_penerima'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');
$alamat = trim($_POST['alamat_pengiriman'] ?? '');
$metode = $_POST['metode_pembayaran'] ?? '';

$metode_valid = [
    'Transfer Bank',
    'E-Wallet',
    'COD'
];

// Validasi data
if (
    $nama_penerima === '' ||
    $telepon === '' ||
    $alamat === '' ||
    !in_array($metode, $metode_valid, true)
) {
    $_SESSION['pesan'] = "Semua data checkout wajib diisi.";
    header("Location: checkout.php");
    exit;
}

if (!preg_match('/^[0-9+\-\s]{8,20}$/', $telepon)) {
    $_SESSION['pesan'] = "Nomor telepon tidak valid.";
    header("Location: checkout.php");
    exit;
}

// =====================================
// 5. CEK KERANJANG
// =====================================

if (empty($_SESSION['keranjang'])) {
    $_SESSION['pesan'] = "Keranjang belanja masih kosong.";
    header("Location: keranjang.php");
    exit;
}

$keranjang = $_SESSION['keranjang'];

try {

    // Mulai transaksi database
    mysqli_begin_transaction($conn);

    $total_harga = 0;
    $detail_produk = [];

    // =====================================
    // 6. CEK STOK DAN HARGA PRODUK
    // =====================================

    foreach ($keranjang as $id_produk => $jumlah) {

        $id_produk = (int) $id_produk;
        $jumlah = (int) $jumlah;

        if ($id_produk <= 0 || $jumlah <= 0) {
            throw new Exception("Data keranjang tidak valid.");
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk, nama_produk, harga, stok
             FROM produk
             WHERE id_produk = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);

        if (!$produk) {
            throw new Exception("Produk tidak ditemukan.");
        }

        if ($produk['stok'] < $jumlah) {
            throw new Exception(
                "Stok produk " . $produk['nama_produk'] .
                " tidak mencukupi."
            );
        }

        $harga = (float) $produk['harga'];
        $subtotal = $harga * $jumlah;

        $total_harga += $subtotal;

        $detail_produk[] = [
            'id_produk' => $id_produk,
            'jumlah' => $jumlah,
            'harga' => $harga,
            'subtotal' => $subtotal
        ];
    }

    if (empty($detail_produk)) {
        throw new Exception("Tidak ada produk yang dibeli.");
    }

    // =====================================
    // 7. SIMPAN TRANSAKSI UTAMA
    // =====================================

    $stmt_transaksi = mysqli_prepare(
        $conn,
        "INSERT INTO transaksi
        (
            id_user,
            tanggal_transaksi,
            total_harga,
            alamat_pengiriman,
            metode_pembayaran,
            status
        )
        VALUES (?, NOW(), ?, ?, ?, 'menunggu')"
    );

    mysqli_stmt_bind_param(
        $stmt_transaksi,
        "idss",
        $id_user,
        $total_harga,
        $alamat,
        $metode
    );

    if (!mysqli_stmt_execute($stmt_transaksi)) {
        throw new Exception("Gagal menyimpan transaksi.");
    }

    // Ambil ID transaksi yang baru dibuat
    $id_transaksi = mysqli_insert_id($conn);

    // =====================================
    // 8. SIMPAN DETAIL TRANSAKSI
    // =====================================

    foreach ($detail_produk as $detail) {

        $stmt_detail = mysqli_prepare(
            $conn,
            "INSERT INTO detail_transaksi
            (
                id_transaksi,
                id_produk,
                jumlah,
                harga,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt_detail,
            "iiidd",
            $id_transaksi,
            $detail['id_produk'],
            $detail['jumlah'],
            $detail['harga'],
            $detail['subtotal']
        );

        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Gagal menyimpan detail produk.");
        }

        // =====================================
        // 9. KURANGI STOK PRODUK
        // =====================================

        $stmt_stok = mysqli_prepare(
            $conn,
            "UPDATE produk
             SET stok = stok - ?
             WHERE id_produk = ?
             AND stok >= ?"
        );

        mysqli_stmt_bind_param(
            $stmt_stok,
            "iii",
            $detail['jumlah'],
            $detail['id_produk'],
            $detail['jumlah']
        );

        if (!mysqli_stmt_execute($stmt_stok)) {
            throw new Exception("Gagal mengurangi stok produk.");
        }

        if (mysqli_stmt_affected_rows($stmt_stok) !== 1) {
            throw new Exception(
                "Stok produk berubah. Silakan checkout ulang."
            );
        }
    }

    // =====================================
    // 10. SIMPAN TRANSAKSI
    // =====================================

    mysqli_commit($conn);

    // Kosongkan keranjang
    unset($_SESSION['keranjang']);

    // Hapus token checkout
    unset($_SESSION['csrf_token']);

    // Simpan pesan sukses
    $_SESSION['pesan'] =
        "Pembelian berhasil! Nomor transaksi: #" . $id_transaksi;

    // =====================================
    // 11. ARAHKAN KE NOTA PEMBELIAN
    // =====================================

    header("Location: nota.php?id=" . $id_transaksi);
    exit;

} catch (Throwable $e) {

    // Batalkan semua perubahan jika gagal
    mysqli_rollback($conn);

    $_SESSION['pesan'] = $e->getMessage();

    header("Location: checkout.php");
    exit;
}
?>
