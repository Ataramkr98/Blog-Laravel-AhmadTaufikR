# Hasil Tahap 4 — Standardisasi portofolio dan dokumentasi

Tanggal: 31 Oktober 2026. Status: selesai dan diverifikasi secara lokal.

## Perubahan

- Menambahkan [README](../README.md) berbahasa Indonesia dengan deskripsi, screenshot, fitur dan role, arsitektur, stack, instalasi SQLite/MySQL, konfigurasi environment, akun demo, alur presentasi, pengembangan, QA, troubleshooting, dan catatan operasional.
- Menyelaraskan metadata Composer/npm dengan BioLearn. Lock file diperbarui tanpa mengubah versi paket pada tahap ini. Minimum runtime terdokumentasi: PHP 8.4.1+ dan Node 22.12+.
- Merapikan [.env.example](../.env.example): SQLite sebagai default yang portabel, URL lokal dengan port 8000, locale Indonesia, session/cache database, media publik, email log, contoh MySQL/SMTP, dan Google OAuth opsional. Variabel layanan yang tidak diperlukan untuk instalasi awal tidak lagi memenuhi contoh konfigurasi.
- Menambahkan [bootstrap lokal](../scripts/prepare-local.php) dan alur `composer setup`: install dari lock, persiapan environment/key/SQLite, migrasi dan seeder, storage link, `npm ci`, lalu build. Bootstrap mempertahankan key dan database yang sudah ada, menolak environment nonlokal serta konfigurasi cache, dan mengembalikan exit code gagal pada error.
- Menyederhanakan `composer dev` menjadi server Laravel yang dapat berjalan di Windows; Vite dan worker antrean memiliki perintah terpisah yang dijelaskan README.
- Menggabungkan workflow CI menjadi [Quality checks](../.github/workflows/tests.yml), dengan permission baca, PHP 8.4/8.5, Node 22, ekstensi yang dibutuhkan, install dari lock, Pint, PHPStan, tes PHP/JS, build, dan audit dependency. Kredensial Flux, cabang starter `workos`, workflow lint duplikat, serta blok auto-commit yang dikomentari dihapus.
- Menghapus skrip `_smoke_cert.php`, Kernel console lama yang tidak didaftarkan oleh bootstrap Laravel, route command `inspire`, unit test `expect(true)`, helper Pest `toBeOne`/`something`, helper role/scope/factory tanpa pemanggil, dan listener `refreshArticles` tanpa emitter di sumber proyek.
- Menghapus komentar yang hanya mengulangi nama method/field, komentar migrasi generik, serta deklarasi kosong. PHPDoc tipe, penjelasan cache/analytics, dan alasan perlindungan data tetap tersedia. Output command operasional dan logging kegagalan OAuth yang diperlukan tetap dipertahankan.
- Memperluas `.gitignore` untuk variasi `.env`, artefak Playwright, dan output sementara. README disertakan dalam export sumber.
- Memindahkan bukti QA Tahap 3 yang dirujuk laporan ke [docs/qa/stage-3](../docs/qa/stage-3/) dan memperbarui tautannya. Screenshot baru untuk README tersedia di [docs/screenshots](../docs/screenshots/).
- Review screenshot menemukan teks tombol pencarian beranda terpotong oleh flex shrink. Tombol kini tidak menyusut/memecah kata, input memiliki label aksesibel dan ruang fleksibel, serta ikon dekoratif tidak dibacakan screen reader.

## Temuan bootstrap yang diperbaiki

Pengujian environment produksi menemukan bahwa exception yang dilempar setelah bootstrap Laravel dapat ditampilkan oleh handler CLI dengan **exit code 0** ketika skrip dijalankan di luar Artisan. Kondisi tersebut dapat membuat Composer meneruskan langkah migrasi/seeder setelah penolakan.

Skrip kini menangkap kegagalan pada batas eksekusinya, menulis pesan ke stderr, dan melakukan `exit(1)`. Pengujian ulang membuktikan environment produksi dan konfigurasi cache ditolak, sementara hash `.env` serta file SQLite tidak berubah. Temuan berasal dari pengujian salinan sementara; migrasi/seeder tidak dijalankan pada MySQL lokal selama Tahap 4.

## Hasil pemeriksaan

| Pemeriksaan | Hasil |
| --- | --- |
| `composer validate --strict` | Lulus |
| `composer check-platform-reqs` | Lulus pada PHP 8.4.25 |
| `composer test` | Lulus: Pint, PHPStan, **94 tes / 464 assertions** |
| PHPStan sesudah perbaikan bootstrap | Lulus, 0 error |
| Pint untuk bootstrap | Lulus |
| Tes render beranda sesudah perapian pencarian | Lulus |
| `npm test` | 3 tes lulus |
| `npm run build` | Lulus; build akhir CSS 81,93 kB, app JS 303,18 kB, editor terpisah 394,68 kB |
| `composer audit --locked` | 0 advisory, 0 paket abandoned |
| `npm audit` | 0 vulnerability |
| Parsing YAML workflow | Struktur valid, 12 langkah; permission contents read |
| Scan sumber aplikasi | Tidak ditemukan `console.*`, `dd`, `dump`, `var_dump`, `print_r`, `debugger`, TODO/FIXME/HACK |

