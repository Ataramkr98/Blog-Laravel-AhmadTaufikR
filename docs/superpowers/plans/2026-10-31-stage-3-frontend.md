# Tahap 3: frontend dan UI/UX

**Goal:** Menyelesaikan masalah frontend pada audit, memakai backend Tahap 2, dan memverifikasi alur desktop/mobile.

**Architecture:** Pertahankan Blade dan Livewire dengan satu runtime Alpine yang dibundel melalui Vite. Gunakan komponen feedback bersama, progressive enhancement formulir POST, dialog glosarium yang dapat dipakai artikel/lesson, dan URL media publik yang mengikuti origin aktif.

**Scope:** Frontend, penghubung state yang diperlukan, dependensi JavaScript, tes regresi perilaku, serta QA browser. README dan standarisasi lingkungan tetap Tahap 4. Tidak menjalankan migrasi destruktif atau mengubah progres lama untuk pengujian UI.

- [x] Cadangkan sumber dan tulis tes gagal untuk portabilitas media, data publik dinamis, dan kontrol editor kursus.
- [x] Perbarui dependensi JavaScript terdampak advisory dan aktifkan typography untuk konten/editor.
- [x] Perbaiki bootstrap Alpine/Livewire, loading, feedback offline/error, formulir async, clipboard, dan editor tanpa proxy reaktif.
- [x] Rapikan navigasi publik/admin, fokus keyboard, label input, responsivitas dan tampilan cetak.
- [x] Tambahkan Login Akun Demo satu klik dan rapikan registrasi sesuai data yang disimpan backend.
- [x] Lengkapi kontrol kursus, glosarium lesson, empty state, validasi, dan pencarian admin.
- [x] Ganti klaim/trending/profil statis dengan data yang tersedia; rapikan URL/fallback media.
- [x] Jalankan tes, analisis statis, build, audit dependency, review kode, dan QA browser desktop/mobile termasuk gagal/offline.

Rancangan mengikuti daftar perbaikan Tahap 1 dan persetujuan pengguna untuk melanjutkan Tahap 3. Workspace tidak memiliki metadata Git; sumber dicadangkan ke TEMP sebelum perubahan.

QA browser awal memakai MySQL karena `artisan serve` memfilter environment subprocess saat reload aktif. Semua perubahan uji dibersihkan dengan verifikasi identitas/baris; data lama dicocokkan dengan cadangan. QA terakhir menggunakan `--no-reload`, sentinel SQLite, dan fingerprint 23 tabel MySQL sebelum/sesudah. Lihat `audit/stage-3-results.md`.
