# Hasil Tahap 3 — Frontend dan UI/UX

Tanggal: 31 Oktober 2026. Status: implementasi dan verifikasi Tahap 3 selesai. Tahap 4 (README, `.env.example`, dan standarisasi akhir) tetap terpisah.

## Perubahan yang selesai

| Area | Hasil |
| --- | --- |
| Runtime | Satu runtime Alpine/Livewire melalui Vite; ekspresi `request()` yang semula dijalankan sebagai JavaScript sudah diperbaiki. |
| Navigasi | Menu mobile publik, sidebar admin dengan focus trap dan inert saat tertutup, Escape dan pengembalian fokus, skip link, label formulir, serta zona SMA/PT yang mengikuti filter reaktif. |
| Login demo | Tombol **Login Akun Demo** mengisi kredensial dan mengirim formulir login normal dalam satu klik. Tidak ada bypass autentikasi atau pemberian hak admin. |
| Formulir | POST memakai progressive enhancement dengan validasi 422, pesan 403/404/419/429/5xx, timeout, indikator proses, pencegahan submit ganda, dan pemulihan tombol setelah gagal. Formulir HTML tetap tersedia saat JavaScript tidak aktif. |
| State permintaan | Loading dan notifikasi bersama; pesan offline; empty state untuk daftar; ringkasan validasi; pesan error lokal di dialog yang memakai focus trap. |
| Editor artikel | Tiptap dimuat saat dibutuhkan; instance editor disimpan di closure agar tidak diproksikan Alpine; API `setContent` sesuai versi 3; duplikasi extension Link dihilangkan. Toolbar dan panel URL gambar/tautan berfungsi. |
| Editor kursus | Tambah/hapus modul, pelajaran, soal, dan opsi; kontrol pelajaran wajib; pemilihan jawaban benar memakai label opsi. Menghapus jawaban benar mengharuskan pemilihan ulang. Indeks jawaban lain tetap menunjuk teks yang sama. |
| Pembacaan materi | Typography aktif, dialog glosarium dapat dipakai artikel maupun pelajaran, salin tautan memiliki feedback, sertifikat dan tampilan cetak dirapikan. |
| Data publik | Topik populer berasal dari tag artikel terbit; halaman About menampilkan jumlah materi dan kontributor yang benar-benar memiliki artikel terbit. Profil akademik dan klaim sosial rekaan dihapus. |
| Media | URL sampul, avatar, dan media lokal mengikuti origin aktif. URL CDN eksternal tetap dipertahankan; avatar memiliki fallback inisial. Identitas visual favicon disesuaikan dengan BioLearn. |
| Admin | Pencarian header terhubung ke daftar artikel; pagination direset saat filter berubah; kedua daftar moderasi memiliki parameter pagination terpisah; notifikasi simpan/hapus langsung terlihat. |
| Registrasi | Formulir sesuai data yang disimpan backend; konfirmasi password yang dikirim divalidasi; pilihan topik yang sebelumnya tidak disimpan sudah dihapus dari alur. |
| Error halaman | Halaman 403/404/419/429/500 memiliki pesan dan jalan kembali yang jelas; tampilan 500 tidak bergantung pada database atau runtime aplikasi. |
| Cache | Glosarium menyimpan array atribut primitif dan melakukan hydration saat dibaca. Ini memperbaiki `__PHP_Incomplete_Class` pada cache terserialisasi ketika kelas PHP tidak diizinkan. Kunci v2 menghindari payload lama. |
| Dependency | Tiptap 3.32.2, Vite 8.3.5, dan typography 0.5.20. Override `postcss-selector-parser` ke versi kompatibel 6.1.5+ menutup advisory dependency typography. |

## Hasil pemeriksaan otomatis

| Pemeriksaan | Hasil |
| --- | --- |
| `composer test` | Lulus: Pint, PHPStan, **95 tes / 465 assertions**. |
| `npm test` | Lulus: 3 tes normalisasi pesan validasi, penyembunyian detail server, dan pembatasan redirect ke origin sendiri. |
| `npm run build` | Lulus. Editor menjadi chunk terpisah; font disajikan secara lokal. |
| `npm audit` | 0 advisory. Sebelum pembaruan terdapat 31 temuan paket terdampak. |
| `composer audit` | 0 advisory dan 0 paket abandoned. |

Tes regresi mencakup origin media, URL eksternal, data kontributor/topik dinamis, pencarian admin dari query URL, manipulasi editor kursus, reset pagination, validasi konfirmasi password, kestabilan indeks jawaban, dan cache lintas request tanpa deserialisasi kelas.

## QA browser

Chrome diuji pada lebar **360, 390, 768, dan 1440 piksel**. Sweep terstruktur menghasilkan **57 pemeriksaan layout**: 15 halaman publik, 24 halaman admin, dan 18 halaman tamu/legal/error. Tidak ditemukan overflow horizontal atau status HTTP yang tidak sesuai. Pemeriksaan admin dan tamu tidak menemukan kontrol formulir tanpa nama aksesibel maupun error JavaScript runtime.

Alur yang diuji langsung:

