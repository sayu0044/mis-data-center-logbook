# Audit tampilan terhadap referensi

Pemeriksaan visual dilakukan pada `https://logbook-mis.ai.studio/` (panel riwayat) dan `/guest` (form), lalu dibandingkan dengan aplikasi lokal pada viewport desktop 1440 px dan mobile 390 px. Screenshot diambil melalui browser automation pada kedua ukuran tersebut.

| Komponen | Referensi | Proyek sebelum revisi | Perbedaan | Perbaikan |
| --- | --- | --- | --- | --- |
| Header tamu | Header putih ringkas; logo gelap; lencana “Presensi Resmi Tamu” | Topbar admin dan navigasi penuh tampak pada halaman tamu | Tinggi, isi, dan posisi berbeda | Header tamu dibuat khusus dengan logo, teks, dan lencana pada lebar referensi |
| Judul dan pengantar | Lencana biru, judul terpusat, deskripsi dua baris, jam saat ini | Lencana dan judul mirip, tetapi ukuran dan jam statis berbeda | Skala dan ritme vertikal berbeda | Ukuran tipografi, jarak, dan jam berjalan Asia/Jakarta disamakan |
| Navigasi kunjungan | Dua tab untuk masuk dan keluar | Tautan check-in/checkout di header | Alur terpisah tidak diperlukan setelah perubahan kebutuhan | Kedua tab dihilangkan; tamu langsung melihat satu formulir saat memindai QR |
| Kartu formulir | Kartu putih sekitar 544 px pada desktop 1440 px, garis aksen di atas, input berikon | Kartu sekitar 576 px dan input tanpa ikon | Lebar dan detail komponen berbeda | Lebar, padding, aksen, ikon, input, dan tombol disesuaikan |
| Form dan data | Referensi memuat departemen wajib, pilihan nama staf, jam masuk serta jam keluar, dan kontak | Proyek memuat perusahaan opsional, nama staf bebas, tanggal wajib, dan checkout terpisah | Data referensi bertentangan dengan requirement Pak Jufri | Form satu kali isi memuat tanggal, identitas, keperluan, jam masuk dan jam keluar; gaya input mengikuti referensi |
| Penyimpanan | Referensi dapat mencatat jam keluar pada form | Proyek awal memakai kode untuk checkout terpisah | Tamu kini mengisi form saat keluar | Satu kiriman langsung menyimpan satu kunjungan lengkap tanpa kode |
| Riwayat tamu | Tab Formulir/Riwayat dengan jumlah data; kartu judul dan ringkasan; pencarian; filter status; tabel putih dengan tanggal, jam masuk/keluar, status, dan aksi; detail serta penyuntingan dalam modal | Riwayat awal belum memiliki editor lengkap | Koreksi hanya tersedia untuk jam presensi | Panah aksi `/history` langsung membuka formulir lengkap; tanggal, identitas, instansi, orang yang ditemui, keperluan, dan kedua jam dapat diubah tanpa memuat ulang halaman; ID tetap dan status dihitung otomatis |
| Mobile | Header 80 px, kartu 358 px pada 390 px, tab dua kolom | Header bertumpuk dan kartu lebih rapat | Posisi dan jarak berbeda | Header, tab, dan kartu diatur responsif sesuai lebar referensi |

Proyek memakai MySQL sebagai sumber data. Tombol Sinkronkan Data memuat ulang data terbaru dari database; CSV dan XLSX memakai cakupan filter yang sama. Tidak ada area admin atau rute administratif. Tab masuk/keluar, preset staf, nomor WhatsApp, dan integrasi cloud tidak dipakai sesuai alur terbaru.