Jumlah tes PHP berkurang satu dari Tahap 3 karena unit test bawaan yang hanya menguji `true` dihapus; tes fitur yang memverifikasi perilaku aplikasi tetap dipertahankan.

## Instalasi bersih dan QA browser

Sumber disalin ke direktori TEMP baru tanpa `.env`, SQLite lama, vendor, node_modules, media runtime, atau hasil build. `composer setup` berhasil memasang **141 paket Composer** dan **84 paket npm**, membuat key serta SQLite baru, menjalankan seluruh migrasi/seeder, membuat storage junction pada Windows, dan membangun aset.

Data instalasi baru: **7 pengguna, 12 artikel, 4 kursus, 16 istilah glosarium, 9 enrollment, 22 progres, 4 percobaan ujian, dan 3 sertifikat**. Akun demo memiliki role pembaca, password sesuai dokumentasi, dua enrollment, dan satu sertifikat. Jumlah ini menggambarkan instalasi baru, bukan jumlah data historis pada MySQL pengguna.

Bootstrap dijalankan ulang; hash `.env` dan SQLite tetap identik. Environment produksi dan config cache ditolak dengan exit code 1 tanpa mengubah kedua file tersebut.

Server QA menggunakan checkout sementara dan `artisan serve --no-reload`. Penanda isolasi diuji sebelum login: maintenance hanya pada SQLite salinan menghasilkan HTTP 503, endpoint `/up` tetap 200, lalu beranda kembali 200 setelah penanda dilepas.

Chrome memverifikasi:

- Tombol Login Akun Demo berhasil masuk melalui formulir normal.
- Dashboard menampilkan kursus aktif dengan progres 33% dan kursus selesai 100%.
- Sertifikat Nadia Pratiwi dan tombol cetak tampil, tanpa overflow pada mobile.
- Akun demo ditolak ketika membuka admin, sesuai HTTP 403 yang diharapkan.
- Sampul kursus dan media seed termuat; satu URL WebP diperiksa langsung menghasilkan HTTP 200 dan content type `image/webp`.
- Pencarian beranda berpindah ke `/articles?search=DNA`.
- Beranda pada lebar **360, 768, dan 1440 piksel** tidak overflow, tidak memiliki gambar gagal setelah decoding selesai, memiliki nama aksesibel pada input, dan tidak memecah kata pada tombol pencarian.
- Tidak ada error JavaScript runtime pada alur penutup. HTTP 403 pada pemeriksaan larangan akses admin adalah hasil yang disengaja.

## Integritas dan artefak

Fingerprint **23 tabel domain/migrasi MySQL** sebelum dan sesudah pekerjaan identik. Hash `.env` pengguna juga identik. Pengujian instalasi tidak menyalin atau mengganti kredensial lokal. Server dan browser QA sudah dihentikan.

- [Pemeriksaan bootstrap](../docs/qa/stage-4/bootstrap-checks.json)
- [Data instalasi baru](../docs/qa/stage-4/install-data.json)
- [Penanda isolasi HTTP](../docs/qa/stage-4/isolation-check.json)
- [Login demo, dashboard, sertifikat, dan media](../docs/qa/stage-4/stage4-browser-check.txt)
- [Pencarian dan layout beranda](../docs/qa/stage-4/stage4-search-check.txt)
- [Integritas database dan environment](../docs/qa/stage-4/database-integrity.json)
- [Screenshot beranda desktop](../docs/screenshots/home-desktop.png)
- [Screenshot login mobile](../docs/screenshots/login-mobile.png)
- [Screenshot katalog kursus](../docs/screenshots/courses-desktop.png)

Bukti Tahap 3 dipertahankan sebagai rekaman historis; screenshot artikel mobile di sana mendahului penggantian favicon terakhir. Screenshot README diambil kembali dari instalasi bersih pada Tahap 4.

## Batas verifikasi

Instalasi penuh diuji pada Windows dengan PHP 8.4.25 dan Node 24.14.1. Workflow Linux/PHP 8.5/Node 22 sudah disesuaikan dan YAML diperiksa, tetapi belum dijalankan pada GitHub Actions. Google OAuth terhadap provider nyata, SMTP nyata, screen reader fisik, serta browser selain Chrome masih memiliki batas verifikasi yang dicatat pada laporan Tahap 3. Tidak ada deployment, commit, atau push pada tahap ini.
