
<?php
session_start();
require_once 'koneksi.php';

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user'])) {
    header("Location: login_admin.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Token keamanan
if (empty($_SESSION['csrf_hapus'])) {
    $_SESSION['csrf_hapus'] = bin2hex(random_bytes(32));
}

$pesan = "";
$jenisPesan = "";

// Proses hapus barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_produk = filter_input(
        INPUT_POST,
        'id_produk',
        FILTER_VALIDATE_INT
    );

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_hapus'], $token)
    ) {
        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenisPesan = "error";

    } elseif (!$id_produk || $id_produk <= 0) {
        $pesan = "ID barang tidak valid.";
        $jenisPesan = "error";

    } else {

        // Ambil data barang
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk, nama_produk, foto
             FROM produk
             WHERE id_produk = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$produk) {
            $pesan = "Barang tidak ditemukan.";
            $jenisPesan = "error";

        } else {

            // Cek apakah barang sudah pernah masuk transaksi
            $cek = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS jumlah
                 FROM detail_transaksi
                 WHERE id_produk = ?"
            );

            if (!$cek) {
                $pesan = "Gagal memeriksa riwayat transaksi: "
                    . mysqli_error($conn);
                $jenisPesan = "error";

            } else {

                mysqli_stmt_bind_param($cek, "i", $id_produk);
                mysqli_stmt_execute($cek);

                $hasilCek = mysqli_stmt_get_result($cek);
                $dataCek = mysqli_fetch_assoc($hasilCek);

                mysqli_stmt_close($cek);

                if ((int)$dataCek['jumlah'] > 0) {

                    $pesan = "Barang tidak bisa dihapus karena sudah memiliki riwayat transaksi. Pertahankan data barang agar riwayat pembelian tetap aman.";
                    $jenisPesan = "error";

                } else {

                    // Hapus barang dari database
                    $hapus = mysqli_prepare(
                        $conn,
                        "DELETE FROM produk WHERE id_produk = ?"
                    );

                    mysqli_stmt_bind_param($hapus, "i", $id_produk);

                    if (mysqli_stmt_execute($hapus)) {

                        // Hapus file foto jika berada di folder upload produk
                        $foto = $produk['foto'] ?? '';

                        if (
                            $foto !== '' &&
                            strpos($foto, 'uploads/produk/') === 0
                        ) {
                            $folder = realpath(
                                __DIR__ . '/uploads/produk'
                            );

                            $pathFoto = realpath(
                                __DIR__ . '/' . $foto
                            );

                            if (
                                $folder &&
                                $pathFoto &&
                                strpos(
                                    $pathFoto,
                                    $folder . DIRECTORY_SEPARATOR
                                ) === 0 &&
                                is_file($pathFoto)
                            ) {
                                unlink($pathFoto);
                            }
                        }

                        $pesan = "Barang berhasil dihapus!";
                        $jenisPesan = "success";

                    } else {

                        $pesan = "Gagal menghapus barang: "
                            . mysqli_stmt_error($hapus);
                        $jenisPesan = "error";
                    }

                    mysqli_stmt_close($hapus);
                }
            }
        }
    }
}

// Ambil daftar barang
$query = mysqli_query(
    $conn,
    "SELECT
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        p.foto,
        k.nama_kategori
     FROM produk p
     LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
     ORDER BY p.id_produk DESC"
);

if (!$query) {
    die("Gagal mengambil data barang: " . mysqli_error($conn));
}

