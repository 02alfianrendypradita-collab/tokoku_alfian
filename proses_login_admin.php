
<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_admin.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login_admin.php?error=1");
    exit;
}

$query = mysqli_prepare(
    $conn,
    "SELECT id_user, nama, email, password, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($query, "s", $email);
mysqli_stmt_execute($query);

$result = mysqli_stmt_get_result($query);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($query);

if (
    $user &&
    password_verify($password, $user['password']) &&
    strtolower(trim($user['role'])) === 'admin'
) {

    session_regenerate_id(true);

    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = 'admin';

    header("Location: dashboard_admin.php");
    exit;
}

header("Location: login_admin.php?error=1");
exit;
?>
