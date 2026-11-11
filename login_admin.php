
<?php
session_start();

if (isset($_SESSION['id_user'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: dashboard_admin.php");
        exit;
    }

    // Jika session milik pelanggan, arahkan ke dashboard pelanggan
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - TokoKu</title>

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
            padding: 20px;
            background: linear-gradient(135deg, #edf2e9, #faf8f3, #f4e6da);
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 42px;
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
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: linear-gradient(135deg, #829b83, #405b46);
            color: white;
            font-size: 30px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(64,91,70,.2);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
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

        .admin-label {
            display: inline-block;
            margin-top: 14px;
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
            margin-bottom: 21px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #405b46;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 15px;
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

        .btn-login {
            width: 100%;
            padding: 16px;
            margin-top: 5px;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, #829b83, #405b46);
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px rgba(64,91,70,.22);
        }

        .back-link {
            margin-top: 24px;
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
            margin-top: 26px;
            text-align: center;
            color: #899087;
            font-size: 12px;
        }

        @media(max-width: 480px) {
            .login-card {
                padding: 30px 23px;
            }
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="logo">T</div>

    <div class="header">
        <h2>Admin TokoKu</h2>
        <p>Selamat datang di panel administrator</p>
        <span class="admin-label">ADMINISTRATOR ACCESS</span>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            Login gagal. Periksa email dan password atau pastikan akun memiliki role admin.
        </div>
    <?php endif; ?>

    <form action="proses_login_admin.php" method="POST">

        <div class="form-group">
            <label>Email Admin</label>
            <input
                type="email"
                name="email"
                placeholder="Masukkan email admin"
                required
                autocomplete="username"
            >
        </div>

        <div class="form-group">
            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="btn-login">
            Masuk ke Dashboard Admin
        </button>

    </form>

    <div class="back-link">
        Ingin belanja?
        <a href="login_pembeli.php">Login sebagai pelanggan</a>
    </div>

    <div class="footer">
        &copy; 2026 TokoKu. Admin Panel.
    </div>

</div>

</body>
</html>
