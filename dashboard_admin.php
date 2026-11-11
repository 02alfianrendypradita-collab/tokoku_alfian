
<?php
session_start();
require_once 'koneksi.php';

// Cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Cek hak akses admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Menghitung jumlah produk
$query_produk = mysqli_query($conn, "SELECT COUNT(*) AS total FROM produk");
$data_produk = mysqli_fetch_assoc($query_produk);
$total_produk = $data_produk['total'] ?? 0;

// Menghitung jumlah transaksi
$query_transaksi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi");
$data_transaksi = mysqli_fetch_assoc($query_transaksi);
$total_transaksi = $data_transaksi['total'] ?? 0;

// Menghitung total penjualan
$query_penjualan = mysqli_query($conn, "SELECT COALESCE(SUM(total_harga), 0) AS total FROM transaksi");
$data_penjualan = mysqli_fetch_assoc($query_penjualan);
$total_penjualan = $data_penjualan['total'] ?? 0;

// Menghitung jumlah pengguna
$query_user = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$data_user = mysqli_fetch_assoc($query_user);
$total_user = $data_user['total'] ?? 0;

// Mengambil transaksi terbaru
$query_terbaru = mysqli_query(
    $conn,
    "SELECT id_transaksi, id_user, tanggal_transaksi,
            total_harga, status, metode_pembayaran
     FROM transaksi
     ORDER BY id_transaksi DESC
     LIMIT 5"
);