- Login salah menampilkan error dan mengembalikan fokus ke field; login demo berhasil dalam satu klik.
- Registrasi menolak konfirmasi password berbeda dan menerima isian valid beserta jenjang PT.
- Filter artikel, hasil kosong, pencarian pengguna kosong, dan sinkronisasi indikator zona SMA/PT/semua.
- Loading dengan request yang diperlambat: `pending=1/loading=true`, kemudian kembali ke `pending=0/loading=false`.
- Formulir newsletter menonaktifkan tombol dan memberi `aria-busy` selama proses, lalu menampilkan hasil sukses.
- HTTP 503 yang disimulasikan menampilkan pesan ramah; dialog editor juga menampilkan error di dalam dialog. Offline ditampilkan dengan jelas.
- Glosarium membuka definisi, ditutup dengan Escape, dan mengembalikan fokus ke istilah asal.
- Kuis artikel menampilkan skor 100% beserta pembahasan; ujian akhir menampilkan hasil belum lulus dengan benar; sertifikat demo dapat dibuka pada mobile.
- Pelajaran dapat ditandai selesai dan tombol kemudian dinonaktifkan.
- Editor membuat draf, menerapkan format tebal, menyimpan, lalu memulihkan konten berformat saat dibuka ulang.
- Kontrol kursus menambah modul dan opsi; formulir tidak lengkap menampilkan ringkasan validasi.
- Sidebar admin membuka, membatasi fokus, ditutup dengan Escape, dan mengembalikan fokus ke tombol menu. Sidebar tertutup tidak menyisakan target keyboard tersembunyi.

### Catatan integritas data QA

Server browser awal ternyata memakai MySQL lokal karena `artisan serve` menyaring environment subprocess saat reload aktif, meskipun migrasi/seeder persiapan telah dijalankan pada SQLite. Server dihentikan setelah hal ini ditemukan.

Hanya baris dengan identitas QA yang terverifikasi dihapus: satu akun registrasi QA, satu draf artikel QA, satu progres demo tambahan, satu percobaan ujian demo tambahan, satu subscription QA, dan satu kunjungan artikel QA. Penghitung dan waktu perubahan artikel dikembalikan ke nilai sebelum pengujian. Tidak dilakukan restore database menyeluruh.

Baris lama dicocokkan dengan cadangan: 12 artikel, 14 review, 10 modul, 14 pelajaran, 7 enrollment, 14 progres lama, 6 percobaan lama, 2 sertifikat lama, dan 5 subscription lama tetap identik. Data tambahan sah dari Tahap 2 dipertahankan. Jumlah akhir: 8 pengguna, 12 artikel, 9 enrollment, 22 progres, 7 percobaan ujian, 3 sertifikat, dan 5 subscription.

QA penutup dijalankan ulang memakai SQLite dengan `--no-reload`. Halaman penanda yang hanya ada di SQLite diverifikasi melalui HTTP sebelum menjalankan alur tulis. Login demo, penyimpanan progres, pembuatan draf melalui editor, dan reload cache berhasil tanpa error JavaScript. Fingerprint **23 tabel MySQL tetap identik** sebelum dan sesudah QA terisolasi ini.

Server dan sesi browser QA sudah dihentikan. Cadangan sumber Tahap 3 dan snapshot sebelum pembersihan disimpan di direktori TEMP lokal.

## Artefak

- [Ringkasan layout](../docs/qa/stage-3/stage3-responsive-summary.json)
- [Verifikasi integritas database](../docs/qa/stage-3/stage3-database-integrity.json)
- [QA terisolasi penutup](../docs/qa/stage-3/stage3-isolated-final-check.txt)
- [Indikator loading](../docs/qa/stage-3/stage3-loading-check.txt)
- [Formulir newsletter](../docs/qa/stage-3/stage3-native-form-check.txt)
- [Sinkronisasi zona](../docs/qa/stage-3/stage3-zone-check.txt)
- [Login mobile](../docs/qa/stage-3/stage3-login-mobile.png)
- [Artikel mobile](../docs/qa/stage-3/stage3-articles-mobile.png)
- [Admin mobile](../docs/qa/stage-3/stage3-admin-mobile.png)
- [Sertifikat mobile](../docs/qa/stage-3/stage3-certificate-mobile.png)
- [About desktop](../docs/qa/stage-3/stage3-about-desktop.png)

## Akun demo

Email: `demo@biology.learn`  
Password: `BioLearnDemo2026!`

Tombol demo memakai akun pembaca dengan progres kursus dan sertifikat dari seeder Tahap 2.

## Batas verifikasi dan tahap berikutnya

Google OAuth terhadap provider eksternal, pengiriman email melalui SMTP nyata, screen reader fisik, dan browser selain Chrome belum diuji end-to-end pada tahap ini. Pengujian aksesibilitas meliputi nama kontrol, semantik, fokus keyboard, dialog, dan responsivitas; ini tidak dinyatakan sebagai sertifikasi WCAG menyeluruh.

Tahap 4 mencakup README profesional, konfigurasi `.env.example`, dan standarisasi akhir. Tahap 3 tidak menjalankan deployment atau mengganti konfigurasi `.env` pengguna.
