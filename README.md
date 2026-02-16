# 🚀 Panduan Instalasi MasTolongMas

Halo! Berikut langkah-langkah mudah untuk menjalankan project **MasTolongMas** di komputer temanmu.

## 📋 Persyaratan
Pastikan komputer temanmu sudah terinstall aplikasi:
1.  **XAMPP** (dengan PHP versi 8.0 ke atas & MySQL).
2.  **Web Browser** (Chrome / Edge).

---

## 🛠️ Cara Pasang (Install)

### 1. Pindahkan File Project
1.  Copy folder `mastolongmas` ini.
2.  Paste ke dalam folder `htdocs` di instalasi XAMPP temanmu.
    *   Biasanya ada di: `C:\xampp\htdocs\`
    *   Jadi nanti alamat foldernya: `C:\xampp\htdocs\mastolongmas\`

### 2. Import Database
1.  Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2.  Buka browser, ketik: `http://localhost/phpmyadmin`
3.  Buat database baru:
    *   Klik **New** di menu kiri.
    *   Isi nama database: `db_mastolongmas`
    *   Klik **Create**.
4.  Import file database:
    *   Klik tab **Import** di menu atas.
    *   Klik **Choose File**.
    *   Cari file bernama `mastolongmas_final.sql` yang ada di dalam folder project ini.
    *   Klik tombol **Import** di paling bawah.
    *   Tunggu sampai muncul pesan sukses (centang hijau).

### 3. Cek Koneksi (Opsional)
Jika di komputer temanmu ada password untuk MySQL (biasanya XAMPP default kosong), sesuaikan di file:
`includes/config.php`
```php
$pass = ''; // Isi jika ada password database
```

---

## 🏃 Cara Menjalankan
1.  Pastikan XAMPP (Apache & MySQL) sudah jalan.
2.  Buka browser.
3.  Akses alamat: `http://localhost/mastolongmas`

---

## 🔑 Akun Login
Gunakan akun ini untuk masuk sebagai Admin:
*   **Username (Admin):** `admin`
*   **Password:** (Sesuai yang sudah dibuat, atau cek tabel `users` jika lupa)

---
*Dibuat dengan ❤️ oleh Tim MasTolongMas.*
