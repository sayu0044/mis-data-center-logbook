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
3. Buat tabel: `Get-Content -Raw database/schema.sql | & .runtime/mysql-8.4.13-winx64/bin/mysql.exe -h 127.0.0.1 -P 3307 -u root`.
4. Buat akun database aplikasi dan perbarui `.env`: `php scripts/configure-mysql.php`.
5. Pasang dependensi: `composer install`.
6. Jalankan lokal: `php -S 127.0.0.1:8000 -t public`, lalu buka `http://127.0.0.1:8000`.

Jika mengubah tampilan, jalankan `npm ci` sekali lalu `npm run build:css`. Selama pengembangan dapat memakai `npm run watch:css`. Commit hasil build `public/assets/tailwind.css` bersama perubahan sumbernya; folder `node_modules/` diabaikan oleh Git.

`APP_URL` perlu mengarah ke alamat HTTPS/intranet yang dapat dijangkau ponsel tamu agar QR formulir bekerja.

Untuk menyalin data kunjungan dari MariaDB XAMPP (port `3306`) ke MySQL baru (port `3307`), buat schema target kosong dahulu lalu jalankan `php scripts/migrate-xampp-mariadb.php`. Script hanya memindahkan tabel `visits`.

## Database yang sudah ada

Instalasi baru hanya membuat tabel `visits`. Jika database lama masih memiliki tabel `admins` atau `audit_logs`, aplikasi revisi ini tidak membacanya dan tidak menghapusnya otomatis agar data lama tidak terhapus tanpa sengaja.

## Alur pencatatan tamu

- Tamu memindai QR saat akan meninggalkan Data Center, lalu mengisi tanggal, identitas, perusahaan opsional, orang yang ditemui, keperluan, jam masuk, dan jam keluar.
- Satu pengiriman formulir membuat satu kunjungan lengkap. Tidak ada kode kunjungan atau tahap check-in/check-out terpisah.
- Jam masuk dan jam keluar yang diisi tamu berbeda dari waktu pencatatan server; kedua waktu pencatatan server tersimpan saat formulir dikirim.
- Halaman riwayat publik menampilkan jumlah data, ringkasan hari ini, tabel tanggal dan jam masuk/keluar, status, pencarian, filter status dan tanggal, detail dalam dialog, ekspor CSV/XLSX sesuai filter, penyuntingan seluruh field kunjungan, serta halaman lanjutan untuk lebih dari 25 data.
- Catatan lama yang belum memiliki jam keluar tetap terlihat sebagai `Belum lengkap`.
- Kolom tanda tangan belum dibuat karena masih menunggu konfirmasi kebutuhan operasional.
- Kredensial database disimpan di `.env`, bukan di repository.
