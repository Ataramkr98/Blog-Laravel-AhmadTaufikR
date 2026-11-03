# BioLearn

Platform pembelajaran biologi berbahasa Indonesia untuk siswa SMA dan mahasiswa. BioLearn menghubungkan artikel, istilah ilmiah, kuis, kursus terstruktur, progres belajar, dan sertifikat dalam satu aplikasi, dengan CMS untuk penulis dan administrator.

![BioLearn pada desktop](docs/screenshots/home-desktop.png)

## Fitur utama

- **Materi sesuai jenjang:** pencarian, filter SMA/PT, kategori, tag, dan pagination dengan state di URL.
- **Pembaca interaktif:** glosarium dalam artikel dan pelajaran, kuis beserta pembahasan, serta referensi ilmiah pada materi PT.
- **Kursus dan evaluasi:** enrollment, modul, pelajaran wajib, progres, ujian akhir, riwayat percobaan, serta sertifikat dengan halaman verifikasi publik dan tampilan cetak.
- **CMS editorial:** editor Tiptap, unggahan media WebP, pengelolaan glosarium/kursus/pengguna, alur moderasi, dan dashboard kunjungan.
- **Autentikasi:** login berbasis sesi, registrasi, pemulihan password, Google OAuth opsional, dan login demo satu klik.
- **Pengalaman responsif:** navigasi mobile, dialog dengan pengelolaan fokus, indikator proses, empty state, pesan validasi, dan notifikasi kegagalan request.
- **Newsletter:** pencatatan langganan serta tautan berhenti berlangganan. Pengiriman kampanye massal belum disediakan.

| Role | Akses |
| --- | --- |
| Tamu | Materi terbit, kuis artikel, pencarian glosarium, katalog kursus, verifikasi sertifikat |
| Pembaca | Akses tamu, enrollment, progres, ujian akhir, dashboard kursus, sertifikat pribadi |
| Penulis | Dashboard CMS, artikel miliknya, pengajuan review, profil |
| Admin | Moderasi, seluruh artikel, kursus, glosarium, pengguna, pengaturan sistem |

## Tech stack dan arsitektur

| Lapisan | Teknologi |
| --- | --- |
| Backend | PHP 8.4.1+, Laravel 13, Eloquent ORM |
| UI | Blade, Livewire 4, Alpine dari runtime Livewire, Tailwind CSS 4 |
| Editor dan build | Tiptap 3, Vite 8, npm |
| Database | PostgreSQL Neon untuk instance terhubung; SQLite untuk instalasi offline; MySQL juga didukung |
| Autentikasi | Laravel session guard, middleware role, policies, Laravel Socialite untuk Google |
| Media | Disk publik Laravel, PHP GD dengan dukungan WebP |
| QA | Pest/PHPUnit, Larastan/PHPStan, Laravel Pint, Node test runner |

Arsitektur berupa **monolith full-stack**. Blade dan Livewire berjalan pada aplikasi Laravel yang sama; frontend tidak memerlukan server API terpisah. Formulir menggunakan CSRF, validasi Laravel, serta respons redirect untuk browser atau JSON error untuk request yang memintanya. Dependensi dipatok melalui `composer.lock` dan `package-lock.json`.

```mermaid
flowchart LR
    Browser[Blade · Alpine · Tiptap] --> Web[Routes · Controllers · Livewire]
    Web --> Access[Session · Middleware · Policies]
    Access --> ORM[Eloquent · Transaksi]
    ORM --> DB[(Neon PostgreSQL / SQLite / MySQL)]
    Web --> Media[Public storage · WebP]
```

## Instalasi lokal

### Prasyarat

- PHP **8.4.1+** dan Composer 2.
- Node.js **22.12+** beserta npm. Verifikasi lokal terakhir menggunakan Node 24.14.1.
- Ekstensi PHP: PDO, `pdo_pgsql` untuk Neon, `pdo_sqlite`/`sqlite3` untuk tes dan demo offline, `gd` dengan WebP, `mbstring`, `dom`/XML, `fileinfo`, `curl`, dan ekstensi standar yang diperiksa Composer. Tambahkan `pdo_mysql` untuk MySQL.
- Direktori `storage/` dan `bootstrap/cache/` dapat ditulis. Pada Linux, font DejaVu dapat digunakan untuk teks pada sampul kursus yang dibuat seeder.
- Koneksi internet saat instalasi dependensi dan build awal; build mengunduh Geist Mono lalu menyajikannya sebagai aset lokal.

### Mulai dengan SQLite

Di dalam direktori proyek hasil clone atau ekstraksi:

```sh
composer setup
composer dev
```

