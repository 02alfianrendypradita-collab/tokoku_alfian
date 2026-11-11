

<?php
session_start();
require_once "koneksi.php";

// Cek login admin
if (!isset($_SESSION['id_user'])) {
    header("Location: login_admin.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Filter tanggal
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Validasi tanggal
if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_awal) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_akhir) ||
    $tanggal_awal > $tanggal_akhir
) {
    $tanggal_awal = date('Y-m-01');
    $tanggal_akhir = date('Y-m-d');
}

// Ringkasan laporan
$sql_ringkasan = "
    SELECT
        COUNT(*) AS total_transaksi,
        COALESCE(SUM(total_harga), 0) AS total_pendapatan
    FROM transaksi
    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
";

$stmt = mysqli_prepare($conn, $sql_ringkasan);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$ringkasan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$total_transaksi = $ringkasan['total_transaksi'] ?? 0;
$total_pendapatan = $ringkasan['total_pendapatan'] ?? 0;

// Total produk terjual
$sql_terjual = "
    SELECT COALESCE(SUM(dt.jumlah), 0) AS total_terjual
    FROM detail_transaksi dt
    JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
    WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
";

$stmt = mysqli_prepare($conn, $sql_terjual);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$data_terjual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$total_terjual = $data_terjual['total_terjual'] ?? 0;

// Produk terlaris
$sql_terlaris = "
    SELECT
        p.nama_produk,
        SUM(dt.jumlah) AS jumlah_terjual
    FROM detail_transaksi dt
    JOIN transaksi t ON dt.id_transaksi = t.id_transaksi
    JOIN produk p ON dt.id_produk = p.id_produk
    WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    GROUP BY p.id_produk, p.nama_produk
    ORDER BY jumlah_terjual DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $sql_terlaris);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$produk_terlaris = mysqli_stmt_get_result($stmt);

// Riwayat transaksi
$sql_transaksi = "
    SELECT
        t.id_transaksi,
        t.tanggal_transaksi,
        t.total_harga,
        t.status,
        u.nama
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    ORDER BY t.tanggal_transaksi DESC
";

$stmt = mysqli_prepare($conn, $sql_transaksi);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_awal, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$data_transaksi = mysqli_stmt_get_result($stmt);

// Format rupiah
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

