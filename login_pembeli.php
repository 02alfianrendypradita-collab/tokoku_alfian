
<?php
session_start();

if (isset($_SESSION['id_user']) && ($_SESSION['role'] ?? '') === 'pembeli') {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // AKUN DUMMY
    if ($username === "user" && $password === "user123") {

        session_regenerate_id(true);

        $_SESSION['id_user'] = 1;
        $_SESSION['nama'] = "Pembeli Demo";
        $_SESSION['role'] = "pembeli";

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login TokoKu</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg, #304735, #829b83);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    width: 350px;
    background: white;
    padding: 35px;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,.2);
}

h2 {
    text-align: center;
    color: #405b46;
    margin-bottom: 10px;
}

p {
    text-align: center;
    color: #888;
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 7px;
    color: #405b46;
}

input {
    width: 100%;
    padding: 12px;
    margin-bottom: 17px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

button {
    width: 100%;
    padding: 13px;
    background: #405b46;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
}

button:hover {
    background: #304735;
}

.error {
    background: #ffe5e5;
    color: red;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 7px;
    font-size: 13px;
}

.demo {
    background: #edf3eb;
    padding: 12px;
    margin-top: 20px;
    border-radius: 8px;
    color: #405b46;
    font-size: 13px;
    line-height: 1.8;
}
</style>
</head>

<body>

<div class="login-box">

    <h2>LOGIN USER</h2>
    <p>Selamat datang di TokoKu</p>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input type="text"
               name="username"
               placeholder="Masukkan username"
               required>

        <label>Password</label>
        <input type="password"
               name="password"
               placeholder="Masukkan password"
               required>

        <button type="submit">LOGIN</button>

    </form>

    <div class="demo">
        <b>Akun Dummy</b><br>
        Username: user<br>
        Password: user123
    </div>

</div>

</body>
</html>
