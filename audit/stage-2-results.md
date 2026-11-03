# Tahap 2 — backend dan seeder

Tanggal: 31 Oktober 2026.

Tahap 2 sudah diterapkan dan diverifikasi. Perubahan tampilan, tombol login demo, pembaruan dependensi JavaScript, dan README tetap menjadi pekerjaan Tahap 3–4.

## Perubahan backend

- Sinkronisasi kursus mempertahankan ID modul, lesson, dan soal. Penyimpanan metadata tidak lagi menghapus progres. Data baru tidak digabung hanya karena judul atau artikel sama.
- Lesson yang memiliki progres tidak dapat dihapus atau diganti artikelnya melalui editor. Artikel yang digunakan kursus tidak dapat dihapus melalui model. Foreign key MySQL memakai RESTRICT untuk melindungi referensi artikel dan progres lesson.
- Lesson hanya menyajikan artikel yang sudah dipublikasikan. Ujian menolak kursus draft/arsip, memeriksa enrollment serta progres, dan memvalidasi semua jawaban.
- ID Livewire yang sensitif dikunci; hasil ujian dibatasi ke pemilik dan kursus. Token submission unik mencegah penyimpanan ganda. Attempt dan penerbitan sertifikat disimpan dalam satu transaksi.
- Attempt baru menyimpan salinan soal dan jawaban. HMAC mendeteksi perubahan soal atau passing score saat siswa sedang mengerjakan ujian. Data attempt lama tetap dipertahankan; salinan soal baru tidak dapat merekonstruksi versi soal historis yang belum pernah disimpan.
- Middleware maintenance berjalan setelah sesi tersedia, termasuk pengecualian URL reset password bertoken. Middleware role dipersistensikan pada request Livewire dan action CMS memeriksa ulang otorisasi.
- Registrasi menerima academic_level yang dihilangkan. Kesalahan validasi dan kredensial menghasilkan JSON Laravel untuk request JSON serta redirect/error bag untuk request web. Login, registrasi, pemulihan password, newsletter, dan submit ujian memiliki pembatasan request.
- OAuth menangani pembatalan, state kedaluwarsa, dan kegagalan provider. Pengaitan akun membutuhkan email Google terverifikasi dan tidak mengganti Google ID lain yang sudah terhubung.
- Kegagalan penulisan media tidak membuat baris aset palsu; error pemrosesan/penyimpanan tampil sebagai error unggahan. Dimensi unggahan dibatasi.
- Pagination artikel menggunakan state dan action Livewire; filter tersimpan dalam URL, perubahan pencarian mereset halaman, dan parameter query yang salah menghasilkan validasi.
- Pengaturan durasi cache glosarium kini digunakan. Perubahan glosarium melalui admin membatalkan cache terkait. Pesan password dan aturan validasi yang digunakan aplikasi tersedia dalam Bahasa Indonesia.

## Seeder dan akun demo

DatabaseSeeder menjalankan ContentSeeder dan DemoAccountSeeder. Seeder berulang mempertahankan ID, progres, waktu penyelesaian, enrollment, attempt, sertifikat, serta perubahan pengajar pada konten yang sudah ada. Seeder tidak otomatis menyelesaikan lesson baru bagi peserta yang sudah terdaftar.

| Properti | Nilai |
|---|---|
| Nama demo | Nadia Pratiwi |
| Email | demo@biology.learn |
| Password | BioLearnDemo2026! |
| Role | pembaca |
| Data awal | Dua enrollment, contoh progres, satu sertifikat |

Kredensial demo adalah data publik untuk demonstrasi. Role dan password akun dengan email demo yang dicadangkan diselaraskan saat seeding; progres yang sudah ada dipertahankan.

## Dependensi

- Laravel 13.15.0 menjadi 13.39.0; Livewire 4.3.1 menjadi 4.4.8.
- Dependensi PHP transitif yang terdampak advisory diperbarui. Paket abandoned nunomaduro/larastan dihapus; konfigurasi memakai larastan/larastan.
- Minimum PHP diselaraskan menjadi 8.4.1 dan ekstensi GD dideklarasikan. Matriks CI memakai PHP 8.4 dan 8.5.
- Larastan 3.10.0 dan PHPStan 2.2.2 dipertahankan dalam lock file: arsip PHPStan terbaru gagal ditulis pada lingkungan Windows ini. Keduanya kompatibel dengan hasil instalasi, analisis statis lulus, dan audit tidak melaporkan advisory.

## Bukti verifikasi

| Pemeriksaan | Hasil |
|---|---|
| composer test | Lulus: Pint, PHPStan, 82 tes / 425 assertions |
| npm run build | Lulus; output audit dibuat di direktori sementara |
| composer validate --strict --no-check-publish | Lulus |
| composer check-platform-reqs | Lulus |
| composer audit --locked --format=json | 0 advisory, 0 paket abandoned |
| Upgrade/rollback/upgrade SQLite berisi data | Lulus; penghapusan lesson melalui query langsung ditolak ketika memiliki progres |
| Migrasi MySQL lokal | Dua migrasi tambahan berhasil; aturan RESTRICT diperiksa melalui information_schema |
| Integritas data MySQL | Seluruh 14 progres, 7 enrollment, 6 attempt, dan 2 sertifikat lama identik dengan cadangan |
| Seeder ulang MySQL | Tidak menambah baris domain atau mengubah progres |
| HTTP akun demo | Login berhasil, dashboard 200, sertifikat 200, akses admin 403 |
| Browser pagination | Filter 4 artikel lalu Next membuka page=2, tetap 4 artikel, tanpa HTTP 405 |

Sumber, media publik, dan 23 tabel database dicadangkan ke direktori sementara sebelum perubahan database. Tidak menjalankan migrate:fresh, penghapusan database, commit, atau push.

## Sisa tahap berikutnya

- Tahap 3: perbaikan Alpine pada layout publik, responsivitas, state loading/empty/error, tampilan validasi lengkap, URL media yang portabel, interaksi placeholder, tombol Login Akun Demo, dan audit/pembaruan dependensi JavaScript.
- Tahap 4: README profesional, konfigurasi lingkungan yang mudah dipindahkan, pembersihan skrip/debug/dead code dan metadata starter kit.
- Google OAuth memakai pengujian provider terkontrol; autentikasi langsung dengan akun Google pengguna belum diuji. Pengiriman email lokal masih memakai driver log.