$nama_admin = $_SESSION['nama'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - TokoKu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f7f8f4;
            color: #344638;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: #ffffff;
            padding: 28px 20px;
            position: fixed;
            height: 100vh;
            box-shadow: 4px 0 25px rgba(50, 75, 55, .05);
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-size: 27px;
            font-weight: 800;
            color: #405b46;
            text-decoration: none;
            padding: 0 12px;
            margin-bottom: 8px;
        }

        .brand span {
            color: #91aa91;
        }

        .admin-label {
            font-size: 11px;
            color: #9ba59a;
            letter-spacing: 2px;
            padding: 0 13px;
            margin-bottom: 35px;
        }

        .menu-title {
            font-size: 11px;
            font-weight: 700;
            color: #a4ada2;
            letter-spacing: 1.5px;
            padding: 0 13px;
            margin-bottom: 13px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            text-decoration: none;
            color: #788579;
            font-size: 14px;
            font-weight: 600;
            padding: 14px;
            border-radius: 12px;
            transition: .3s;
        }

        .menu a .icon {
            font-size: 19px;
            width: 23px;
            text-align: center;
        }

        .menu a:hover,
        .menu a.active {
            background: #edf4ec;
            color: #405b46;
            transform: translateX(3px);
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #edf0eb;
        }

        .logout {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #c46c60;
            font-size: 14px;
            font-weight: 700;
            padding: 13px;
            border-radius: 12px;
            transition: .3s;
        }

        .logout:hover {
            background: #fff0ed;
        }

        /* MAIN */
        .main {
            width: calc(100% - 260px);
            margin-left: 260px;
            padding: 30px 38px;
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            gap: 20px;
        }

        .topbar h1 {
            color: #405b46;
            font-size: 26px;
        }

        .topbar p {
            color: #99a196;
            font-size: 13px;
            margin-top: 5px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 9px 16px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(50,75,55,.04);
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #829b83, #405b46);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
        }

        .profile-info strong {
            display: block;
            font-size: 13px;
            color: #405b46;
        }

        .profile-info span {
            color: #9ba59a;
            font-size: 11px;
        }

        /* WELCOME */
        .welcome {
            background: linear-gradient(120deg, #405b46, #829b83);
            border-radius: 22px;
            padding: 30px 35px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(64,91,70,.15);
        }

        .welcome::after {
            content: "✦";
            position: absolute;
            right: 55px;
            top: -40px;
            font-size: 180px;
            opacity: .09;
        }

        .welcome h2 {
            font-size: 24px;
            margin-bottom: 9px;
        }

        .welcome p {
            font-size: 13px;
            opacity: .85;
        }

        .welcome .tag {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 14px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 30px;
            font-size: 11px;
        }

        /* STATISTIK */
        .section-title {
            font-size: 18px;
            color: #405b46;
            margin-bottom: 18px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(50,75,55,.04);
            transition: .3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(50,75,55,.09);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .green {
            background: #edf5ec;
        }

        .peach {
            background: #fff0e9;
        }

        .blue {
            background: #edf3f8;
        }

        .yellow {
            background: #fff7e8;
        }

        .stat-card p {
            color: #98a196;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .stat-card h3 {
            font-size: 25px;
            color: #405b46;
        }

        /* MENU CEPAT */
        .quick-menu {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .quick-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-decoration: none;
            color: #405b46;
            box-shadow: 0 5px 20px rgba(50,75,55,.04);
            transition: .3s;
            border: 1px solid transparent;
        }

        .quick-card:hover {
            border-color: #c7d8c5;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(50,75,55,.08);
        }

        .quick-icon {
            font-size: 25px;
            margin-bottom: 14px;
        }

        .quick-card h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }

        .quick-card p {
            color: #9ba59a;
            font-size: 11px;
            line-height: 1.5;
        }

        /* TABEL TRANSAKSI */
        .table-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 5px 20px rgba(50,75,55,.04);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
        }

        .table-header h2 {
            color: #405b46;
            font-size: 17px;
        }

        .table-header a {
            color: #829b83;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th {
            text-align: left;
            padding: 14px;
            font-size: 11px;
            color: #9ba59a;
            background: #f8faf6;
            font-weight: 700;
        }

        td {
            padding: 15px 14px;
            font-size: 12px;
            color: #627063;
            border-bottom: 1px solid #f0f2ee;
        }

        tr:last-child td {
            border-bottom: none;
        }

       .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        .status-menunggu {
            background: #fff4df;
            color: #b58b43;
        }

        .status-dibayar {
            background: #e2f6e7;
            color: #238344;
        }

        .status-selesai {
            background: #e2f6e7;
            color: #238344;
        }

        .status-dibatalkan {
            background: #fff0ed;
            color: #c46c60;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #9ba59a;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            color: #a1aaa0;
            font-size: 11px;
            padding: 25px 0 5px;
        }

        /* RESPONSIVE */
        @media(max-width: 1100px) {
            .stats,
            .quick-menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 750px) {
            .sidebar {
                width: 75px;
                padding: 22px 10px;
            }

            .brand {
                font-size: 18px;
                padding: 0;
                text-align: center;
            }

            .admin-label,
            .menu-title,
            .menu a span:not(.icon),
            .logout span:not(.icon) {
                display: none;
            }

            .menu a,
            .logout {
                justify-content: center;
                padding: 14px 5px;
            }

            .menu a .icon {
                font-size: 21px;
            }

            .main {
                width: calc(100% - 75px);
                margin-left: 75px;
                padding: 22px 15px;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 21px;
            }

            .profile {
                padding: 7px;
            }

            .profile-info {
                display: none;
            }

            .welcome {
                padding: 25px 20px;
            }

            .welcome h2 {
                font-size: 20px;
            }
        }

        @media(max-width: 450px) {
            .stats,
            .quick-menu {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-card h3 {
                font-size: 19px;
            }

            .quick-card {
                padding: 15px;
            }

            .quick-card h3 {
                font-size: 12px;
            }

            .table-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <a href="dashboard_admin.php" class="brand">
            Toko<span>Ku.</span>
        </a>

        <div class="admin-label">ADMIN PANEL</div>

        <div class="menu-title">MENU UTAMA</div>

        <nav class="menu">

            <a href="dashboard_admin.php" class="active">
                <span class="icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="tambah_produk.php">
                <span class="icon">＋</span>
                <span>Tambah Barang</span>
            </a>

            <a   a href="produk_admin.php">
                <span class="icon">▣</span>
                <span>Lihat Produk</span>
            </a>

            <a href="hapus_produk.php">
                <span class="icon">▣</span>
                <span>Hapus Barang</span>
            </a>

            <a href="laporan_admin.php">
                <span class="icon">▤</span>
                <span>Laporan</span>
            </a>

            <a href="nota_admin.php">
                <span class="icon">▧</span>
                <span>Nota Pembelian</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="logout.php" class="logout">
                <span class="icon">↪</span>
                <span>Keluar</span>
            </a>

        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- TOPBAR -->
        <header class="topbar">

            <div>
                <h1>Dashboard Admin</h1>
                <p>Selamat datang di halaman pengelolaan TokoKu.</p>
            </div>

            <div class="profile">
                <div class="avatar">
                    <?= strtoupper(substr($nama_admin, 0, 1)) ?>
                </div>

                <div class="profile-info">
                    <strong><?= htmlspecialchars($nama_admin) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

        </header>

        <!-- WELCOME -->
        <section class="welcome">

            <h2>Halo, <?= htmlspecialchars($nama_admin) ?>! 👋</h2>

            <p>
                Kelola produk, pantau transaksi, dan lihat perkembangan
                penjualan toko melalui dashboard ini.
            </p>

            <span class="tag">✦ TokoKu Administration System</span>

        </section>

        <!-- STATISTIK -->
        <h2 class="section-title">Ringkasan Toko</h2>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon green">📦</div>
                </div>

                <p>Total Barang</p>
                <h3><?= number_format($total_produk) ?></h3>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon peach">🛍️</div>
                </div>

                <p>Total Transaksi</p>
                <h3><?= number_format($total_transaksi) ?></h3>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon blue">👥</div>
                </div>

                <p>Total Pengguna</p>
                <h3><?= number_format($total_user) ?></h3>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon yellow">💰</div>
                </div>

                <p>Total Penjualan</p>
                <h3 style="font-size:19px;">
                    Rp <?= number_format($total_penjualan, 0, ',', '.') ?>
                </h3>
            </div>

        </section>

        <!-- MENU CEPAT -->
        <h2 class="section-title">Pengelolaan Toko</h2>

        <section class="quick-menu">

            <a href="tambah_produk.php" class="quick-card">
                <div class="quick-icon">➕</div>
                <h3>Tambah Barang</h3>
                <p>Tambahkan produk baru ke dalam toko.</p>
            </a>

            <a href="hapus_produk.php" class="quick-card">
                <div class="quick-icon">🗑️</div>
                <h3>Hapus Barang</h3>
                <p>Kelola dan hapus produk yang tersedia.</p>
            </a>

            <a href="laporan.php" class="quick-card">
                <div class="quick-icon">📊</div>
                <h3>Laporan</h3>
                <p>Pantau hasil penjualan toko.</p>
            </a>

            <a href="nota.php" class="quick-card">
                <div class="quick-icon">🧾</div>
                <h3>Nota Pembelian</h3>
                <p>Lihat daftar nota transaksi pelanggan.</p>
            </a>

        </section>

        <!-- TRANSAKSI TERBARU -->
        <section class="table-card">

            <div class="table-header">

                <h2>Transaksi Terbaru</h2>

                <a href="nota_admin.php">Lihat Semua →</a>

            </div>

            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>ID TRANSAKSI</th>
                            <th>ID PELANGGAN</th>
                            <th>TANGGAL</th>
                            <th>PEMBAYARAN</th>
                            <th>TOTAL</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($query_terbaru && mysqli_num_rows($query_terbaru) > 0): ?>

                            <?php while ($transaksi = mysqli_fetch_assoc($query_terbaru)): ?>

                                <tr>

                                    <td>
                                        #<?= htmlspecialchars($transaksi['id_transaksi']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($transaksi['id_user']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($transaksi['tanggal_transaksi']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($transaksi['metode_pembayaran']) ?>
                                    </td>

                                    <td>
                                        Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        <span class="status">
                                            <?= htmlspecialchars($transaksi['status']) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty">
                                    Belum ada transaksi pembelian.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

        <div class="footer">
            © 2026 TokoKu. Admin Dashboard.
        </div>

    </main>

</div>

</body>
</html>