function aman($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Admin - TokoKu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f5f7f3;
            color: #344238;
        }

        .container {
            max-width: 1250px;
            margin: auto;
            padding: 35px 25px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #294b39;
        }

        .header p {
            color: #89948b;
            margin-top: 6px;
        }

        .btn {
            border: none;
            padding: 11px 18px;
            border-radius: 10px;
            background: #416b51;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            transition: .3s;
        }

        .btn:hover {
            background: #294b39;
            transform: translateY(-2px);
        }

        .btn-light {
            background: #e8eee8;
            color: #416b51;
        }

        .btn-light:hover {
            background: #d9e5da;
            color: #294b39;
        }

        .filter-box {
            background: white;
            padding: 22px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(40, 70, 50, .05);
        }

        .filter-box h3 {
            margin-bottom: 15px;
            color: #365440;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 13px;
            color: #718076;
        }

        .form-group input {
            padding: 11px;
            border: 1px solid #dce5dc;
            border-radius: 9px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #648d6d;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: white;
            padding: 23px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(40, 70, 50, .05);
            border: 1px solid #edf1ec;
        }

        .summary-icon {
            width: 45px;
            height: 45px;
            border-radius: 13px;
            background: #eaf2e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .summary-card p {
            color: #89948b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .summary-card h2 {
            color: #31543c;
            font-size: 25px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .panel {
            background: white;
            padding: 23px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(40, 70, 50, .05);
            border: 1px solid #edf1ec;
        }

        .panel h3 {
            color: #365440;
            margin-bottom: 20px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #edf1ec;
            gap: 12px;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: 600;
            color: #405449;
        }

        .product-qty {
            background: #eaf2e9;
            color: #416b51;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table-panel {
            background: white;
            padding: 23px;
            border-radius: 17px;
            box-shadow: 0 5px 20px rgba(40, 70, 50, .05);
            border: 1px solid #edf1ec;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .table-header h3 {
            color: #365440;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th {
            background: #f0f5ef;
            color: #526a57;
            font-size: 13px;
            text-align: left;
            padding: 15px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #edf1ec;
            font-size: 13px;
            color: #59685e;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            background: #eaf2e9;
            color: #416b51;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #929d94;
        }

        .footer {
            text-align: center;
            color: #9aa49b;
            font-size: 12px;
            margin-top: 30px;
        }

        @media(max-width: 750px) {
            .summary {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 23px;
            }

            .container {
                padding: 22px 15px;
            }
        }

        @media print {
            body {
                background: white;
            }

            .filter-box,
            .header .actions,
            .footer {
                display: none !important;
            }

            .container {
                max-width: 100%;
                padding: 0;
            }

            .summary-card,
            .panel,
            .table-panel {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h1>Laporan Penjualan</h1>
            <p>Ringkasan aktivitas penjualan TokoKu</p>
        </div>

        <div class="actions">
            <a href="dashboard_admin.php" class="btn btn-light">
                ← Dashboard
            </a>

            <button onclick="window.print()" class="btn">
                🖨 Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Filter tanggal -->
    <div class="filter-box">
        <h3>Filter Periode Laporan</h3>

        <form method="GET" class="filter-form">

            <div class="form-group">
                <label>Tanggal Awal</label>
                <input
                    type="date"
                    name="tanggal_awal"
                    value="<?= aman($tanggal_awal) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Tanggal Akhir</label>
                <input
                    type="date"
                    name="tanggal_akhir"
                    value="<?= aman($tanggal_akhir) ?>"
                    required
                >
            </div>

            <button type="submit" class="btn">
                Tampilkan Laporan
            </button>

            <a href="laporan_admin.php" class="btn btn-light">
                Reset
            </a>

        </form>
    </div>

    <!-- Ringkasan -->
    <div class="summary">

        <div class="summary-card">
            <div class="summary-icon">💰</div>
            <p>Total Pendapatan</p>
            <h2><?= rupiah($total_pendapatan) ?></h2>
        </div>

        <div class="summary-card">
            <div class="summary-icon">🧾</div>
            <p>Total Transaksi</p>
            <h2><?= number_format($total_transaksi) ?></h2>
        </div>

        <div class="summary-card">
            <div class="summary-icon">📦</div>
            <p>Produk Terjual</p>
            <h2><?= number_format($total_terjual) ?></h2>
        </div>

    </div>

    <!-- Produk terlaris -->
    <div class="content-grid">

        <div class="panel">
            <h3>🏆 Produk Terlaris</h3>

            <?php if (mysqli_num_rows($produk_terlaris) > 0): ?>

                <?php while ($produk = mysqli_fetch_assoc($produk_terlaris)): ?>

                    <div class="product-item">
                        <span class="product-name">
                            <?= aman($produk['nama_produk']) ?>
                        </span>

                        <span class="product-qty">
                            <?= number_format($produk['jumlah_terjual']) ?> Terjual
                        </span>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty">
                    Belum ada produk terjual pada periode ini.
                </div>

            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>📊 Informasi Laporan</h3>

            <div class="product-item">
                <span>Periode Awal</span>
                <strong><?= date('d-m-Y', strtotime($tanggal_awal)) ?></strong>
            </div>

            <div class="product-item">
                <span>Periode Akhir</span>
                <strong><?= date('d-m-Y', strtotime($tanggal_akhir)) ?></strong>
            </div>

            <div class="product-item">
                <span>Total Pendapatan</span>
                <strong><?= rupiah($total_pendapatan) ?></strong>
            </div>
        </div>

    </div>

    <!-- Tabel transaksi -->
    <div class="table-panel">

        <div class="table-header">
            <h3>Riwayat Transaksi</h3>
            <span class="product-qty">
                <?= number_format($total_transaksi) ?> Transaksi
            </span>
        </div>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Transaksi</th>
                        <th>Nama Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($data_transaksi) > 0): ?>

                        <?php $no = 1; ?>

                        <?php while ($transaksi = mysqli_fetch_assoc($data_transaksi)): ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    #<?= aman($transaksi['id_transaksi']) ?>
                                </td>

                                <td>
                                    <?= aman($transaksi['nama']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd-m-Y H:i',
                                        strtotime($transaksi['tanggal_transaksi'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= rupiah($transaksi['total_harga']) ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= aman(ucfirst($transaksi['status'])) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty">
                                Belum ada transaksi pada periode ini.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

    <div class="footer">
        © <?= date('Y') ?> TokoKu — Laporan Admin
    </div>

</div>

</body>
</html>
