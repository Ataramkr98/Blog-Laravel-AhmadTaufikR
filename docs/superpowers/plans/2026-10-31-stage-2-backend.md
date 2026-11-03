# Tahap 2: backend dan seeder

**Goal:** Memperbaiki temuan backend Tahap 1 dan menyediakan data demo yang aman diulang.

**Architecture:** Pertahankan monolit Laravel/Livewire. Sinkronisasi isi kursus memakai ID yang stabil; action selalu memeriksa otorisasi dan publikasi. Response web memakai redirect/flash, sedangkan request JSON memakai status HTTP dan struktur error Laravel.

**Scope:** Backend, pengujian regresi, dependensi PHP, migrasi tambahan bila diperlukan, seeder dan akun demo. UI/UX serta README tetap untuk tahap berikutnya. Tidak menjalankan migrasi destruktif.

- [x] Tulis dan jalankan tes regresi yang membuktikan kegagalan: progres kursus, batas akses konten/ujian, role yang dicabut, maintenance dengan cookie sesi, validasi registrasi, OAuth, rate limiting, JSON, seeder berulang, akun demo.
- [x] Sinkronkan modul/lesson/soal tanpa mengganti ID yang masih digunakan. Tolak penghapusan lesson yang memiliki progres.
- [x] Batasi attempt berdasarkan pemilik/kursus, kunci ID Livewire, validasi jawaban, tolak kursus/artikel yang tidak dipublikasikan, dan simpan attempt/sertifikat dalam transaksi.
- [x] Jalankan maintenance setelah sesi dimuat; persist middleware role; tambahkan validasi, respons JSON dan pembatasan request; tangani kegagalan OAuth.
- [x] Jadikan seeder idempoten dan pertahankan data pengguna. Buat akun pembaca demo dengan contoh enrollment, progres dan sertifikat.
- [x] Perbarui dependensi PHP yang terdampak advisory dan selaraskan minimum PHP/PHPStan. Verifikasi compatibility melalui seluruh tes.
- [x] Jalankan Pint, PHPStan, seluruh Pest, build, audit Composer, dan smoke test MySQL. Sebelum seeding lokal, cadangkan tabel domain; periksa bahwa progres lama tetap ada.

Cadangan sumber sebelum perubahan tersedia di direktori sementara sistem. Workspace tidak memiliki metadata Git; tidak membuat commit atau branch.
