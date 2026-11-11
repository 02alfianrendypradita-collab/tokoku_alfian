
<?php
session_start();
require_once 'koneksi.php';

// Pastikan hanya menerima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_pembeli.php");
    exit;
}

// Ambil data dari form
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input kosong
if ($email === '' || $password === '') {
    header("Location: login_pembeli.php?error=1");
    exit;
}

// Cari akun pembeli berdasarkan email
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_user, nama, email, password, role
     FROM users
     WHERE email = ? AND role = 'pembeli'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// Periksa apakah akun ditemukan
if (mysqli_num_rows($result) === 1) {

    $user = mysqli_fetch_assoc($result);

    // Verifikasi password
    if (password_verify($password, $user['password'])) {

        // Buat session baru
        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'pembeli';

        // Arahkan ke dashboard pembeli
        header("Location: dashboard.php");
        exit;

    } else {
        // Password salah
        header("Location: login_pembeli.php?error=1");
        exit;
    }

} else {
    // Akun tidak ditemukan
    header("Location: login_pembeli.php?error=1");
    exit;
}

mysqli_stmt_close($stmt);
?>
