
<?php
require_once 'koneksi.php';

$nama = "Administrator TokoKu";
$email = "admin@tokoku.com";
$password = "Admin12345";
$role = "admin";

// Buat password hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Periksa apakah akun sudah ada
$cek = mysqli_prepare(
    $conn,
    "SELECT id_user FROM users WHERE email = ? LIMIT 1"
);

if (!$cek) {
    die("Gagal memeriksa akun: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($cek, "s", $email);
mysqli_stmt_execute($cek);

$result = mysqli_stmt_get_result($cek);
$akun = mysqli_fetch_assoc($result);

mysqli_stmt_close($cek);

if ($akun) {

    // Perbarui akun jika sudah ada
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET nama = ?, password = ?, role = ?
         WHERE email = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nama,
        $hash,
        $role,
        $email
    );

} else {

    // Tambahkan akun baru
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (nama, email, password, role)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nama,
        $email,
        $hash,
        $role
    );
}

if (mysqli_stmt_execute($stmt)) {
    echo "<h2 style='color:green'>Akun Admin Berhasil Dibuat!</h2>";
    echo "Email: admin@tokoku.com<br>";
    echo "Password: Admin12345<br>";
    echo "Role: admin<br><br>";
    echo "<a href='login_admin.php'>Buka Login Admin</a>";
} else {
    echo "Gagal membuat akun: " .
         htmlspecialchars(mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);
?>
