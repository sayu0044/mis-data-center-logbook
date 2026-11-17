# Contoh pembatasan admin di Apache 2.4

Atur document root ke folder `public/`, lalu batasi seluruh URL `/admin` pada web server. Contoh di bawah harus disesuaikan dengan CIDR jaringan MIS yang benar:

```apache
<VirtualHost *:443>
    ServerName logbook.internal.example
    DocumentRoot "/srv/mis-logbook/public"

    <Directory "/srv/mis-logbook/public">
        Require all granted
        AllowOverride None
        Options -Indexes
    </Directory>

    <LocationMatch "^/admin(?:/|$)">
        Require ip 10.20.0.0/16 192.168.10.0/24
    </LocationMatch>

    # Tambahkan konfigurasi PHP-FPM dan sertifikat TLS sesuai server.
</VirtualHost>
```

Isi `ADMIN_ALLOWED_CIDRS` dengan CIDR internal yang sama. Aplikasi menolak semua akses admin jika variabel ini kosong. Jika memakai reverse proxy, batasi koneksi ke proxy pada jaringan tepercaya dan teruskan alamat klien sebenarnya melalui konfigurasi server; aplikasi sengaja hanya membaca `REMOTE_ADDR`, bukan `X-Forwarded-For`. Batasi akses ke URL admin juga pada firewall. Server `php -S` hanya untuk pengembangan; ia tidak menerapkan aturan Apache di atas.