Buka **http://localhost:8000**, lalu pilih **Login Akun Demo** di halaman masuk.

`composer setup` memasang dependensi PHP dari lock file, menyalin `.env.example` jika `.env` belum ada, membuat `APP_KEY` jika kosong, membuat file SQLite jika belum ada, menjalankan migrasi beserta seeder, membuat storage link, menjalankan `npm ci`, dan membangun aset. Perintah ini ditujukan untuk instalasi lokal dengan data demonstrasi; hanya environment `local`/`testing` yang diterima. Key dan file database yang sudah ada dipertahankan. Konfigurasi yang sudah di-cache harus dibersihkan terlebih dahulu dengan `php artisan config:clear`.

Seeder menyediakan **12 artikel, 4 kursus, 16 istilah glosarium**, soal, referensi, contoh kunjungan, dan akun demo beserta progres serta sertifikat. Data tersebut bersifat fiktif untuk demonstrasi portofolio.

### Menggunakan Neon PostgreSQL

Instance proyek yang terhubung menggunakan Neon. `.env.example` tetap menyediakan SQLite agar clone baru dapat dijalankan tanpa kredensial cloud. Untuk memakai Neon, isi `.env` menggunakan connection string dari konsol Neon:

```dotenv
DB_CONNECTION=pgsql
DB_URL="postgresql://USER:PASSWORD@HOST-pooler.REGION.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
DB_POOLED=true
DB_DIRECT_HOST=HOST.REGION.aws.neon.tech
DB_SSLMODE=require
DB_CHANNEL_BINDING=require
DB_CONNECT_TIMEOUT=15
```

