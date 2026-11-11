
<?php
session_start();
require_once 'koneksi.php';

// Cek login admin
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login_admin.php");
    exit;
}

// Membuat token keamanan
if (!isset($_SESSION['csrf_pembayaran'])) {
    $_SESSION['csrf_pembayaran'] = bin2hex(random_bytes(32));
}

// Ambil notifikasi
$pesan = $_SESSION['pesan_pembayaran'] ?? '';
unset($_SESSION['pesan_pembayaran']);

// Ambil data transaksi
$query = mysqli_query($conn, "
    SELECT
        t.id_transaksi,
        t.tanggal_transaksi,
        t.total_harga,
        t.metode_pembayaran,
        t.status,
        u.nama
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.id_transaksi DESC
");

if (!$query) {
    die("Gagal mengambil data transaksi: " . mysqli_error($conn));
}

function aman($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nota Pembelian - Admin TokoKu</title>

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

.container {
    max-width: 1250px;
    margin: auto;
    padding: 35px 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.header h1 {
    color: #405b46;
    font-size: 27px;
}

.header p {
    color: #99a196;
    font-size: 13px;
    margin-top: 6px;
}

.btn {
    display: inline-block;
    padding: 11px 17px;
    background: #405b46;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    border: none;
    cursor: pointer;
    transition: .3s;
}

.btn:hover {
    background: #294b39;
}

.btn-detail {
    background: #edf4ec;
    color: #405b46;
}

.btn-detail:hover {
    background: #dce9da;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(50,75,55,.05);
}

.card h2 {
    font-size: 18px;
    color: #405b46;
    margin-bottom: 20px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 15px;
    background: #f0f5ef;
    color: #526a57;
    font-size: 12px;
    white-space: nowrap;
}

td {
    padding: 15px;
    border-bottom: 1px solid #edf1ec;
    font-size: 13px;
    color: #627063;
    white-space: nowrap;
}

.status {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 11px;
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

.status-lain {
    background: #edf0f1;
    color: #64748b;
}

.btn-bayar {
    background: linear-gradient(135deg, #269b55, #176b39);
    color: white;
    border: none;
    padding: 10px 13px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: .3s;
}

.btn-bayar:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(22, 163, 74, .2);
}

.sudah-bayar {
    display: inline-block;
    background: #e2f6e7;
    color: #238344;
    padding: 9px 12px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.notifikasi {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    background: #e2f6e7;
    color: #238344;
    font-size: 13px;
    font-weight: 600;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #99a196;
}

@media(max-width: 600px) {
    .container {
        padding: 22px 14px;
    }

    .header h1 {
        font-size: 23px;
    }

    .card {
        padding: 16px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="header">
        <div>
            <h1>Nota Pembelian</h1>
            <p>Daftar seluruh transaksi pembelian pelanggan TokoKu.</p>
        </div>

        <a href="dashboard_admin.php" class="btn">
            ← Dashboard
        </a>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="notifikasi">
            ✓ <?= aman($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <h2>Riwayat Transaksi Pelanggan</h2>

        <div class="table-wrapper">
            <table>

                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Transaksi</th>
                        <th>Nama Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total Pembelian</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Nota</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($query) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                    <tr>

                        <td><?= $no++ ?></td>

                        <td>
                            #<?= aman($row['id_transaksi']) ?>
                        </td>

                        <td>
                            <?= aman($row['nama']) ?>
                        </td>

                        <td>
                            <?= date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])) ?>
                        </td>

                        <td>
                            <?= rupiah($row['total_harga']) ?>
                        </td>

                        <td>
                            <?= aman($row['metode_pembayaran']) ?>
                        </td>

                        <td>
                            <?php if ($row['status'] === 'menunggu'): ?>

                                <span class="status status-menunggu">
                                    Menunggu
                                </span>

                            <?php elseif ($row['status'] === 'dibayar'): ?>

                                <span class="status status-dibayar">
                                    Dibayar
                                </span>

                            <?php else: ?>

                                <span class="status status-lain">
                                    <?= aman(ucfirst($row['status'])) ?>
                                </span>

                            <?php endif; ?>
                        </td>

                        <td>

                            <?php if ($row['status'] === 'menunggu'): ?>

                                <form
                                    action="proses_pembayaran.php"
                                    method="POST"
                                    onsubmit="return confirm('Apakah pembayaran transaksi ini sudah benar-benar diterima?')"
                                >

                                    <input
                                        type="hidden"
                                        name="id_transaksi"
                                        value="<?= (int)$row['id_transaksi'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= aman($_SESSION['csrf_pembayaran']) ?>"
                                    >

                                    <button type="submit" class="btn-bayar">
                                        ✓ Selesaikan Pembayaran
                                    </button>

                                </form>

                            <?php elseif ($row['status'] === 'dibayar'): ?>

                                <span class="sudah-bayar">
                                    ✓ Sudah Dibayar
                                </span>

                            <?php else: ?>

                                <span>-</span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <a
                                href="nota_detail_admin.php?id=<?= (int)$row['id_transaksi'] ?>"
                                class="btn btn-detail"
                                target="_blank"
                            >
                                Lihat Nota
                            </a>
                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="9" class="empty">
                            Belum ada transaksi pembelian.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>
        </div>

    </div>

</div>

</body>
</html>
