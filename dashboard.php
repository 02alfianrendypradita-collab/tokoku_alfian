
<?php
session_start();
require_once 'koneksi.php';

// Proteksi halaman pembeli
if (!isset($_SESSION['id_user'])) {
    header("Location: login_pembeli.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: login_admin.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama = $_SESSION['nama'] ?? 'Pembeli';

// Menghitung jumlah produk
$q_produk = mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM produk
");
$total_produk = mysqli_fetch_assoc($q_produk)['total'] ?? 0;

// Menghitung jumlah transaksi pembeli
$q_transaksi = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM transaksi
    WHERE id_user = $id_user
");
$total_transaksi = mysqli_fetch_assoc($q_transaksi)['total'] ?? 0;

// Menghitung jumlah keranjang
$q_keranjang = mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah), 0) AS total
    FROM keranjang
    WHERE id_user = $id_user
");
$total_keranjang = mysqli_fetch_assoc($q_keranjang)['total'] ?? 0;

// Mengambil data produk
$q_produk_list = mysqli_query($conn, "
    SELECT * FROM produk
    ORDER BY id_produk DESC
");

// Mengambil riwayat transaksi pembeli
$q_riwayat = mysqli_query($conn, "
    SELECT
        t.*,
        COUNT(d.id_detail) AS jumlah_item
    FROM transaksi t
    LEFT JOIN detail_transaksi d
        ON t.id_transaksi = d.id_transaksi
    WHERE t.id_user = $id_user
    GROUP BY t.id_transaksi
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 10
");

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pembeli - TokoKu</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    background: #f6f8f4;
    color: #344638;
}

/* SIDEBAR */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #405b46, #304735);
    padding: 28px 18px;
    color: white;
    z-index: 100;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 30px;
    border-bottom: 1px solid rgba(255,255,255,.15);
}

.brand-icon {
    width: 43px;
    height: 43px;
    background: #829b83;
    border-radius: 13px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 23px;
}

.brand h2 {
    font-size: 22px;
}

.brand p {
    font-size: 11px;
    color: #cbd9ca;
}

.menu-title {
    margin: 30px 12px 12px;
    font-size: 11px;
    color: #b8c9b8;
    font-weight: bold;
    letter-spacing: 1px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 14px 15px;
    margin-bottom: 7px;
    color: #dce8dc;
    text-decoration: none;
    border-radius: 12px;
    font-size: 14px;
    transition: .3s;
}

.menu a i {
    font-size: 19px;
}

.menu a:hover,
.menu a.active {
    background: rgba(255,255,255,.15);
    color: white;
}

.menu a.active {
    border-left: 4px solid #b7d0b5;
}

.logout {
    position: absolute;
    bottom: 25px;
    left: 18px;
    right: 18px;
}

.logout a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 12px;
    color: #ffe1dc;
    background: rgba(255,255,255,.09);
    text-decoration: none;
}

