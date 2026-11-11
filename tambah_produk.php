
<?php
session_start();
require_once 'koneksi.php';

// Keamanan: hanya admin yang boleh mengakses
if (!isset($_SESSION['id_user'])) {
    header("Location: login_admin.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Token keamanan form
if (empty($_SESSION['csrf_produk'])) {
    $_SESSION['csrf_produk'] = bin2hex(random_bytes(32));
}

$error = "";

// Ambil kategori dari database
$kategori = mysqli_query(
    $conn,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

if (!$kategori) {
    die("Gagal mengambil kategori: " . mysqli_error($conn));
}

// Proses simpan barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $harga = $_POST['harga'] ?? '';
    $stok = $_POST['stok'] ?? '';

    $foto = null;
    $lokasiFoto = null;

    // Validasi token
    if (
        !hash_equals(
            $_SESSION['csrf_produk'],
            $_POST['csrf_token'] ?? ''
        )
    ) {
        $error = "Permintaan tidak valid. Silakan muat ulang halaman.";

    } elseif (
        $nama_produk === '' ||
        $id_kategori <= 0 ||
        !is_numeric($harga) ||
        $harga < 0 ||
        filter_var($stok, FILTER_VALIDATE_INT) === false ||
        $stok < 0
    ) {
        $error = "Mohon isi semua data barang dengan benar.";

    } else {

        // Pastikan kategori tersedia
        $cekKategori = mysqli_prepare(
            $conn,
            "SELECT id_kategori FROM kategori WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($cekKategori, "i", $id_kategori);
        mysqli_stmt_execute($cekKategori);

        $hasilKategori = mysqli_stmt_get_result($cekKategori);
        $kategoriAda = mysqli_fetch_assoc($hasilKategori);

        mysqli_stmt_close($cekKategori);

        if (!$kategoriAda) {
            $error = "Kategori barang tidak ditemukan.";
        }

        // Proses upload foto jika ada
        if (
            $error === "" &&
            isset($_FILES['foto']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $error = "Foto gagal diunggah.";

            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran foto maksimal 2 MB.";

            } else {

                $tmpFoto = $_FILES['foto']['tmp_name'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpFoto);
                finfo_close($finfo);

                $formatFoto = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($formatFoto[$mime]) || !getimagesize($tmpFoto)) {
                    $error = "Format foto harus JPG, PNG, atau WEBP.";

                } else {

                    $folder = __DIR__ . '/uploads/produk/';

                    if (
                        !is_dir($folder) &&
                        !mkdir($folder, 0755, true)
                    ) {
                        $error = "Folder upload tidak bisa dibuat.";

                    } else {

                        $namaFile = bin2hex(random_bytes(16))
                            . '.' . $formatFoto[$mime];

                        $lokasiFoto = $folder . $namaFile;
                        $foto = 'uploads/produk/' . $namaFile;

                        if (!move_uploaded_file($tmpFoto, $lokasiFoto)) {
                            $error = "Gagal menyimpan foto barang.";
                            $foto = null;
                            $lokasiFoto = null;
                        }
                    }
                }
            }
        }

        // Simpan data barang
        if ($error === "") {

            $harga = (float)$harga;
            $stok = (int)$stok;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO produk
                (id_kategori, nama_produk, harga, stok, foto)
                VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $error = "Query gagal: " . mysqli_error($conn);

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "isdis",
                    $id_kategori,
                    $nama_produk,
                    $harga,
                    $stok,
                    $foto
                );

                if (mysqli_stmt_execute($stmt)) {

                    mysqli_stmt_close($stmt);

                    header("Location: tambah_produk.php?success=1");
                    exit;

                } else {

                    $error = "Gagal menyimpan barang: "
                        . mysqli_stmt_error($stmt);

                    mysqli_stmt_close($stmt);

                    // Hapus foto jika penyimpanan database gagal
                    if ($lokasiFoto && file_exists($lokasiFoto)) {
                        unlink($lokasiFoto);
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Barang - TokoKu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f5f7f2;
            color: #405b46;
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
            max-width: 850px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: #405b46;
        }

        .page-heading p {
            color: #899087;
            font-size: 14px;
        }

        .form-card {
            background: white;
            padding: 35px;
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(64,91,70,.07);
        }

        .form-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: 650;
        }

        input, select {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #e2e8df;
            border-radius: 12px;
            outline: none;
            background: #fafbf8;
            font-size: 14px;
            color: #405b46;
            transition: .3s;
        }

        input:focus, select:focus {
            border-color: #829b83;
            box-shadow: 0 0 0 4px rgba(130,155,131,.12);
            background: white;
        }

        .row-input {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .file-info {
            margin-top: 8px;
            color: #999;
            font-size: 12px;
        }

        .success {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #eaf5e9;
            color: #357342;
            font-size: 14px;
        }

        .error {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #fff0ed;
            color: #c45d4d;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: .3s;
        }

        .btn-save {
            background: linear-gradient(135deg, #829b83, #405b46);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(64,91,70,.2);
        }

        .btn-back {
            background: #edf2e9;
            color: #405b46;
        }

        .btn-back:hover {
            background: #dfe9dc;
        }

        @media(max-width: 600px) {
            .form-card {
                padding: 25px 20px;
            }

            .row-input {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .button-group {
                flex-direction: column;
            }

            .navbar {
                padding: 0 20px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="brand">Toko<span>Ku.</span></div>
    <div class="admin-info">
        Administrator: <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
    </div>
</nav>

<div class="container">

    <div class="page-heading">
        <h1>Tambah Barang</h1>
        <p>Tambahkan produk baru ke dalam katalog toko Anda.</p>
    </div>

    <div class="form-card">

        <div class="form-title">Informasi Barang</div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                Barang berhasil ditambahkan ke database!
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($kategori) === 0): ?>
            <div class="error">
                Belum ada kategori barang. Tambahkan kategori terlebih dahulu.
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_produk']) ?>"
            >

            <div class="form-group">
                <label>Nama Barang</label>
                <input
                    type="text"
                    name="nama_produk"
                    placeholder="Contoh: Sepatu Sneakers"
                    value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>"
                    maxlength="255"
                    required
                >
            </div>

            <div class="form-group">
                <label>Kategori Barang</label>

                <select name="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>

                    <?php while ($kat = mysqli_fetch_assoc($kategori)): ?>
                        <option
                            value="<?= (int)$kat['id_kategori'] ?>"
                            <?= (($_POST['id_kategori'] ?? '') == $kat['id_kategori']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <div class="row-input">

                <div class="form-group">
                    <label>Harga Barang (Rp)</label>
                    <input
                        type="number"
                        name="harga"
                        placeholder="Contoh: 150000"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Stok Barang</label>
                    <input
                        type="number"
                        name="stok"
                        placeholder="Contoh: 20"
                        min="0"
                        step="1"
                        value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-group">
                <label>Foto Barang</label>
                <input
                    type="file"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                >
                <div class="file-info">
                    Format JPG, PNG, WEBP. Maksimal 2 MB.
                </div>
            </div>

            <div class="button-group">

                <a href="dashboard_admin.php" class="btn btn-back">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="btn btn-save"
                    <?= mysqli_num_rows($kategori) === 0 ? 'disabled' : '' ?>
                >
                    + Simpan Barang
                </button>

            </div>

        </form>

    </div>

</div>

</body>
</html>
