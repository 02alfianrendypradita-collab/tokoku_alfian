<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) die("ID tidak valid.");

$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nama FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaksi = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$t) die("Transaksi tidak ditemukan.");

$stmt = mysqli_prepare($conn, "
    SELECT dt.*, p.nama_produk
    FROM detail_transaksi dt
    JOIN produk p ON dt.id_produk = p.id_produk
    WHERE dt.id_transaksi = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

function rp($n) {
    return "Rp " . number_format((float)$n, 0, ',', '.');
}
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nota TokoKu</title>

<style>
* {box-sizing:border-box;font-family:Arial,sans-serif}
body {background:#eef3ed;padding:25px;color:#354b39}
.nota {
    max-width:550px;margin:auto;background:white;
    padding:30px;border-radius:15px;
    box-shadow:0 5px 25px #0001
}
.header {
    text-align:center;background:#405b46;
    color:white;padding:22px;border-radius:12px
}
.header h1 {font-size:28px}
.header p {font-size:12px;margin-top:5px}
h2 {font-size:19px;margin:25px 0 15px}
.info {
    background:#f3f7f1;padding:17px;
    border-radius:10px;line-height:1.8;
    font-size:13px
}
table {width:100%;border-collapse:collapse;margin-top:20px;font-size:12px}
th {background:#edf3eb;color:#405b46}
th,td {padding:12px 7px;text-align:left;border-bottom:1px solid #eee}
.right {text-align:right}
.total {
    background:#405b46;color:white;padding:18px;
    border-radius:10px;margin-top:20px;
    display:flex;justify-content:space-between;gap:10px;
    font-weight:bold
}
.total span {min-width:0}
.total span:last-child {text-align:right;white-space:nowrap}
.footer {text-align:center;margin-top:25px;font-size:12px;color:#89948b}
.actions {text-align:center;margin-top:20px}
button,a {
    padding:11px 18px;border:0;border-radius:8px;
    text-decoration:none;cursor:pointer;font-size:12px;
    background:#405b46;color:white
}
a {background:#edf3eb;color:#405b46}
@media(max-width:450px) {
    body {padding:10px}
    .nota {padding:17px}
}

/* ===== CETAK: lebar struk 80mm, memenuhi halaman ===== */
@page {
    size: 80mm auto;
    margin: 0;
}
@media print {
    html, body {
        width: 80mm;
        height: 100%;
        margin: 0;
        padding: 0;
        background: white;
    }
    .nota {
        width: 80mm;
        max-width: 80mm;
        min-height: 99vh;
        margin: 0;
        padding: 6mm 5mm;
        border-radius: 0;
        box-shadow: none;
        display: flex;
        flex-direction: column;
    }

    /* Header */
    .header {
        padding: 14px 10px;
        border-radius: 8px;
    }
    .header h1 {font-size: 24px; letter-spacing: 1px}
    .header p {font-size: 11px; margin-top: 4px}

    /* Judul */
    h2 {
        font-size: 15px;
        text-align: center;
        margin: 18px 0 12px;
        padding-bottom: 8px;
        border-bottom: 2px dashed #405b46;
        letter-spacing: .5px;
    }

    /* Info transaksi */
    .info {
        padding: 12px;
        font-size: 11.5px;
        line-height: 1.9;
        border-radius: 8px;
    }

    /* Tabel produk */
    table {
        margin-top: 16px;
        font-size: 11.5px;
        table-layout: fixed;
    }
    th {padding: 9px 5px; font-size: 11px}
    td {
        padding: 11px 5px;
        border-bottom: 1px dashed #c9d3ca;
        vertical-align: top;
    }
    th, td {word-wrap: break-word; overflow-wrap: anywhere}
    th:nth-child(1), td:nth-child(1) {width: 46%}
    th:nth-child(2), td:nth-child(2) {width: 12%; text-align: center}
    th:nth-child(3), td:nth-child(3) {width: 42%}

    /* Total */
    .total {
        padding: 13px 12px;
        margin-top: 18px;
        border-radius: 8px;
        font-size: 13px;
    }

    /* Footer turun ke dasar halaman */
    .footer {
        margin-top: auto !important;
        padding-top: 24px;
        font-size: 11px;
    }
    .footer::before {
        content: "";
        display: block;
        border-top: 2px dashed #405b46;
        margin-bottom: 14px;
    }
    .footer b {font-size: 12px; color: #405b46}
    .footer p {margin-top: 5px}

    .actions {display: none}
    tr, .total, .footer {page-break-inside: avoid; break-inside: avoid}
    .header, .total, th {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
</head>

<body>
<div class="nota">

    <div class="header">
        <h1>TokoKu.</h1>
        <p>Belanja Mudah dan Nyaman</p>
    </div>

    <h2>Nota Pembelian</h2>

    <div class="info">
        <b>No. Transaksi:</b> #<?= $id ?><br>
        <b>Nama:</b> <?= e($t['nama']) ?><br>
        <b>Tanggal:</b> <?= date('d-m-Y H:i', strtotime($t['tanggal_transaksi'])) ?><br>
        <b>Status:</b> <?= e($t['status']) ?><br>
        <b>Pembayaran:</b> <?= e($t['metode_pembayaran'] ?? '-') ?>
    </div>

    <table>
        <tr>
            <th>Produk</th>
            <th>Qty</th>
            <th class="right">Subtotal</th>
        </tr>

        <?php while($item = mysqli_fetch_assoc($items)): ?>
        <tr>
            <td><?= e($item['nama_produk']) ?></td>
            <td><?= (int)$item['jumlah'] ?></td>
            <td class="right">
                <?= rp($item['harga'] * $item['jumlah']) ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="total">
        <span>Total Pembayaran</span>
        <span><?= rp($t['total_harga']) ?></span>
    </div>

    <div class="footer">
        <b>Terima Kasih Telah Berbelanja!</b>
        <p>TokoKu - Belanja Mudah dan Nyaman</p>
    </div>

    <div class="actions">
        <button onclick="window.print()">Cetak Nota</button>
        <a href="nota_admin.php">Kembali</a>
    </div>

</div>
</body>
</html>