Gunakan hostname tanpa `-pooler` untuk `DB_DIRECT_HOST`. Aplikasi memakai endpoint pooled; Laravel otomatis memakai koneksi direct untuk migrasi. Connector proyek meneruskan `channel_binding` dan timeout ke libpq, sehingga parameter keamanan URL tetap diberlakukan. Susunan pooled/direct mengikuti [panduan koneksi Neon](https://neon.com/docs/connect/connection-pooling) dan [dokumentasi Laravel](https://laravel.com/framework/docs/database#pooled-postgresql-connections).

Migrasi PostgreSQL mengaktifkan [ekstensi `citext`](https://neon.com/docs/extensions/citext) untuk mempertahankan pencarian serta keunikan email tanpa membedakan huruf besar/kecil. Pencarian materi dan glosarium menggunakan `ILIKE` melalui `whereLike` Laravel. Ini menjaga perilaku aplikasi saat beralih dari MySQL ke PostgreSQL.

Hapus pengaturan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_CHARSET` dari koneksi sebelumnya agar tidak bercampur dengan URL. Simpan URL yang berisi password hanya pada `.env` atau secret environment server. Pada database Neon kosong, setelah dependensi tersedia:

```sh
php artisan config:clear
php artisan migrate
php artisan db:seed
```

Migrasi membuat skema; seeder memasukkan data demonstrasi. Keduanya tidak menyalin database lama. Pemindahan data instance yang sudah ada harus mempertahankan ID, referensi, JSON, boolean, serta sequence PostgreSQL. Catatan pemindahan instance ini tersedia di [laporan Neon](audit/neon-database-results.md).

### Menggunakan MySQL

Jika memilih MySQL sejak awal, buat database kosong `biology_learn` dan pengguna lokal yang memiliki akses ke database tersebut. Buat `.env` dari contoh sebelum menjalankan `composer setup`:

```sh
php -r "copy('.env.example', '.env');"
```

Ganti bagian database pada `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biology_learn
DB_USERNAME=biology_learn
DB_PASSWORD=your-local-password
```

Kemudian jalankan `composer setup`. Hapus `DB_URL` dan opsi Neon jika beralih dari PostgreSQL. Jangan menetapkan `DB_DATABASE` ke path SQLite ketika menggunakan MySQL. Mengganti koneksi setelah instalasi membuat aplikasi memakai database yang berbeda; langkah ini tidak menyalin data antardatabase. SQLite, MySQL, dan PostgreSQL Neon telah diverifikasi.

### Proyek yang sudah memiliki data

Gunakan alur pembaruan berikut setelah membuat cadangan database dan media:

```sh
composer install
npm ci
php artisan config:clear
php artisan migrate
npm run build
```

Seeder dapat dijalankan secara eksplisit dengan `php artisan db:seed`. Seeder berulang mempertahankan ID pelajaran, progres, riwayat ujian, sertifikat, serta perubahan pengajar pada struktur kursus. Email demo dicadangkan: seeder mengembalikan role akun tersebut menjadi pembaca dan password ke nilai demo. Hindari `migrate:fresh` pada database yang ingin dipertahankan.

## Konfigurasi `.env`

Gunakan [.env.example](.env.example) sebagai acuan. File `.env` dan variasinya diabaikan Git; simpan kredensial pribadi hanya pada environment lokal/server.

| Variabel | Kegunaan/default lokal |
| --- | --- |
| `APP_NAME`, `APP_URL` | Identitas aplikasi dan URL absolut, default `http://localhost:8000` |
| `APP_ENV`, `APP_DEBUG` | `local` dan `true` untuk pengembangan |
| `APP_KEY` | Dibuat bootstrap lokal; pertahankan saat memperbarui instalasi yang sama |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `id`; pesan validasi dan pemulihan password berbahasa Indonesia |
| `DB_CONNECTION` | `pgsql` untuk Neon; `sqlite` pada contoh instalasi offline |
| `DB_URL` | Connection string Neon; kosongkan untuk demo SQLite/MySQL tanpa URL |
| `DB_POOLED`, `DB_DIRECT_HOST` | Pooler untuk aplikasi serta hostname direct untuk migrasi Neon |
| `DB_SSLMODE`, `DB_CHANNEL_BINDING`, `DB_CONNECT_TIMEOUT` | TLS, channel binding libpq, serta batas waktu koneksi PostgreSQL |
| `DB_DATABASE` | Kosongkan/hapus variabel untuk path SQLite default; nama database untuk MySQL |
| `SESSION_DRIVER`, `CACHE_STORE` | `database`; tabel dibuat melalui migrasi |
| `QUEUE_CONNECTION` | `database`; worker diperlukan jika menambahkan pekerjaan antrean |
| `FILESYSTEM_DISK` | `public`; media tersedia melalui `public/storage` |
| `MAIL_MAILER` | `log`; pesan pemulihan password ditulis ke `storage/logs/laravel.log` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | Isi sesuai penyedia jika `MAIL_MAILER=smtp` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Identitas pengirim email |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Opsional, kredensial OAuth Google |
| `GOOGLE_REDIRECT_URI` | `${APP_URL}/auth/google/callback`; harus sama dengan redirect URI pada konsol Google |
| `SESSION_SECURE_COOKIE` | Aktifkan pada server HTTPS |

Setelah mengubah `.env`, jalankan `php artisan config:clear` dan mulai ulang server pengembangan. Host `localhost` dan `127.0.0.1` memiliki cookie berbeda; gunakan host yang konsisten dengan `APP_URL`.

Login Google tanpa kredensial akan menampilkan pesan konfigurasi. Login email dan demo tetap dapat digunakan. Driver email `log` tidak mengirim email ke inbox; gunakan SMTP untuk menguji pengiriman nyata.

## Akun demo dan alur presentasi

| Akun | Email | Password |
| --- | --- | --- |
| **Demo pembaca** | `demo@biology.learn` | `BioLearnDemo2026!` |
| Admin lokal dari seeder | `admin@biology.learn` | `password` |
| Penulis lokal dari seeder | `penulis@biology.learn` | `password` |

Kredensial admin/penulis berlaku pada database baru; seeder tidak mengganti password akun tersebut jika sudah ada. Untuk demonstrasi publik, gunakan tombol demo pembaca. Ganti password akun berhak akses CMS sebelum memublikasikan instalasi.

Alur singkat untuk perekrut:

1. Buka `/sign-in` dan klik **Login Akun Demo**. Tombol mengisi lalu mengirim formulir login biasa.
2. Buka **Kursus Saya** (`/my-courses`) untuk melihat satu kursus selesai dan satu kursus yang sedang dipelajari.
3. Buka sertifikat kursus selesai; gunakan tombol cetak atau **Save as PDF** dari browser.
4. Lanjutkan pelajaran pada kursus aktif, buka definisi glosarium, lalu tandai pelajaran selesai.
5. Jelajahi `/articles`, ubah filter jenjang, dan coba kuis pada artikel.

![Login demo pada mobile](docs/screenshots/login-mobile.png)

## Pengembangan dan pemeriksaan

```sh
# Terminal pertama: server Laravel
composer dev

# Terminal kedua: Vite dengan hot reload
npm run dev

# Opsional: worker antrean
composer dev:queue
```

Build statis dari `composer setup` sudah cukup untuk menjalankan aplikasi tanpa server Vite. Jalankan kembali `npm run build` setelah mengubah frontend jika tidak menggunakan hot reload.

```sh
composer validate --strict
composer check-platform-reqs
composer test
npm test
npm run build
composer audit
npm audit
```

`composer test` menjalankan pemeriksaan format Pint, analisis statis PHPStan/Larastan, lalu tes fitur Pest. PHPUnit memaksa SQLite **in-memory** dan mengosongkan `DB_URL`, sehingga tes tidak memakai database Neon dari `.env` atau environment terminal. Cache/session menggunakan `array`, mail `array`, dan antrean `sync` sesuai [phpunit.xml](phpunit.xml). `npm test` menguji normalisasi pesan error serta pembatasan redirect pada formulir.

Workflow [Quality checks](.github/workflows/tests.yml) menjalankan instalasi dari lock file, build, tes, analisis statis, dan audit dependency pada PHP 8.4/8.5 serta Node 22. Workflow tidak memerlukan kredensial Flux atau Google. Hasil GitHub Actions baru tersedia setelah repositori di-push dan workflow dijalankan.

## Struktur proyek

```text
app/Actions/          Sinkronisasi konten kursus dengan perlindungan progres
app/Http/             Controller dan middleware autentikasi/role
app/Livewire/         Filter, kuis, ujian, serta pengelolaan CMS
app/Models/           Model Eloquent dan relasi domain
app/Policies/         Otorisasi artikel dan kursus
app/Support/          Sanitasi HTML, glosarium, media dan pembuatan sampul
database/             Migrasi, factory, seeder, dan aset WebP bawaan
resources/            Blade, CSS, runtime UI, dan editor
scripts/              Bootstrap instalasi lokal
tests/                Regresi fitur dan JavaScript
audit/                Laporan perbaikan serta verifikasi per tahap
docs/                 Screenshot, bukti QA, dan rencana implementasi
```

## Operasional dan troubleshooting

| Gejala | Langkah pemeriksaan |
| --- | --- |
| `could not find driver` | Periksa `php -m`; aktifkan `pdo_sqlite` atau `pdo_mysql` pada PHP CLI/server yang digunakan |
| Gambar belum tampil | Jalankan `php artisan storage:link`, periksa `storage/app/public` dan `APP_URL`. Di Windows, pembuatan symlink dapat memerlukan Developer Mode atau terminal dengan hak administrator |
| `Vite manifest not found` | Jalankan `npm ci` lalu `npm run build` |
| Browser mencoba Vite yang sudah mati | Hentikan Vite secara normal; jika tertinggal, hapus hanya file `public/hot`, lalu build ulang |
| Error 419 | Muat ulang formulir dan gunakan host yang sama; periksa sesi serta `APP_KEY` |
| SQLite belum ada | Jalankan `php scripts/prepare-local.php` untuk instalasi lokal, lalu `php artisan migrate --seed` |
| Halaman maintenance | Admin/penulis dapat masuk melalui `/sign-in` dan menonaktifkan maintenance di pengaturan CMS |

Untuk QA browser, gunakan salinan proyek dan database tersendiri. Jika environment ditentukan dari terminal, jalankan `php artisan serve --no-reload`; verifikasi koneksi salinan sebelum mencoba alur tulis. Opsi ini menjaga environment pada subprocess server Laravel.

Untuk server publik, gunakan document root **`public/`**, konfigurasi database/HTTPS dan mail yang sesuai, `APP_ENV=production`, `APP_DEBUG=false`, serta key instalasi yang stabil. Pasang dependensi dengan `composer install --no-dev --optimize-autoloader`, bangun aset dengan dependensi build tersedia, jalankan `php artisan migrate --force` dan `php artisan storage:link`, lalu cache konfigurasi/view. `composer setup` adalah alur lokal yang juga memasukkan akun dan konten demonstrasi; pilih secara eksplisit data demo yang ingin dipublikasikan. Deployment belum dijalankan oleh pekerjaan ini.

## Bukti QA dan batas verifikasi

Laporan terperinci: [backend dan seeder](audit/stage-2-results.md), [frontend dan UI/UX](audit/stage-3-results.md), serta [standardisasi portofolio](audit/stage-4-results.md). Laporan mencatat hasil tes, QA browser, dan batas cakupannya; screenshot menggunakan data demonstrasi.

Google OAuth terhadap akun provider nyata, pengiriman SMTP nyata, screen reader fisik, dan browser selain Chrome belum diuji end-to-end. Sertifikat merupakan bukti penyelesaian kursus di aplikasi, bukan sertifikasi akademik terakreditasi. Materi dan akun seeder digunakan untuk demonstrasi.

## Kontribusi

Pasang dependensi dari lock file, kerjakan perubahan dalam branch tersendiri, dan sertakan tes regresi untuk perubahan perilaku. Sebelum mengajukan perubahan, jalankan `composer test`, `npm test`, serta `npm run build`. Jangan sertakan `.env`, database lokal, `vendor/`, `node_modules/`, atau hasil build dalam commit.
