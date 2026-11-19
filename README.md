# MIS Data Center Logbook

Aplikasi buku tamu responsif dengan PHP 8.2+, PDO, dan MySQL Community Server 8.4. Antarmuka berbahasa Indonesia dan menggunakan zona waktu `Asia/Jakarta`.

Antarmuka menggunakan Tailwind CSS 4. Sumbernya berada di `public/assets/tailwind-source.css`, dengan aturan komponen khusus di `public/assets/style.css`. File `public/assets/tailwind.css` adalah hasil build yang disajikan aplikasi dan disimpan di repository, sehingga Node.js tidak diperlukan saat menjalankan PHP di server.

## Rute

- `/` untuk tamu: satu formulir kunjungan yang diisi saat akan keluar dari Data Center.
- `/history` untuk riwayat tamu, pencarian, filter, ekspor CSV/XLSX, detail kunjungan, dan penyuntingan data kunjungan.

Tidak ada halaman admin, akun administrator, CRUD manual, atau autentikasi sesi. Sesi hanya menyimpan token CSRF dan state singkat alur tamu. Rute `/admin` sengaja ditutup.

## Instalasi lokal

1. Unduh MySQL 8.4 ZIP resmi ke `.runtime`: `New-Item -ItemType Directory -Force .runtime; curl.exe -L -o .runtime/mysql-8.4.13-winx64.zip https://dev.mysql.com/get/Downloads/MySQL-8.4/mysql-8.4.13-winx64.zip`.
2. Jalankan MySQL lokal: `powershell -ExecutionPolicy Bypass -File scripts/start-mysql.ps1`.
3. Salin konfigurasi contoh dengan `Copy-Item .env.example .env`, lalu buat database dan akun aplikasi menggunakan `php scripts/configure-mysql.php`.
4. Buat tabel di database tersebut: `Get-Content -Raw database/schema.sql | & .runtime/mysql-8.4.13-winx64/bin/mysql.exe -h 127.0.0.1 -P 3307 -u root mis_logbook`.
5. Pasang dependensi: `composer install`.
6. Jalankan lokal: `php -S 127.0.0.1:8000 -t public`, lalu buka `http://127.0.0.1:8000`.

Jika mengubah tampilan, jalankan `npm ci` sekali lalu `npm run build:css`. Selama pengembangan dapat memakai `npm run watch:css`. Commit hasil build `public/assets/tailwind.css` bersama perubahan sumbernya; folder `node_modules/` diabaikan oleh Git.

Untuk memakai database MySQL yang sudah ada, isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Impor `database/schema.sql` ke database yang dipilih menggunakan akun yang memiliki izin `CREATE TABLE`. Berkas skema hanya membuat tabel `visits` pada database aktif; akun aplikasi cukup memerlukan izin `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` setelah tabel tersedia. Jangan jalankan `scripts/configure-mysql.php` untuk koneksi ini karena skrip tersebut khusus MySQL lokal dan akan mengganti `.env`.

Autocomplete formulir tamu memakai `esa.v_user_esa` secara bawaan melalui `GET /api/employees` dan `GET /api/mis-staff`. Pencarian NIK (`ID_CARD`) dan nama (`NAMA`) mencocokkan awalan teks; pemilihan staf yang ditemui hanya menampilkan karyawan aktif (`MAIN_STATUS = 'p'`) pada departemen MIS (`ID_DEPAR = 17`). Jika sumber berbeda, isi `EMPLOYEE_LOOKUP_SOURCE` dengan nama tabel/view yang memiliki kolom tersebut. Nama koneksi Navicat tidak dapat langsung dipakai oleh PHP.

Koneksi database kunjungan saat ini tidak memiliki izin `SELECT` pada `esa.v_user_esa`. Untuk memakai koneksi `approval` yang berbeda, tambahkan konfigurasi berikut ke `.env` lokal (jangan commit kredensial):

```dotenv
EMPLOYEE_LOOKUP_SOURCE=esa.v_user_esa
EMPLOYEE_DB_HOST=<host database karyawan>
EMPLOYEE_DB_PORT=3306
EMPLOYEE_DB_DATABASE=esa
EMPLOYEE_DB_USERNAME=<akun dengan izin SELECT pada esa.v_user_esa>
EMPLOYEE_DB_PASSWORD=<kata sandi akun tersebut>
```

`EMPLOYEE_DB_USERNAME` mengaktifkan koneksi baca terpisah. Bila tidak diisi, lookup memakai koneksi `DB_*` utama; akun itu perlu diberi izin `SELECT` pada sumber karyawan. Untuk keamanan, gunakan akun baca saja. Jika tabel `visits` dibuat sebelum NIK ditambahkan, jalankan `database/migrations/2026-11-18-visitor-identity.sql` sekali dengan akun yang memiliki izin `ALTER TABLE`.

Untuk menyalin data kunjungan dari MariaDB XAMPP (port `3306`) ke MySQL baru (port `3307`), buat schema target kosong dahulu lalu jalankan `php scripts/migrate-xampp-mariadb.php`. Script hanya memindahkan tabel `visits`.

## Database yang sudah ada

Instalasi baru hanya membuat tabel `visits`. Jika database lama masih memiliki tabel `admins` atau `audit_logs`, aplikasi revisi ini tidak membacanya dan tidak menghapusnya otomatis agar data lama tidak terhapus tanpa sengaja.

## Alur pencatatan tamu

- Tamu membuka formulir saat akan meninggalkan Data Center, lalu mengisi tanggal, identitas, departemen opsional, orang yang ditemui, keperluan, jam masuk, dan jam keluar.
- Satu pengiriman formulir membuat satu kunjungan lengkap. Tidak ada kode kunjungan atau tahap check-in/check-out terpisah.
- Jam masuk dan jam keluar yang diisi tamu berbeda dari waktu pencatatan server; kedua waktu pencatatan server tersimpan saat formulir dikirim.
- Halaman riwayat publik menampilkan jumlah data, ringkasan hari ini, tabel tanggal dan jam masuk/keluar, status, pencarian, filter status dan tanggal, detail dalam dialog, ekspor CSV/XLSX sesuai filter, penyuntingan seluruh field kunjungan, serta halaman lanjutan untuk lebih dari 25 data.
- Catatan lama yang belum memiliki jam keluar tetap terlihat sebagai `Belum lengkap`.
- Kolom tanda tangan belum dibuat karena masih menunggu konfirmasi kebutuhan operasional.
- Kredensial database disimpan di `.env`, bukan di repository.
