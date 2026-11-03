# Tahap 4 — Standardisasi Portofolio

> **For agentic workers:** Gunakan superpowers:executing-plans untuk mengerjakan checklist ini dalam sesi yang telah diotorisasi pengguna.

**Goal:** Repositori BioLearn memiliki sumber yang bersih, konfigurasi contoh yang portabel, CI yang sesuai dependensi, dan README yang dapat diikuti pada instalasi baru.

**Architecture:** Pertahankan Laravel/Livewire monolith. Default instalasi baru memakai SQLite; MySQL pada `.env` pengguna tidak diubah. Bootstrap lokal menyiapkan key/database hanya ketika belum ada. Semua smoke test instalasi dijalankan dalam salinan sementara dengan `.env` baru.

**Tech Stack:** PHP 8.4.1+, Laravel 13, Livewire 4, Blade/Alpine, Tailwind 4, Vite 8, Tiptap 3, Pest, PHPStan, Pint.

## 1. Cadangan dan bukti penggunaan

- [x] Cadangkan sumber/config sebelum perubahan ke TEMP dan catat hash `.env` tanpa menampilkan nilainya.
- [x] Temukan file/command starter dan skrip debugging; verifikasi referensi sebelum penghapusan.

## 2. Pembersihan dan konfigurasi

- [x] Hapus `_smoke_cert.php`, Kernel console lama yang tidak didaftarkan, command `inspire`, dan unit test `expect(true)`.
- [x] Hapus komentar layout yang mengulang markup dan deklarasi kosong; pertahankan PHPDoc tipe serta komentar yang menjelaskan keputusan/perlindungan data.
- [x] Perbarui metadata Composer/npm dan lock file, abaikan `.env`/artefak sementara, serta sertakan README dalam export.
- [x] Rapikan `.env.example`: SQLite default, APP_URL dengan port, locale Indonesia, session/cache database, mail log, Google OAuth opsional, contoh MySQL/SMTP.
- [x] Tambahkan `scripts/prepare-local.php`: buat `.env` bila belum ada, tolak environment nonlokal, hasilkan APP_KEY hanya jika kosong, dan buat file SQLite hanya jika belum ada. Integrasikan `composer setup` dengan migrate/seed, storage link, npm ci, build.
- [x] Rapikan workflow CI: hapus Flux credentials, gunakan lock file, lint check, Node test, PHP extensions dan versi runtime yang sesuai. Hilangkan override CORS terbuka dari Vite.

## 3. Dokumentasi

- [x] Buat README Bahasa Indonesia: deskripsi, screenshot, fitur dan role, stack/arsitektur, instalasi baru, MySQL, env, akun demo, pengembangan, pemeriksaan, pemeliharaan dan batas fitur.
- [x] Simpan screenshot pilihan dan bukti QA historis yang dirujuk audit ke `docs/`, sehingga folder output sementara dapat diabaikan.
- [x] Tambahkan laporan Tahap 4 dengan perubahan, verifikasi, dan batas pengujian.

## 4. Verifikasi

- [x] Jalankan `composer validate --strict`, `composer check-platform-reqs`, `composer test`, `npm test`, `npm run build`, `composer audit`, dan `npm audit`.
- [x] Jalankan instalasi dari source/config bersih di direktori TEMP; verifikasi demo login, health, media, serta key/SQLite yang tidak berubah ketika bootstrap diulang. Gunakan `serve --no-reload` untuk override environment.
- [x] Pastikan tidak ada log debugging/skrip sementara pada sumber aplikasi, semua tautan dokumentasi lokal valid, `.env` pengguna sama, dan fingerprint tabel domain MySQL tidak berubah.
- [x] Review akhir dan selesaikan temuan yang relevan. Tidak melakukan deployment, commit, atau push.

**Catatan review:** penolakan bootstrap kini memakai exit code 1 secara eksplisit setelah ditemukan handler exception CLI Laravel dapat menampilkan exception dengan exit code 0 di luar Artisan. Review screenshot juga memperbaiki flex shrink dan label pencarian beranda. Bukti akhir tersedia di `audit/stage-4-results.md`.
