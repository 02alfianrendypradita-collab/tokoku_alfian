
<?php
session_start();
require_once 'koneksi.php';

// Hanya admin yang boleh masuk
if (!isset($_SESSION['id_user'])) {
    header("Location: login_admin.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Ambil semua produk
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
    die("Gagal mengambil produk: " . mysqli_error($conn));
}

$totalProduk = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Admin TokoKu</title>

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
        }

        .navbar {
            background: white;
            padding: 20px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 15px rgba(64,91,70,.06);
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
        }

        .brand span {
            color: #829b83;
        }

        .admin {
            font-size: 13px;
            color: #829b83;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 22px;
        }

        .heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .heading h1 {
            font-size: 28px;
            margin-bottom: 7px;
        }

        .heading p {
            color: #899087;
            font-size: 14px;
        }

        .total {
            background: #eaf1e8;
            padding: 13px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .btn {
            padding: 12px 18px;
            border-radius: 11px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: .3s;
        }

        .btn-add {
            color: white;
            background: linear-gradient(135deg, #829b83, #405b46);
        }

        .btn-back {
            color: #405b46;
            background: #eaf1e8;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .product-card {
            background: white;
            border-radius: 19px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(64,91,70,.06);
            transition: .3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 30px rgba(64,91,70,.12);
        }

        .product-image {
            width: 100%;
            height: 210px;
            object-fit: cover;
            display: block;
            background: #edf2e9;
        }

        .no-image {
            width: 100%;
            height: 210px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2e9;
            color: #829b83;
            font-size: 14px;
        }

        .product-content {
            padding: 18px;
        }

        .category {
            display: inline-block;
            padding: 6px 10px;
            background: #edf3eb;
            color: #55745a;
            border-radius: 20px;
            font-size: 11px;
            margin-bottom: 12px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            min-height: 40px;
        }

        .price {
            color: #405b46;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .stock {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #edf0eb;
            font-size: 12px;
            color: #899087;
        }

        .stock strong {
            color: #405b46;
        }

        .empty {
            grid-column: 1 / -1;
            background: white;
            border-radius: 18px;
            padding: 60px 20px;
            text-align: center;
            color: #899087;
        }

        .empty h3 {
            color: #405b46;
            margin-bottom: 8px;
        }

        @media(max-width: 950px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media(max-width: 650px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 13px;
            }

            .product-image,
            .no-image {
                height: 160px;
            }

            .product-content {
                padding: 13px;
            }

            .heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .product-name {
                font-size: 14px;
            }

            .price {
                font-size: 15px;
            }
        }

        @media(max-width: 380px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="brand">Toko<span>Ku.</span></div>

    <div class="admin">
        ADMINISTRATOR
    </div>
</nav>

<div class="container">

    <div class="heading">
        <div>
            <h1>Katalog Produk</h1>
            <p>Lihat semua barang yang tersedia di TokoKu.</p>
        </div>

        <div class="total">
            Total Produk: <?= $totalProduk ?>
        </div>
    </div>

    <div class="actions">
        <a href="dashboard_admin.php" class="btn btn-back">
            ← Dashboard
        </a>

        <a href="tambah_produk.php" class="btn btn-add">
            + Tambah Barang
        </a>
    </div>

    <div class="product-grid">

        <?php if ($totalProduk > 0): ?>

            <?php while ($produk = mysqli_fetch_assoc($query)): ?>

                <div class="product-card">

                    <?php
                    $foto = $produk['foto'] ?? '';

                    $fotoAman = (
                        $foto !== '' &&
                        strpos($foto, 'uploads/produk/') === 0
                    );

                    $pathFoto = __DIR__ . '/' . $foto;

                    if (
                        $fotoAman &&
                        is_file($pathFoto)
                    ):
                    ?>

                        <img
                            src="<?= htmlspecialchars($foto) ?>"
                            alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                            class="product-image"
                        >

                    <?php else: ?>

                        <div class="no-image">
                            Foto belum tersedia
                        </div>

                    <?php endif; ?>

                    <div class="product-content">

                        <span class="category">
                            <?= htmlspecialchars($produk['nama_kategori'] ?? 'Tanpa Kategori') ?>
                        </span>

                        <div class="product-name">
                            <?= htmlspecialchars($produk['nama_produk']) ?>
                        </div>

                        <div class="price">
                            Rp <?= number_format((float)$produk['harga'], 0, ',', '.') ?>
                        </div>

                        <div class="stock">
                            <span>Stok barang</span>
                            <strong>
                                <?= (int)$produk['stok'] ?> unit
                            </strong>
                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <h3>Belum Ada Produk</h3>
                <p>Silakan tambahkan barang terlebih dahulu.</p>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
