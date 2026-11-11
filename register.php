
<?php
session_start();
require_once 'koneksi.php';

// Jika sudah login
if (isset($_SESSION['id_user'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if (isset($_POST['daftar'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    // Validasi password
    if ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {

        // Cek email sudah digunakan
        $stmt = mysqli_prepare($conn, "SELECT id_user FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email sudah terdaftar! Gunakan email lain.";
        } else {

            // Enkripsi password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Simpan akun pembeli
            $stmt_insert = mysqli_prepare(
                $conn,
                "INSERT INTO users (nama, email, password, role)
                 VALUES (?, ?, ?, 'pembeli')"
            );

            mysqli_stmt_bind_param(
                $stmt_insert,
                "sss",
                $nama,
                $email,
                $hash
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                header("Location: login_pembeli.php?success=daftar");
                exit;
            } else {
                $error = "Pendaftaran gagal! Silakan coba lagi.";
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
    <title>Daftar Pembeli - TokoKu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px 20px;
            background: linear-gradient(135deg, #edf2e9, #faf8f3, #f4e6da);
        }

        .register-card {
            width: 100%;
            max-width: 460px;
            padding: 40px;
            background: rgba(255,255,255,.95);
            border-radius: 26px;
            box-shadow: 0 20px 60px rgba(64,91,70,.13);
            animation: muncul .6s ease;
        }

        @keyframes muncul {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 21px;
            background: linear-gradient(135deg, #829b83, #405b46);
            color: white;
            font-size: 29px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(64,91,70,.2);
        }

        .header {
            text-align: center;
            margin-bottom: 27px;
        }

        .header h2 {
            color: #405b46;
            font-size: 27px;
            margin-bottom: 8px;
        }

        .header p {
            color: #899087;
            font-size: 14px;
        }

        .customer-label {
            display: inline-block;
            margin-top: 13px;
            padding: 8px 15px;
            border-radius: 30px;
            background: #edf3eb;
            color: #405b46;
            font-size: 12px;
            font-weight: 700;
        }

        .error {
            padding: 13px;
            margin-bottom: 20px;
            border-radius: 11px;
            background: #fff0ed;
            color: #c45d4d;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #405b46;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #e2e8df;
            border-radius: 13px;
            outline: none;
            background: #fafbf8;
            font-size: 14px;
            transition: .3s;
        }

        input:focus {
            border-color: #829b83;
            box-shadow: 0 0 0 4px rgba(130,155,131,.13);
            background: white;
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            margin-top: 7px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, #829b83, #405b46);
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(64,91,70,.22);
        }

        .login-link {
            margin-top: 23px;
            text-align: center;
            color: #899087;
            font-size: 13px;
        }

        .login-link a {
            color: #405b46;
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-link {
            margin-top: 20px;
            text-align: center;
            color: #899087;
            font-size: 13px;
        }

        .back-link a {
            color: #405b46;
            font-weight: 700;
            text-decoration: none;
        }

        .footer {
            margin-top: 25px;
            text-align: center;
            color: #899087;
            font-size: 12px;
        }

        @media(max-width: 480px) {
            .register-card {
                padding: 30px 23px;
            }
        }
    </style>
</head>

<body>

<div class="register-card">

    <div class="logo">T</div>

    <div class="header">
        <h2>Daftar TokoKu</h2>
        <p>Buat akun untuk mulai berbelanja.</p>
        <span class="customer-label">CUSTOMER REGISTRATION</span>
    </div>

    <?php if ($error != ''): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input
                type="text"
                name="nama"
                placeholder="Masukkan nama lengkap"
                value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Email</label>
            <input
                type="email"
                name="email"
                placeholder="Masukkan email aktif"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Minimal 6 karakter"
                minlength="6"
                required
            >
        </div>

        <div class="form-group">
            <label>Konfirmasi Password</label>
            <input
                type="password"
                name="konfirmasi"
                placeholder="Ulangi password"
                minlength="6"
                required
            >
        </div>

        <button type="submit" name="daftar" class="btn-register">
            Daftar Sekarang
        </button>

    </form>

    <div class="login-link">
        Sudah punya akun?
        <a href="login_pembeli.php">Login Sekarang</a>
    </div>

    <div class="back-link">
        <a href="login_admin.php">Login sebagai Admin</a>
    </div>

    <div class="footer">
        &copy; 2026 TokoKu. Customer Registration.
    </div>

</div>

</body>
</html>
        