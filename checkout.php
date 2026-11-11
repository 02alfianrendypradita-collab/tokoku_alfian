
<?php
session_start();
require_once 'koneksi.php';

// Proteksi login
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: login_pembeli.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}

// Mulai transaksi database
mysqli_begin_transaction($conn);

try {
    // Ambil isi keranjang dan kunci data produk
    $stmt = mysqli_prepare($conn, "
        SELECT
            k.id_keranjang,
            k.id_produk,
            k.jumlah,
            p.nama_produk,
            p.harga,
            p.stok
        FROM keranjang k
        JOIN produk p ON k.id_produk = p.id_produk
        WHERE k.id_user = ?
        FOR UPDATE
    ");

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $items = [];
    $total = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        if ((int)$row['jumlah'] <= 0 ||
            (int)$row['jumlah'] > (int)$row['stok']) {
            throw new Exception(
                "Stok produk " . $row['nama_produk'] . " tidak mencukupi."
            );
        }

        $total += (float)$row['harga'] * (int)$row['jumlah'];
        $items[] = $row;
    }

    if (count($items) === 0) {
        throw new Exception("Keranjang masih kosong.");
    }

    // Simpan transaksi utama
    $status = "Menunggu Pembayaran";

    $insertTransaksi = mysqli_prepare($conn, "
        INSERT INTO transaksi
        (id_user, tanggal_transaksi, total_harga, status)
        VALUES (?, NOW(), ?, ?)
    ");

    mysqli_stmt_bind_param(
        $insertTransaksi,
        "ids",
        $id_user,
        $total,
        $status
    );

    if (!mysqli_stmt_execute($insertTransaksi)) {
        throw new Exception("Gagal menyimpan transaksi.");
    }

    $id_transaksi = mysqli_insert_id($conn);

    // Simpan detail dan kurangi stok
    $insertDetail = mysqli_prepare($conn, "
        INSERT INTO detail_transaksi
        (id_transaksi, id_produk, jumlah, harga)
        VALUES (?, ?, ?, ?)
    ");

    $updateStok = mysqli_prepare($conn, "
        UPDATE produk
        SET stok = stok - ?
        WHERE id_produk = ? AND stok >= ?
    ");

    foreach ($items as $item) {
        $id_produk = (int)$item['id_produk'];
        $jumlah = (int)$item['jumlah'];
        $harga = (float)$item['harga'];

        mysqli_stmt_bind_param(
            $insertDetail,
            "iiid",
            $id_transaksi,
            $id_produk,
            $jumlah,
            $harga
        );

        if (!mysqli_stmt_execute($insertDetail)) {
            throw new Exception("Gagal menyimpan detail pesanan.");
        }

        mysqli_stmt_bind_param(
            $updateStok,
            "iii",
            $jumlah,
            $id_produk,
            $jumlah
        );

        if (!mysqli_stmt_execute($updateStok) ||
            mysqli_stmt_affected_rows($updateStok) !== 1) {
            throw new Exception("Stok produk berubah. Silakan coba lagi.");
        }
    }

    // Kosongkan keranjang pembeli
    $hapusKeranjang = mysqli_prepare($conn, "
        DELETE FROM keranjang WHERE id_user = ?
    ");

    mysqli_stmt_bind_param($hapusKeranjang, "i", $id_user);
    mysqli_stmt_execute($hapusKeranjang);

    // Simpan semua perubahan
    mysqli_commit($conn);

    header("Location: checkout.php?berhasil=" . $id_transaksi);
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $pesan_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TokoKu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #344638;
        }

        .box {
            background: white;
            padding: 35px;
            border-radius: 15px;
            text-align: center;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 5px 20px #405b4615;
        }

        h2 {
            color: #405b46;
        }

        .nomor {
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            margin-top: 15px;
            background: #405b46;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .error {
            color: #c0392b;
        }
    </style>
</head>
<body>

<div class="box">

    <?php if (isset($_GET['berhasil'])): ?>

        <h2>Checkout Berhasil!</h2>
        <p>Pesanan kamu berhasil dibuat.</p>

        <div class="nomor">
            Pesanan #<?= (int)$_GET['berhasil'] ?>
        </div>

        <p>Status: Menunggu Pembayaran</p>

        <a href="dashboard.php" class="btn">
            Kembali ke Dashboard
        </a>

    <?php else: ?>

        <h2>Checkout Gagal</h2>
        <p class="error">
            <?= htmlspecialchars(
                $pesan_error ?? 'Terjadi kesalahan.',
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <a href="keranjang.php" class="btn">
            Kembali ke Keranjang
        </a>

    <?php endif; ?>

</div>

</body>
</html>
