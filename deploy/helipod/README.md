# Deployment Helipod — biology-learn

Aplikasi menggunakan container PHP 8.4/Apache terpisah dan Caddy yang sudah ada pada VPS `169.58.217.236` (SSH port `24100`).

- URL: https://blog-learn.taufikr.web.id
- Direktori server: `/root/biology-learn-deploy`
- Rilis pertama: `20261101-helipod-01`
- Port pemeriksaan lokal VPS: `127.0.0.1:8083`
- Docker network: `tera-vps_default`; proxy dipercaya hanya pada `172.18.0.0/16`.
- Batas memori container: 192 MB; maksimal empat worker Apache.
- Database: koneksi Neon PostgreSQL dari konfigurasi project yang sudah ada. Tidak menggunakan `migrate:fresh` atau menjalankan ulang seeder.
- Upload dan runtime storage: `shared/storage`, terpisah dari image/rilis.
- Environment privat: `.env.production` pada direktori server, mode 600. APP_KEY dipertahankan dari instalasi project.
- HTTPS: Caddy, secure session cookies, dan redirect HTTPS yang diproses melalui konfigurasi trusted proxy.

## Verifikasi dan operasional

Jalankan dari direktori server:

```sh
docker compose ps
docker compose logs --tail=100 biolearn
curl --fail http://127.0.0.1:8083/up
docker compose exec biolearn php deploy/helipod/operations.php status
```

Tes sebelum deployment: BioLearn 102 tes PHP dan 3 tes JavaScript; warehouse 77 tes PHP. Build produksi frontend kedua aplikasi berhasil. File `config/trustedproxy.php` menerima daftar IP/CIDR melalui `TRUSTED_PROXIES`; tanpa konfigurasi, tidak ada proxy tambahan yang dipercaya.

## Rilis berikutnya

Bangun frontend dengan `npm ci` dan `npm run build` di komputer build. Paketkan source, lockfile, `public/build`, dan folder `deploy`; kecualikan `.env`, vendor lokal, node_modules, cache lokal, serta database SQLite. `.dockerignore` juga mencegah file tersebut masuk image. Upload arsip `biology-learn.tar.gz`, `biology-learn-media.tar.gz`, dan environment privat `biology-learn.env.production` ke direktori server. Gunakan tag rilis baru.

```sh
bash /root/install-release.sh biology-learn RELEASE_TAG
bash /root/activate-release.sh biology-learn RELEASE_TAG
```

Salinan kedua script terdapat di folder ini. Script instalasi hanya menyiapkan dan membangun rilis. Script aktivasi membuat backup PostgreSQL dengan client PostgreSQL 18, memverifikasi daftar objek backup, menjalankan migrasi non-destruktif, lalu menunggu container sehat. Rahasia koneksi backup sementara dihapus saat script selesai. Konten upload lama dipertahankan.

## Cadangan dan pemulihan

Backup database berada di `backups/database-before-RELEASE_TAG.dump`, beserta daftar objek `.contents`. Backup ini mengandung data privat; akses hanya untuk administrator server. Kredensial aplikasi tidak dimasukkan ke image.

Pada pembaruan berikutnya, simpan environment dan tag rilis aktif sebelum aktivasi. Untuk mengembalikan versi aplikasi, set ulang `RELEASE_TAG` dan `APP_SOURCE` di `.env` ke rilis sebelumnya lalu jalankan `docker compose up -d --wait`. Pemulihan database dilakukan terpisah setelah meninjau perubahan migrasi; script tidak mereset database otomatis.

Konfigurasi Caddy utama berada di `/root/tera-deploy/Caddyfile`. Salinannya dibuat sebelum penambahan kedua aplikasi. Untuk membatalkan publikasi rilis pertama, pulihkan isi cadangan Caddy ke file yang sama, jalankan `docker exec tera-vps-edge-1 caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile`, kemudian hentikan container project dengan `docker compose stop`. Data persisten tetap tersimpan.

## Integrasi

MAIL_MAILER memakai konfigurasi project; jika masih `log`, email tidak dikirim ke inbox. OAuth Google memerlukan kredensial dan callback URL publik yang sesuai.
Akun CMS admin/penulis yang masih memakai password bawaan diganti saat aktivasi; kredensial baru disimpan pada server di cms-credentials.json, dengan salinan privat di folder SSH komputer deployment. Kredensial tidak termasuk source atau image. Akun demo pembaca dipertahankan.

## Pemetaan jaringan dan verifikasi publik

Publikasi selesai pada 1 November 2026. DNS kedua domain mengarah ke `169.58.217.236`. Pemetaan berikut telah diverifikasi pada tab Network VPS Helipod:

| Domain | Protokol / port publik | Port internal |
| --- | --- | --- |
| blog-learn.taufikr.web.id | HTTP / 80 | 80 |
| warehouse.taufikr.web.id | HTTP / 80 | 80 |
| blog-learn.taufikr.web.id | HTTPS / 443 | 443 |
| warehouse.taufikr.web.id | HTTPS / 443 | 443 |

Caddy menggunakan domain tersebut, menerbitkan sertifikat HTTPS, dan mengalihkan HTTP ke HTTPS. Konfigurasi aktif berada di `/root/tera-deploy/Caddyfile`. `APP_URL` dan `ASSET_URL` masing-masing aplikasi memakai domain publik yang sesuai; callback Google BioLearn memakai `https://blog-learn.taufikr.web.id/auth/google/callback`.

Pemeriksaan dari internet berhasil untuk health check, login demo, lima halaman per aplikasi, empat aset per halaman login, secure cookie, dan redirect ke domain HTTPS yang benar. Akses HTTP ke `.env`, `composer.json`, serta log privat ditolak. Halaman tidak lagi mengacu ke domain sementara. Dashboard warehouse juga diverifikasi melalui browser setelah tombol Login Akun Demo dipakai.

Situs yang sudah ada, `accurate.taufikr.web.id` dan `hr-management.taufikr.web.id`, tetap merespons HTTP 200. Nama service Docker kedua aplikasi dibuat unik (`biolearn` dan `warehouse`) agar alias `web` milik aplikasi lama tidak tertimpa.

Bukti visual dan hasil pemeriksaan tersimpan di `output/helipod-production/` pada workspace lokal. Sertifikat Caddy tersimpan dalam volume persisten layanan edge dan diperbarui otomatis oleh Caddy.

Rujukan pengaturan jaringan: https://docs.helipod.io/docs/guides/vps-ports-and-domains
