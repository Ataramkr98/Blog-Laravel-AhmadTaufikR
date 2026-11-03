# Pemindahan database ke Neon PostgreSQL

Tanggal: 31 Oktober 2026. Status: koneksi Neon aktif dan terverifikasi.

## Hasil

Aplikasi memakai PostgreSQL Neon **18.6** melalui connection string pada `.env` lokal. Endpoint pooled digunakan aplikasi, sedangkan migrasi menggunakan endpoint direct yang mewarisi database dan kredensial yang sama. TLS dan channel binding diwajibkan. Koneksi, migrasi, serta login demo sudah diuji langsung.

Database Neon awalnya kosong. Data dari **26 tabel MySQL** disalin dalam transaksi, diperiksa per record setelah normalisasi tipe boolean/numerik/JSON, dan sequence PostgreSQL disinkronkan. Tidak menjalankan seeder ulang pada data yang dipindahkan. ID, waktu, password hash, progres, soal/jawaban, dan relasi sertifikat dipertahankan.

| Data | Jumlah |
| --- | ---: |
| Pengguna | 8 |
| Artikel | 12 |
| Glosarium | 16 |
| Kursus | 4 |
| Modul / pelajaran | 10 / 14 |
| Enrollment | 9 |
| Progres pelajaran | 22 |
| Percobaan ujian akhir | 7 |
| Sertifikat | 3 |
| Media asset | 29 |
| Langganan newsletter | 5 |

Pemeriksaan setelah QA menunjukkan tidak ada perbedaan nilai record pada 26 tabel Neon terhadap sumber. Data pada 26 tabel MySQL sumber tetap utuh. Cache dan sesi aktif baru menggunakan penyimpanan aplikasi yang dikonfigurasi; cache/sesi lama tidak diimpor. File media tetap berada pada disk publik Laravel.

## Penyesuaian kode

- [Konfigurasi database](../config/database.php) mendukung pooled/direct PostgreSQL, TLS, channel binding, dan timeout koneksi.
- [Connector PostgreSQL](../app/Database/Connectors/PostgresConnector.php) meneruskan `channel_binding` dan `connect_timeout` ke libpq. Connector bawaan versi Laravel terpasang belum meneruskan kedua opsi URL tersebut ke DSN. Nilai channel binding serta timeout diperiksa sebelum koneksi.
- Dua migrasi status artikel disesuaikan untuk PostgreSQL: perubahan enum melalui `ALTER COLUMN ... CHECK` semula gagal dengan SQLSTATE `42601`. Migrasi kini mengelola CHECK constraint secara eksplisit serta melepaskan constraint lama saat memperluas workflow.
- [Migrasi email](../database/migrations/2026_10_31_000003_preserve_case_insensitive_emails_on_postgresql.php) memakai `citext` untuk email pengguna, newsletter, dan token pemulihan password, sehingga pencarian dan keunikannya tetap mengabaikan kapitalisasi. Migrasi tidak mengubah driver SQLite/MySQL.
- Pencarian artikel, glosarium, dan pengguna memakai `whereLike`/`orWhereLike` Laravel agar PostgreSQL menggunakan `ILIKE`.
- [Resolver media](../app/Support/PublicMediaUrl.php) mengenali URL development lama berakhiran `.test` ketika file publik yang sesuai masih tersedia. Sampul dari database lama tampil pada origin aplikasi aktif; URL CDN eksternal tetap dipertahankan.
- [Konfigurasi pengujian](../phpunit.xml) mengatur environment database pada `$_SERVER` dan environment proses. [TestCase](../tests/TestCase.php) menolak koneksi selain SQLite in-memory sebelum trait RefreshDatabase dijalankan. Ini mencegah pengujian memakai database Neon dari `.env` atau environment terminal.
- README dan `.env.example` memuat panduan Neon dengan placeholder. Kredensial asli tidak ditulis pada dokumentasi, source code, atau artefak QA publik.

## Verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Koneksi runtime Laravel | `pgsql`, pooled aktif, direct endpoint tersedia, TLS/channel binding `require` |
| Migrasi Neon | Seluruh migrasi selesai, tidak ada yang pending |
| Migrasi skema sementara | Migrasi penuh, reset/rollback, lalu migrasi ulang berhasil |
| Status artikel | Keenam status workflow diterima; status tidak valid ditolak |
| Email | Lookup dengan kapitalisasi berbeda berhasil; duplikat berbeda kapitalisasi ditolak |
| Pencarian | `dna` menemukan 6 artikel; pencarian glosarium `atp` menemukan hasil |
| Tulis model | JSON soal/jawaban dan boolean berhasil round trip pada koneksi pooled; seluruh record uji di-rollback |
| Integritas data | 26 tabel sesuai sumber; MySQL sumber tidak berubah; sequence valid |
| `composer test` | Pint, PHPStan, **102 tes / 487 assertions** lulus |
| Isolasi terhadap environment terminal | 20 tes koneksi/media lulus ketika terminal sengaja diberi URL PostgreSQL lokal yang tidak dapat digunakan; suite tetap memakai SQLite in-memory |
| QA Chrome | Beranda dan health 200, demo login berhasil, dashboard aktif/selesai serta sertifikat tampil, pencarian huruf kecil berhasil |
| Media dan runtime UI | Tidak ada gambar gagal, request gagal, atau error JavaScript pada alur penutup |

Skema sementara untuk pengujian migrasi sudah dihapus. Browser serta server QA sudah dihentikan. Pemeriksaan browser memakai database Neon aktif; tidak ada record QA permanen yang ditinggalkan.

## Bukti

- [Hasil pemindahan data](../docs/qa/neon/data-transfer-results.json)
- [Verifikasi koneksi aktif](../docs/qa/neon/active-connection-checks.json)
- [Migrasi dan constraint PostgreSQL](../docs/qa/neon/postgres-migration-checks.json)
- [Integritas setelah pengujian](../docs/qa/neon/final-integrity.json)
- [Login demo, media, dashboard, dan sertifikat di browser](../docs/qa/neon/neon-browser-check.txt)

Cadangan `.env` sebelum pemindahan, konfigurasi koneksi sumber, dan snapshot data disimpan secara privat pada direktori TEMP lokal. Lokasi cadangan dicatat pada `biolearn-neon-work-path.txt` di TEMP. Cadangan tersebut mengandung kredensial/data akun dan tidak dimasukkan ke repositori. APP_KEY dan nilai environment selain pengaturan database dipertahankan.

## Catatan operasional

Koneksi `.env` saat ini sudah mengarah ke Neon. Jika memakai worker antrean atau proses aplikasi yang berumur panjang, mulai ulang proses tersebut agar membaca konfigurasi baru. Untuk kembali ke database MySQL lama, pulihkan `.env` dari cadangan privat lalu jalankan `php artisan config:clear`; data MySQL masih tersedia.

Deployment aplikasi dan pengunggahan media ke object storage tidak dilakukan pada pekerjaan ini. Pengujian CI remote belum dijalankan; workflow sudah menyertakan ekstensi `pdo_pgsql`. Laporan Tahap 2–4 tetap menjadi rekaman hasil sebelum pemindahan database ini.

Referensi konfigurasi: [Neon connection pooling](https://neon.com/docs/connect/connection-pooling), [Laravel pooled PostgreSQL](https://laravel.com/framework/docs/database#pooled-postgresql-connections), [Neon citext](https://neon.com/docs/extensions/citext), dan [opsi koneksi libpq](https://www.postgresql.org/docs/current/libpq-connect.html).
