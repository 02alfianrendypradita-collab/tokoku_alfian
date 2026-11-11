
<?php
session_start();
require_once 'koneksi.php';

// Proteksi login
if (!isset($_SESSION['id_user'])) {
    header("Location: login_pembeli.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: login_pembeli.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama = $_SESSION['nama'] ?? 'Pembeli';

// Fungsi rupiah
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Tambah produk dari dashboard
if (isset($_GET['id_produk'])) {
    $id_produk = (int) $_GET['id_produk'];

    $cek = mysqli_prepare($conn, "SELECT id_produk, stok FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($cek, "i", $id_produk);
    mysqli_stmt_execute($cek);
    $produk = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

    if ($produk && (int)$produk['stok'] > 0) {
        $stmt = mysqli_prepare($conn, "
            SELECT id_keranjang, jumlah
            FROM keranjang
            WHERE id_user = ? AND id_produk = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_produk);
        mysqli_stmt_execute($stmt);
        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($item) {
            if ((int)$item['jumlah'] < (int)$produk['stok']) {
                $jumlah = (int)$item['jumlah'] + 1;
                $update = mysqli_prepare($conn, "
                    UPDATE keranjang SET jumlah = ?
                    WHERE id_keranjang = ? AND id_user = ?
                ");
                mysqli_stmt_bind_param($update, "iii", $jumlah, $item['id_keranjang'], $id_user);
                mysqli_stmt_execute($update);
            }
        } else {
            $jumlah = 1;
            $insert = mysqli_prepare($conn, "
                INSERT INTO keranjang (id_user, id_produk, jumlah)
                VALUES (?, ?, ?)
            ");
            mysqli_stmt_bind_param($insert, "iii", $id_user, $id_produk, $jumlah);
            mysqli_stmt_execute($insert);
        }
    }

    header("Location: keranjang.php");
    exit;
}

// Update jumlah dan hapus barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';
    $id_keranjang = (int)($_POST['id_keranjang'] ?? 0);

    if ($aksi === 'update') {
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

        $stmt = mysqli_prepare($conn, "
            SELECT k.id_keranjang, p.stok
            FROM keranjang k
            JOIN produk p ON k.id_produk = p.id_produk
            WHERE k.id_keranjang = ? AND k.id_user = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $id_keranjang, $id_user);
        mysqli_stmt_execute($stmt);
        $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($data) {
            $jumlah = min($jumlah, (int)$data['stok']);

            if ($jumlah > 0) {
                $update = mysqli_prepare($conn, "
                    UPDATE keranjang SET jumlah = ?
                    WHERE id_keranjang = ? AND id_user = ?
                ");
                mysqli_stmt_bind_param($update, "iii", $jumlah, $id_keranjang, $id_user);
                mysqli_stmt_execute($update);
            } else {
                $hapus = mysqli_prepare($conn, "
                    DELETE FROM keranjang
                    WHERE id_keranjang = ? AND id_user = ?
                ");
                mysqli_stmt_bind_param($hapus, "ii", $id_keranjang, $id_user);
                mysqli_stmt_execute($hapus);
            }
        }
    }

    if ($aksi === 'hapus') {
        $hapus = mysqli_prepare($conn, "
            DELETE FROM keranjang
            WHERE id_keranjang = ? AND id_user = ?
        ");
        mysqli_stmt_bind_param($hapus, "ii", $id_keranjang, $id_user);
        mysqli_stmt_execute($hapus);
    }

    header("Location: keranjang.php");
    exit;
}

// Ambil isi keranjang
$stmt = mysqli_prepare($conn, "
    SELECT
        k.id_keranjang,
        k.jumlah,
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.foto,
        p.stok
    FROM keranjang k
    JOIN produk p ON k.id_produk = p.id_produk
    WHERE k.id_user = ?
    ORDER BY k.id_keranjang DESC
");

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['subtotal'] = (float)$row['harga'] * (int)$row['jumlah'];
    $total += $row['subtotal'];
    $items[] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang Belanja - TokoKu</title>

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

.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: linear-gradient(180deg, #405b46, #304735);
    padding: 28px 18px;
    color: white;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 30px;
    border-bottom: 1px solid #ffffff30;
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
}

.menu a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 14px;
    margin-bottom: 7px;
    color: #dce8dc;
    text-decoration: none;
    border-radius: 12px;
    font-size: 14px;
}

.menu a:hover,
.menu a.active {
    background: #ffffff25;
    color: white;
}

.menu a i {
    font-size: 19px;
}

.logout {
    position: absolute;
    bottom: 25px;
    left: 18px;
    right: 18px;
}

.logout a {
    display: block;
    padding: 14px;
    color: #ffe1dc;
    background: #ffffff15;
    text-decoration: none;
    border-radius: 10px;
}

.main {
    margin-left: 250px;
    padding: 35px;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.topbar h2 {
    color: #405b46;
}

.topbar p {
    color: #899087;
    font-size: 13px;
    margin-top: 5px;
}

.profile {
    font-weight: bold;
    color: #405b46;
}

.cart-box {
    background: white;
    padding: 25px;
    border-radius: 17px;
    box-shadow: 0 5px 20px #405b4610;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    background: #edf3eb;
    color: #405b46;
    padding: 15px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 15px;
    border-bottom: 1px solid #edf0eb;
    font-size: 13px;
}

.produk {
    display: flex;
    align-items: center;
    gap: 12px;
}

.produk img {
    width: 65px;
    height: 65px;
    object-fit: cover;
    border-radius: 9px;
    background: #edf3eb;
}

.jumlah {
    width: 65px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 7px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
}

.btn-update {
    background: #829b83;
    color: white;
}

.btn-hapus {
    background: #ffe5e5;
    color: #c0392b;
}

.total-box {
    margin-top: 25px;
    padding: 22px;
    background: #edf3eb;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-box h3 {
    color: #405b46;
}

.total-harga {
    font-size: 23px;
    font-weight: bold;
    color: #405b46;
}

.btn-belanja {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 20px;
    border-radius: 9px;
    background: #405b46;
    color: white;
    text-decoration: none;
}

.kosong {
    text-align: center;
    padding: 55px 15px;
    color: #899087;
}

.kosong i {
    font-size: 55px;
    display: block;
    margin-bottom: 15px;
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
        text-align: center;
        justify-content: center;
        padding: 14px 5px;
    }

    .main {
        margin-left: 75px;
        padding: 20px;
    }
}

@media(max-width: 550px) {
    .main {
        padding: 15px 10px;
    }

    .topbar h2 {
        font-size: 18px;
    }

    .cart-box {
        padding: 12px;
    }

    .total-box {
        gap: 10px;
        flex-direction: column;
        align-items: flex-start;
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
        <a href="dashboard.php">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>

        <a href="dashboard.php#produk">
            <i class="bi bi-shop"></i>
            <span>Produk</span>
        </a>

        <a href="keranjang.php" class="active">
            <i class="bi bi-cart3"></i>
            <span>Keranjang</span>
        </a>

        <a href="dashboard.php#riwayat">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Pembelian</span>
        </a>
    </div>

    <div class="logout">
        <a href="logout.php"
           onclick="return confirm('Yakin ingin keluar?')">
            <i class="bi bi-box-arrow-left"></i>
            <span> Logout</span>
        </a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div>
            <h2>Keranjang Belanja</h2>
            <p>Daftar produk yang ingin kamu beli.</p>
        </div>

        <div class="profile">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <div class="cart-box">

        <?php if (count($items) > 0): ?>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($items as $item): ?>

                <tr>
                    <td>
                        <div class="produk">

                            <?php if (!empty($item['foto'])): ?>
                                <img
                                    src="<?= htmlspecialchars($item['foto'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($item['nama_produk'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <img
                                    src="https://placehold.co/100x100?text=Produk"
                                    alt="Produk"
                                >
                            <?php endif; ?>

                            <strong>
                                <?= htmlspecialchars($item['nama_produk'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>

                        </div>
                    </td>

                    <td><?= rupiah($item['harga']) ?></td>

                    <td>
                        <form method="POST">
                            <input type="hidden" name="aksi" value="update">
                            <input type="hidden" name="id_keranjang"
                                   value="<?= (int)$item['id_keranjang'] ?>">

                            <input
                                type="number"
                                name="jumlah"
                                class="jumlah"
                                min="1"
                                max="<?= (int)$item['stok'] ?>"
                                value="<?= (int)$item['jumlah'] ?>"
                                required
                            >

                            <button class="btn btn-update" type="submit">
                                Update
                            </button>
                        </form>
                    </td>

                    <td>
                        <strong><?= rupiah($item['subtotal']) ?></strong>
                    </td>

                    <td>
                        <form method="POST"
                              onsubmit="return confirm('Hapus produk ini dari keranjang?')">

                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id_keranjang"
                                   value="<?= (int)$item['id_keranjang'] ?>">

                            <button class="btn btn-hapus" type="submit">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

        <div class="total-box">

        <form action="checkout.php" method="POST"
      onsubmit="return confirm('Apakah kamu yakin ingin checkout?')">

    <button type="submit" class="btn-belanja">
        <i class="bi bi-bag-check"></i>
        Checkout Sekarang
    </button>

</form>
            <div>
                <h3>Total Belanja</h3>
                <p>Jumlah seluruh produk di keranjang</p>
            </div>

            <div class="total-harga">
                <?= rupiah($total) ?>
            </div>
        </div>

        <a href="dashboard.php#produk" class="btn-belanja">
            <i class="bi bi-arrow-left"></i>
            Lanjut Belanja
        </a>

        <?php else: ?>

        <div class="kosong">
            <i class="bi bi-cart-x"></i>
            <h3>Keranjang Masih Kosong</h3>
            <p>Belum ada produk yang ditambahkan.</p>

            <a href="dashboard.php#produk" class="btn-belanja">
                Mulai Belanja
            </a>
        </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