/* MAIN */
.main {
    margin-left: 250px;
    padding: 30px 35px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.topbar h2 {
    font-size: 25px;
    color: #405b46;
}

.topbar p {
    margin-top: 5px;
    color: #899087;
    font-size: 13px;
}

.profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-icon {
    width: 43px;
    height: 43px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5eee2;
    color: #405b46;
    font-size: 21px;
}

.profile strong {
    font-size: 14px;
}

.profile small {
    display: block;
    color: #899087;
    margin-top: 3px;
}

/* WELCOME */
.welcome {
    background: linear-gradient(120deg, #829b83, #405b46);
    padding: 32px;
    border-radius: 20px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    margin-bottom: 27px;
    box-shadow: 0 12px 30px rgba(64,91,70,.13);
}

.welcome h2 {
    font-size: 26px;
    margin-bottom: 10px;
}

.welcome p {
    color: #e5eee2;
    font-size: 14px;
}

.welcome-icon {
    font-size: 80px;
    color: rgba(255,255,255,.2);
}

/* STATISTIK */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 35px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 17px;
    display: flex;
    align-items: center;
    gap: 17px;
    box-shadow: 0 5px 20px rgba(64,91,70,.05);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
    background: #edf3eb;
    color: #405b46;
}

.stat-card h3 {
    font-size: 23px;
    color: #405b46;
}

.stat-card p {
    color: #899087;
    font-size: 12px;
    margin-top: 4px;
}

/* SECTION */
.section {
    margin-bottom: 38px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 20px;
    color: #405b46;
}

.section-header p {
    color: #899087;
    font-size: 12px;
    margin-top: 5px;
}

.section-header a {
    color: #405b46;
    font-size: 13px;
    text-decoration: none;
    font-weight: 700;
}

/* PRODUK */
.product-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

.product-card {
    background: white;
    border-radius: 17px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(64,91,70,.05);
    transition: .3s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(64,91,70,.12);
}

.product-image {
    height: 175px;
    background: #edf3eb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    font-size: 45px;
    color: #829b83;
}

.product-info {
    padding: 17px;
}

.product-info h3 {
    font-size: 14px;
    color: #405b46;
    margin-bottom: 8px;
}

.product-info .price {
    font-size: 16px;
    color: #405b46;
    font-weight: 800;
    margin-bottom: 8px;
}

.product-info .stock {
    font-size: 12px;
    color: #899087;
    margin-bottom: 14px;
}

.btn-cart {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #829b83, #405b46);
    padding: 10px;
    border-radius: 10px;
    color: white;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
    transition: .3s;
}

.btn-cart:hover {
    color: white;
    opacity: .9;
}

.btn-cart.disabled {
    background: #a6ada5;
    pointer-events: none;
}

/* RIWAYAT */
.history-card {
    background: white;
    border-radius: 17px;
    padding: 22px;
    box-shadow: 0 5px 20px rgba(64,91,70,.05);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    padding: 15px;
    text-align: left;
    background: #f4f7f1;
    color: #405b46;
    font-size: 12px;
}

td {
    padding: 16px 15px;
    border-bottom: 1px solid #edf0eb;
    font-size: 13px;
    color: #626e62;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    background: #edf3eb;
    color: #405b46;
    font-size: 11px;
    font-weight: bold;
}

.empty {
    text-align: center;
    padding: 40px 15px;
    color: #899087;
}

.empty i {
    font-size: 40px;
    display: block;
    margin-bottom: 12px;
}

.btn-detail {
    text-decoration: none;
    color: #405b46;
    font-weight: bold;
}

@media(max-width: 1100px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media(max-width: 850px) {
    .sidebar {
        width: 75px;
        padding: 20px 10px;
    }

    .brand {
        justify-content: center;
        padding: 0 0 25px;
    }

    .brand-text,
    .menu-title,
    .menu a span,
    .logout span {
        display: none;
    }

    .menu a,
    .logout a {
        justify-content: center;
        padding: 14px 5px;
    }

    .main {
        margin-left: 75px;
        padding: 25px 20px;
    }

    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 550px) {
    .main {
        padding: 20px 12px;
    }

    .topbar h2 {
        font-size: 19px;
    }

    .profile strong {
        font-size: 12px;
    }

    .welcome {
        padding: 23px;
    }

    .welcome h2 {
        font-size: 20px;
    }

    .welcome-icon {
        font-size: 48px;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .product-image {
        height: 130px;
    }

    .product-info {
        padding: 12px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="brand">
        <div class="brand-icon">
            <i class="bi bi-bag-heart-fill"></i>
        </div>

        <div class="brand-text">
            <h2>TokoKu</h2>
            <p>Customer Panel</p>
        </div>
    </div>

    <div class="menu-title">MENU UTAMA</div>

    <div class="menu">
        <a href="dashboard.php" class="active">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <a href="#produk">
            <i class="bi bi-shop"></i>
            <span>Produk</span>
        </a>

        <a href="keranjang.php">
            <i class="bi bi-cart3"></i>
            <span>Keranjang</span>
        </a>

        <a href="#riwayat">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Pembelian</span>
        </a>
    </div>

    <div class="logout">
        <a href="logout.php"
           onclick="return confirm('Yakin ingin keluar dari akun?')">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <h2>Dashboard Pembeli</h2>
            <p>Selamat datang kembali di TokoKu.</p>
        </div>

        <div class="profile">
            <div class="profile-icon">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>
                <strong><?= e($nama) ?></strong>
                <small>Pelanggan</small>
            </div>
        </div>
    </div>

    <!-- WELCOME -->
    <div class="welcome">
        <div>
            <h2>Halo, <?= e($nama) ?>! 👋</h2>
            <p>
                Selamat berbelanja di TokoKu.
                Temukan produk favoritmu hari ini!
            </p>
        </div>

        <div class="welcome-icon">
            <i class="bi bi-bag-heart-fill"></i>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <h3><?= $total_produk ?></h3>
                <p>Total Produk</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-cart3"></i>
            </div>
            <div>
                <h3><?= $total_keranjang ?></h3>
                <p>Barang di Keranjang</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <h3><?= $total_transaksi ?></h3>
                <p>Total Pembelian</p>
            </div>
        </div>

    </div>

    <!-- PRODUK -->
    <div class="section" id="produk">

        <div class="section-header">
            <div>
                <h2>Produk Pilihan</h2>
                <p>Temukan produk yang ingin kamu beli.</p>
            </div>

            <a href="produk.php">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="product-grid">

            <?php if ($q_produk_list && mysqli_num_rows($q_produk_list) > 0): ?>

                <?php while ($produk = mysqli_fetch_assoc($q_produk_list)): ?>

                    <div class="product-card">

                        <div class="product-image">

                            <?php if (!empty($produk['foto'])): ?>
                                <img
                                    src="<?= e($produk['foto']) ?>"
                                    alt="<?= e($produk['nama_produk']) ?>"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='block';"
                                >
                                <div class="no-image" style="display:none;">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="product-info">

                            <h3><?= e($produk['nama_produk']) ?></h3>

                            <div class="price">
                                <?= rupiah($produk['harga']) ?>
                            </div>

                            <div class="stock">
                                <i class="bi bi-box"></i>
                                Stok: <?= (int)$produk['stok'] ?>
                            </div>

                            <?php if ((int)$produk['stok'] > 0): ?>
                                <a href="keranjang.php?id_produk=<?= (int)$produk['id_produk'] ?>"
                                   class="btn-cart">
                                    <i class="bi bi-cart-plus"></i>
                                    Tambahkan ke Keranjang
                                </a>
                            <?php else: ?>
                                <span class="btn-cart disabled">
                                    Stok Habis
                                </span>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty" style="grid-column:1/-1;">
                    <i class="bi bi-box-seam"></i>
                    <p>Belum ada produk tersedia.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <!-- RIWAYAT PEMBELIAN -->
    <div class="section" id="riwayat">

        <div class="section-header">
            <div>
                <h2>Riwayat Pembelian</h2>
                <p>Daftar transaksi yang pernah kamu lakukan.</p>
            </div>
        </div>

        <div class="history-card">

            <?php if ($q_riwayat && mysqli_num_rows($q_riwayat) > 0): ?>

                <table>

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>ID Transaksi</th>
                            <th>Tanggal</th>
                            <th>Jumlah Item</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $no = 1; ?>

                        <?php while ($transaksi = mysqli_fetch_assoc($q_riwayat)): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    #<?= (int)$transaksi['id_transaksi'] ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd-m-Y',
                                        strtotime($transaksi['tanggal_transaksi'])
                                    )) ?>
                                </td>

                                <td>
                                    <?= (int)$transaksi['jumlah_item'] ?> item
                                </td>

                                <td>
                                    <strong>
                                        <?= rupiah($transaksi['total_harga']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= e(ucfirst($transaksi['status'])) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">
                    <i class="bi bi-receipt"></i>
                    <p>Belum ada riwayat pembelian.</p>
                    <p style="font-size:12px;margin-top:7px;">
                        Yuk, mulai belanja produk favoritmu!
                    </p>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <div style="text-align:center;padding:15px;color:#899087;font-size:12px;">
        &copy; 2026 TokoKu. Customer Dashboard.
    </div>

</div>

</body>
</html>
