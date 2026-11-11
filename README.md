# 🛒 TokoKu – Website Toko Online

**TokoKu** adalah aplikasi website toko online yang dikembangkan untuk memudahkan pengguna dalam melihat produk dan melakukan aktivitas belanja secara online. Website ini memiliki dua hak akses utama, yaitu Admin dan Pembeli.

## ✨ Fitur Utama

### 👨‍💻 Admin
- Login Admin
- Dashboard Admin
- Menambahkan produk/barang
- Menghapus produk/barang
- Melihat daftar produk
- Menampilkan gambar produk
- Mengelola data produk toko

### 🛍️ Pembeli
- Registrasi dan Login Pembeli
- Dashboard Pembeli
- Melihat katalog produk
- Melihat gambar dan informasi produk
- Menambahkan produk ke keranjang
- Melihat isi keranjang belanja

## 🛠️ Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP Native | Backend |
| MySQL | Database |
| HTML5 | Struktur halaman |
| CSS3 | Desain tampilan |
| JavaScript | Interaktivitas |
| Bootstrap / CSS | Komponen tampilan |
| XAMPP | Local Server |

## 📂 Struktur Project

```text
TokoKu/
│
├── koneksi.php
├── index.php
├── login.php
├── register.php
├── proses_login_pembeli.php
├── dashboard.php
├── dashboard_admin.php
│
├── produk_admin.php
├── tambah_barang.php
├── hapus_barang.php
│
├── keranjang.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
└── uploads/
    └── gambar_produk/
```

*Struktur folder dapat disesuaikan dengan file yang ada di project.*

## 🚀 Cara Menjalankan Project

1. Download atau clone repository ini.
2. Simpan folder project ke dalam direktori `htdocs`.
3. Jalankan Apache dan MySQL melalui XAMPP.
4. Buka phpMyAdmin melalui browser.
5. Buat database sesuai konfigurasi pada file `koneksi.php`.
6. Import file database SQL jika tersedia.
7. Sesuaikan konfigurasi koneksi database.
8. Buka website melalui browser:

```text
http://localhost/TokoKu
```

## ⚙️ Konfigurasi Database

Sesuaikan konfigurasi database pada file `koneksi.php`.

Contoh:

```php
<?php
$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "db_tokoku"
);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
```

## 🎯 Tujuan Project

Project TokoKu dibuat sebagai implementasi pembelajaran pengembangan aplikasi berbasis web, khususnya dalam penggunaan PHP Native dan MySQL untuk membangun sistem toko online.

## 👨‍💻 Developer

**Nama:** Aditya Devinza Anggara

**Project:** TokoKu – Website Toko Online

**Kategori:** Web Development

---

© 2026 TokoKu. All Rights Reserved.