$totalBarang = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hapus Barang - TokoKu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f5f7f2;
            color: #405b46;
            min-height: 100vh;
        }

        .navbar {
            height: 75px;
            padding: 0 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            box-shadow: 0 3px 15px rgba(64,91,70,.06);
        }

        .brand {
            font-size: 23px;
            font-weight: 800;
            color: #405b46;
        }

        .brand span {
            color: #829b83;
        }

        .admin-info {
            font-size: 13px;
            color: #829b83;
            font-weight: 600;
        }

        .container {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .heading h1 {
            font-size: 28px;
            margin-bottom: 7px;
        }

        .heading p {
            color: #899087;
            font-size: 14px;
        }

        .total-box {
            padding: 14px 20px;
            border-radius: 14px;
            background: #eaf1e8;
            color: #405b46;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .success {
            background: #eaf5e9;
            color: #357342;
        }

        .error {
            background: #fff0ed;
            color: #c45d4d;
        }

        .table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 12px 35px rgba(64,91,70,.06);
            overflow: hidden;
        }

        .table-header {
            margin-bottom: 22px;
        }

        .table-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .table-header p {
            font-size: 13px;
            color: #899087;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            padding: 15px 12px;
            background: #f5f8f3;
            color: #405b46;
            font-size: 13px;
            text-align: left;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #edf0eb;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #fcfdfb;
        }

        .product-image {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            object-fit: cover;
            background: #edf2e9;
        }

        .no-image {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2e9;
            color: #829b83;
            font-size: 11px;
        }

        .product-name {
            font-weight: 700;
            color: #405b46;
        }

        .category {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: #edf3eb;
            color: #405b46;
            font-size: 11px;
            font-weight: 600;
        }

        .stock {
            font-weight: 700;
        }

        .btn-delete {
            padding: 9px 15px;
            border: none;
            border-radius: 9px;
            background: #fff0ed;
            color: #c45d4d;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: .3s;
        }

        .btn-delete:hover {
            background: #c45d4d;
            color: white;
            transform: translateY(-2px);
        }

        .empty {
            text-align: center;
            padding: 55px 20px;
            color: #899087;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .back-button {
            display: inline-block;
            margin-top: 22px;
            padding: 12px 20px;
            border-radius: 11px;
            background: #edf2e9;
            color: #405b46;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: .3s;
        }

        .back-button:hover {
            background: #dfe9dc;
        }

        @media(max-width: 600px) {
            .navbar {
                padding: 0 20px;
            }

            .container {
                margin-top: 30px;
            }

            .heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .table-card {
                padding: 17px;
            }

            .admin-info {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="brand">Toko<span>Ku.</span></div>

    <div class="admin-info">
        Administrator:
        <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
    </div>
</nav>

<div class="container">

    <div class="heading">
        <div>
            <h1>Hapus Barang</h1>
            <p>Kelola dan hapus barang dari katalog TokoKu.</p>
        </div>

        <div class="total-box">
            Total Barang: <?= $totalBarang ?>
        </div>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert <?= $jenisPesan ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="table-card">

        <div class="table-header">
            <h3>Daftar Barang</h3>
            <p>Pilih barang yang ingin dihapus dari toko.</p>
        </div>

        <?php if ($totalBarang > 0): ?>

            <div class="table-responsive">

                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Foto</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php $no = 1; ?>

                    <?php while ($barang = mysqli_fetch_assoc($query)): ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?php if (!empty($barang['foto'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($barang['foto']) ?>"
                                        class="product-image"
                                        alt="Foto barang"
                                    >
                                <?php else: ?>
                                    <div class="no-image">No Foto</div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="product-name">
                                    <?= htmlspecialchars($barang['nama_produk']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="category">
                                    <?= htmlspecialchars($barang['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                            </td>

                            <td>
                                Rp <?= number_format((float)$barang['harga'], 0, ',', '.') ?>
                            </td>

                            <td class="stock">
                                <?= (int)$barang['stok'] ?>
                            </td>

                            <td>
                                <form
                                    method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$barang['id_produk'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_hapus']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                    >
                                        Hapus
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                    </tbody>
                </table>

            </div>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">📦</div>
                <h3>Belum Ada Barang</h3>
                <p>Belum ada barang yang ditambahkan ke katalog TokoKu.</p>
            </div>

        <?php endif; ?>

    </div>

    <a href="dashboard_admin.php" class="back-button">
        ← Kembali ke Dashboard
    </a>

</div>

</body>
</html>
