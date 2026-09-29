# Antriin — Sistem Antrian Multi-Loket

Aplikasi antrian untuk kantor cabang, instansi, atau klinik yang punya lebih dari satu loket. Pelanggan ambil nomor sendiri lewat kios tanpa perlu login, petugas memanggil nomor dari panel yang masing-masing dipakai per loket, dan nomor yang dipanggil langsung muncul di layar display besar yang tertempel di ruang tunggu.

Dibangun dengan Laravel 12 dan Tailwind CSS 4. Tidak ada Livewire, tidak ada Filament, tidak ada admin panel generator. Seluruh antarmuka ditulis tangan dari Blade. Realtime-nya lewat Pusher Channels.

```
Kios (publik)          Panel Petugas (butuh login)        Papan Display (publik)
      |                           |                              ^
      | POST /ambil-antrian       | POST /panel/panggil          | WebSocket
      v                           v                              |
 AntrianService -- broadcast --> AntrianObserver -- broadcast --+
```

---

## Daftar Isi

- [Masalah yang diselesaikan](#masalah-yang-diselesaikan)
- [Fitur](#fitur)
- [Tumpukan teknologi](#tumpukan-teknologi)
- [Menjalankan proyek](#menjalankan-proyek)
- [Akun demo](#akun-demo)
- [Struktur folder](#struktur-folder)
- [Penjelasan folder per bagian](#penjelasan-folder-per-bagian)
- [Alur kerja lengkap](#alur-kerja-lengkap)
- [Basis data](#basis-data)
- [Model dan relasi](#model-dan-relasi)
- [Daftar rute](#daftar-rute)
- [Realtime](#realtime)
- [Menjalankan pengujian](#menjalankan-pengujian)
- [Deploy](#deploy)
- [Yang perlu diperbaiki](#yang-perlu-diperbaiki)
- [Lisensi](#lisensi)

---

## Masalah yang diselesaikan

Pengaturan antrean secara manual cepat membosankan, dan hampir selalu bermasalah di titik yang sama.

**Nomor kembar.** Kalau dua pelanggan menekan tombol ambil nomor di detik yang sama, keduanya membaca nomor terakhir yang sama, lalu keduanya mendapat `A042`. Di `AntrianService` ini diselesaikan dengan `DB::transaction` dipadu `lockForUpdate()`. Baris antrian terakhir dikunci sebelum dihitung, jadi permintaan kedua menunggu sampai transaksi pertama selesai.

**Petugas lupa antrian yang sedang dipanggil.** Kalau petugas me-refresh browser atau keburu ke luar, nomor yang sedang aktif hilang dari layar. Karena status antrian disimpan di database, panel selalu tahu sedang menangani siapa. Begitu halaman dibuka lagi, nomor yang menggantung langsung tampil lagi lengkap dengan tombol `SELESAI` dan `LEWATI`.

**Layar display yang statis.** Monitor di ruang tunggu biasanya butuh laptop atau mini PC yang menyala terus. Di sini, begitu petugas menekan `PANGGIL BERIKUTNYA`, nomor berubah sendiri di browser mana pun yang membuka `/display-board`, tanpa perlu refresh.

Nomor juga di-reset setiap hari, dan formatnya selalu tiga digit dengan awalan huruf layanan: `A001`, `A002`, lalu `B001` untuk layanan berikutnya.

---

## Fitur

- **Kios publik** di `/`. Pelanggan pilih layanan, langsung dapat nomor. Tidak perlu akun, tidak perlu password.
- **Nomor terformat per layanan.** Prefix huruf diambil dari tabel `layanans`, angka tiga digit, restart setiap hari.
- **Panel petugas** di `/panel`. Satu terminal per loket, terlindungi login dan pengecekan role.
- **Antrian menggantung.** Kalau petugas menutup browser di tengah penanganan, nomornya tetap tampil saat halaman dibuka lagi.
- **Aksi `SELESAI` dan `LEWATI`.** Lewati dipakai saat pelanggan tidak merespons, dan tetap menutup waktu penanganan.
- **Papan display real-time** di `/display-board`. Layar besar, angka jumbo, berubah seketika tanpa refresh.
- **Penghitung antrean langsung** di panel. Petugas melihat jumlah antrean bertambah tanpa reload halaman.
- **Penomoran aman dari balapan.** Row level locking, bukan sekadar `max(id) + 1`.
- **Multi-loket.** Beberapa loket bisa melayani satu layanan yang sama, dan antrean dibagi otomatis berdasarkan `waktu_ambil` paling lama.

Yang tidak ada di proyek ini, dan memang tidak diklaim ada: cetak tiket ke printer thermal, integrasi pembayaran, WhatsApp atau SMS, laporan, dan grafik analitik. Nomor antrian hanya ditampilkan sebagai pesan di layar, tidak dicetak.

---

## Tumpunan teknologi

| Lapisan | Teknologi | Versi |
|---|---|---|
| Framework | Laravel | 12.x, terpasang 12.63.0 |
| Runtime | PHP | 8.2 |
| Database | MySQL atau MariaDB | - |
| Styling | Tailwind CSS | 4.x lewat `@tailwindcss/vite` |
| Bundler | Vite | 6.x |
| Realtime | Pusher Channels, `laravel-echo` dan `pusher-js` | 2.3.7 dan 8.5.0 |
| Autentikasi | Tulis tangan memakai `Auth::attempt`, tanpa Breeze atau Jetstream | - |
| Testing | PHPUnit | 11.5 |
| Server development | `php artisan serve`, Vite, dan Pail, digabung jadi `composer dev` | - |

Semua dependensi ada di `composer.json` dan `package.json`. Yang perlu diperhatikan, `laravel/reverb` ikut terpasang sebagai bawaan skeleton Laravel 12, tapi tidak dipakai. Aplikasi di-hardcode ke Pusher di `resources/js/echo.js:7`. File `config/reverb.php` dan `laravel.zip` di root repository adalah sisa bawaan skeleton, bukan bagian dari sistem.

Tidak ada dependensi produksi di sisi JavaScript. Seluruh isi `package.json` adalah `devDependencies`.

---

## Menjalankan proyek

Prasyarat: PHP 8.2 atau lebih baru, Composer 2, Node.js 20 atau lebih baru, dan MySQL yang sudah jalan.

```bash
# 1. Ambil dependensi
composer install
npm install

# 2. Siapkan konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Buat database kosong di MySQL, lalu isi kredensialnya di .env
#    Proyek ini memakai MySQL, bukan SQLite:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=antriin
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Bangun tabel dan isi data contoh
php artisan migrate --seed

# 5. Jalankan
composer dev
```

Perintah `composer dev` menyalakan empat proses sekaligus lewat `concurrently`: web server di `localhost:8000`, worker antrean, `pail` untuk melihat log, dan Vite dengan hot reload.

Kalau mau menjalankan manual secara terpisah:

```bash
php artisan serve
npm run dev
php artisan pail
```

### Realtime butuh kredensial Pusher

Ini bagian yang sering terlewat. Berkas `.env.example` bawaan Laravel tidak memuat satu pun variabel Pusher, jadi setelah menyalin `.env.example` ke `.env`, papan display akan diam. `window.Echo` dibuat dengan key `undefined` dan halamannya tidak pernah berubah. Tambahkan manual:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

Key bisa diambil gratis dari dashboard Pusher. Yang wajib ada hanya `VITE_PUSHER_APP_KEY` dan `VITE_PUSHER_APP_CLUSTER`, karena dua variabel itulah yang dibaca Vite di sisi browser. Setelah menambah variabel baru, jalankan ulang `npm run dev` supaya Vite membaca ulang `.env`.

Kalau lebih suka tidak bergantung pada layanan pihak ketiga, `laravel/reverb` sebenarnya sudah terpasang dan bisa dipakai. Ganti `BROADCAST_CONNECTION` menjadi `reverb`, isi variabel `REVERB_*`, ubah `broadcaster` di `resources/js/echo.js` menjadi `reverb`, lalu jalankan `php artisan reverb:start`. Yang belum ada di repo ini adalah berkas `reverb.config.js`.

### Build untuk produksi

```bash
npm run build
php artisan optimize
```

Hasil `npm run build` masuk ke folder `public/build/`. Folder itu ada di `.gitignore`, jadi harus dibangun ulang di server.

---

## Akun demo

Perintah `php artisan migrate --seed` mengisi data contoh: dua layanan, tiga loket, dan tiga petugas. Semuanya memakai password `password`.

| Nama | Email | Loket | Layanan |
|---|---|---|---|
| Petugas CS 1 | `cs1@antrian.com` | Loket 1 | Customer Service |
| Petugas CS 2 | `cs2@antrian.com` | Loket 2 | Customer Service |
| Petugas Teller | `teller@antrian.com` | Loket 3 | Teller |

Dua layanan bawaannya adalah `Customer Service` dengan prefix `A`, dan `Teller` dengan prefix `B`.

Seeder ini tidak idempotent. `Layanan::create()` dan `User::create()` dipanggil langsung tanpa pengecekan apakah datanya sudah ada. Jalankan `db:seed` dua kali dan tabelnya akan berisi duplikat. Untuk mulai dari nol, kosongkan dulu databasenya.

---

## Struktur folder

```
antriin/
|
+-- app/
|   +-- Events/               dua event broadcast
|   |   +-- AntrianBaru.php         dikirim saat tiket baru dibuat
|   |   +-- NomorDipanggil.php      dikirim saat nomor dipanggil
|   |
|   +-- Exceptions/
|   |   +-- AntrianKosongException.php  "antrean kosong" jadi pesan flash
|   |
|   +-- Http/
|   |   +-- Controllers/
|   |   |   +-- Controller.php         kelas dasar, kosong
|   |   |   +-- AuthController.php     login dan logout
|   |   |   +-- AntrianController.php  semua aksi kiosk, display, panel
|   |   +-- Middleware/
|   |       +-- RoleMiddleware.php    cek hak akses berdasarkan role
|   |
|   +-- Models/
|   |   +-- User.php            petugas, punya relasi ke loket
|   |   +-- Layanan.php         jenis layanan dan prefix huruf
|   |   +-- Loket.php           meja layanan
|   |   +-- Antrian.php         satu tiket antrian
|   |
|   +-- Observers/
|   |   +-- AntrianObserver.php memicu broadcast saat status berubah
|   |
|   +-- Providers/
|   |   +-- AppServiceProvider.php    mendaftarkan observer
|   |
|   +-- Services/
|       +-- AntrianService.php   seluruh logika bisnis antrian
|
+-- bootstrap/
|   +-- app.php                 registrasi routing dan alias middleware
|   +-- providers.php
|
+-- config/                     12 berkas, semuanya masih bawaan Laravel
|
+-- database/
|   +-- migrations/             7 migrasi, 3 domain dan 4 bawaan Laravel
|   +-- seeders/DatabaseSeeder.php
|   +-- factories/UserFactory.php
|   +-- database.sqlite         sisa skeleton, tidak terpakai
|
+-- public/
|   +-- index.php               front controller
|   +-- .htaccess               aturan rewrite untuk Apache
|   +-- build/                  hasil Vite
|
+-- resources/
|   +-- css/app.css             entry point Tailwind v4
|   +-- js/
|   |   +-- app.js              entry point Vite
|   |   +-- bootstrap.js        setup axios
|   |   +-- echo.js             konfigurasi Pusher dan Echo
|   +-- views/
|       +-- Layouts/app.blade.php    satu-satunya layout
|       +-- Auth/Login.blade.php
|       +-- antrian/
|       |   +-- kios.blade.php       halaman kios
|       |   +-- display.blade.php    papan display
|       |   +-- panel.blade.php      panel petugas
|       +-- welcome.blade.php        halaman bawaan Laravel, tidak dipakai
|
+-- routes/
|   +-- web.php                 7 rute aplikasi
|   +-- channels.php            otorisasi channel privat, belum dipakai
|   +-- console.php             perintah artisan bawaan
|
+-- storage/                    log, cache, session, view kompilasi
+-- tests/                      baru 2 berkas contoh bawaan
|
+-- .env.example                template environment
+-- .htaccess                   rewrite ke public/ untuk shared hosting
+-- composer.json               dependensi PHP dan script dev
+-- package.json                dependensi JavaScript
+-- vite.config.js              konfigurasi Vite
+-- phpunit.xml                 konfigurasi PHPUnit
```

---

## Penjelasan folder per bagian

### `app/` - seluruh isi aplikasi

Tidak ada subdirektori yang tidak terpakai di sini, dan tidak ada bagian penting yang hilang. Yang memang tidak ada adalah sebagai berikut. Folder `Jobs/` karena tidak ada pekerjaan latar yang benar-benar dipakai. Folder `Policies/` dan `Notifications/` karena tidak dipakai sama sekali. Folder `Http/Requests/` karena validasi ditulis langsung di dalam controller. Dan folder `Enums/` karena status antrian disimpan sebagai string biasa, bukan enum PHP.

#### `app/Services/AntrianService.php` - jantungnya aplikasi

Ada empat metode, masing-masing dibungkus `DB::transaction()`.

**`ambilNomor(int $layananId)`** ada di baris 17. Metode ini mencari tiket terakhir hari ini untuk layanan tersebut dengan `lockForUpdate()`, membuang prefix-nya, menambah satu, lalu memformat ulang jadi tiga digit. Kalau belum ada tiket sama sekali, penomoran mulai dari `A001`.

```php
$angkaTerakhir = (int) substr($antrianTerakhir->nomor_antrian, strlen($layanan->kode_prefix));
$formatNomor = $layanan->kode_prefix . str_pad((string) ($angkaTerakhir + 1), 3, '0', STR_PAD_LEFT);
```

`lockForUpdate()` di baris 27 adalah alasan utama kelas ini ada. Tanpa itu, dua request yang datang bersamaan bisa membaca `A041` yang sama dan sama-sama membuat `A042`.

**`panggilBerikutnya(int $loketId)`** ada di baris 59. Metode ini mengambil tiket tertua yang masih berstatus `menunggu` untuk layanan milik loket tersebut, lalu mengubahnya menjadi `dipanggil` sambil mencatat `loket_id` dan `waktu_dipanggil`. Kalau tidak ada yang menunggu, metode ini melempar `AntrianKosongException`.

**`selesaikanAntrian(int $antrianId, int $loketId)`** di baris 95 dan **`lewatiAntrian(...)`** di baris 120. Keduanya mencari tiket dengan syarat `id`, `loket_id`, dan `status = 'dipanggil'`. Syarat `loket_id` itu penting. Tanpa itu, petugas di loket 1 bisa menyelesaikan antrian yang sedang ditangani petugas di loket 2 hanya dengan menebak ID. Kalau tidak ketemu, keduanya melempar `InvalidArgumentException` yang ditangkap controller dan ditampilkan sebagai pesan flash.

Keduanya juga mengisi `waktu_selesai`, termasuk pada aksi `lewati`. Lewat dianggap mengakhiri waktu penanganan, seperti yang dijelaskan di komentar baris 135.

#### `app/Observers/AntrianObserver.php` - pemisahan tanggung jawab

Observer ini didaftarkan di `app/Providers/AppServiceProvider.php:25`. Fungsinya sempit dan jelas. Observer ini hanya menyimak event `updated` pada model `Antrian`, mengecek apakah kolom `status` benar-benar berubah lewat `isDirty('status')`, dan kalau status barunya adalah `dipanggil`, ia mem-broadcast `NomorDipanggil`.

```php
if (!$antrian->isDirty('status')) return;
if ($antrian->status === 'dipanggil') broadcast(new NomorDipanggil($antrian));
```

Efeknya, `AntrianService` tidak perlu tahu apa-apa soal Pusher maupun layar display. Kalau nanti butuh notifikasi lain saat tiket selesai, cukup ditambahkan di sini.

#### `app/Http/Middleware/RoleMiddleware.php`

Cuma satu middleware custom, 24 baris. Alias-nya didaftarkan di `bootstrap/app.php:19` sebagai `role`, lalu dipakai di grup rute lewat `->middleware('role:admin')`.

```php
if (!$request->user() || $request->user()->role !== $role) {
    abort(403, 'Akses ditolak. ...');
}
```

Perlu diketahui bahwa kolom `role` di database bertipe enum yang hanya punya satu nilai, yaitu `admin`. Jadi middleware ini secara praktis cuma memaksa seluruh pengguna untuk selalu bertipe admin. Kalau nanti mau menambah role seperti supervisor atau manajer, ubah dulu definisi enum-nya di `database/migrations/0001_01_01_000000_create_users_table.php:17`.

#### `app/Http/Controllers/AntrianController.php`

Ada tujuh method, masing-masing dipetakan ke satu rute. Semuanya menerima `AntrianService` lewat constructor di baris 15, bukan memakai helper `app()`.

Tiga method hanya menyiapkan data untuk view, yaitu `kios()`, `displayBoard()`, dan `panelPetugas()`. Empat lainnya mengubah state, yaitu `ambil()`, `panggilBerikutnya()`, `selesai()`, dan `lewati()`.

`panelPetugas()` di baris 46 perlu diperhatikan, karena di situ logika antrian menggantung terjadi. Method ini mencari tiket berstatus `dipanggil` milik `loket_id` milik pengguna yang sedang login. Kalau ada, itulah `$antrianAktif` yang dirender sebagai nomor besar di panel, lengkap dengan dua tombol aksi. Kalau tidak ada, yang muncul cuma tombol `PANGGIL BERIKUTNYA`.

#### `app/Exceptions/AntrianKosongException.php`

Ini bukan exception biasa. Dua method-nya di-override. Method `report()` dikosongkan supaya kondisi "antrean kosong" tidak masuk ke log sebagai error sistem, karena itu kondisi normal dan bukan kegagalan. Method `render()` mengembalikan `back()->with('error', ...)`, supaya pesannya langsung muncul sebagai flash message di panel.

Efeknya, controller `panggilBerikutnya()` tidak butuh `try/catch` untuk kasus ini. Cukup `return back()->with('success', ...)`. Yang memakai `try/catch` di controller hanya `InvalidArgumentException` dari dua aksi selesai dan lewati.

### `resources/views/` - antarmuka

Proyek ini tidak punya folder `components/`, tidak punya `partials/`, dan tidak memakai komponen Blade anonymous. Seluruh halaman adalah berkas Blade utuh yang memakai `@extends('layouts.app')`. Total ada enam berkas dan satu layout.

`Layouts/app.blade.php` memegang hampir semua yang bersifat global: elemen `<head>`, pemuatan Tailwind, pemuatan Google Fonts untuk Sora dan JetBrains Mono, navbar sticky, menu hamburger untuk tampilan mobile, serta `@stack('scripts')` di kaki body. Tiap halaman menyuntikkan skrip realtime-nya lewat `@push('scripts')`.

Tema warnanya didefinisikan inline di `layouts/app.blade.php:9-28` sebagai `tailwind.config` milik Tailwind Play CDN. Tokennya adalah `bg`, `panel`, `line`, `ink`, `muted`, `amber`, `cyan`, dan `mint`. Token-token itu hanya hidup di CDN, tidak ada di `resources/css/app.css`, dan itulah alasan kenapa CDN-nya tidak bisa dihapus begitu saja tanpa memindahkan definisi warna lebih dulu.

`Auth/Login.blade.php` cukup simpel: form email dan password, blok error, serta panel kecil berisi kredensial uji. Kredensial itu tercetak langsung di markup baris 44 sampai 51, jadi jangan dibiarkan begitu di produksi.

`antrian/kios.blade.php` adalah grid tombol besar, satu untuk setiap layanan aktif. Tiap tombol adalah sebuah `<form>` dengan `layanan_id` tersembunyi. Tulisan "Tiket tercetak" di baris 16 agak menyesatkan, karena memang tidak ada printer. Nomornya hanya muncul di flash message.

`antrian/display.blade.php` dirender di sisi server lebih dulu supaya tidak kosong saat pertama dibuka, lalu isinya diganti lewat WebSocket. Angkanya sengaja dibuat raksasa, `text-[18rem]` untuk layar besar.

`antrian/panel.blade.php` bertema gelap, kontras dengan halaman lain yang terang. Panel ini punya dua kondisi, yaitu belum ada yang dipanggil dan sedang ada yang dipanggil, dibedakan dengan `@if (!$antrianAktif)`. Tiap kondisi punya form POST-nya sendiri.

> Catatan penting soal kapitalisasi. Folder di disk bernama `Layouts/` dan `Auth/` dengan huruf besar, tapi controller memanggil `view('layouts.app')` dan `view('auth.login')` dengan huruf kecil. Di Windows dan macOS ini tidak masalah karena filesystem-nya case-insensitive. Di Linux yang case-sensitive, setiap halaman akan gagal dengan pesan `View [layouts.app] not found`. Kalau proyek ini mau dideploy ke server Linux, rename kedua folder itu ke huruf kecil lebih dulu, lalu bersihkan cache dengan `php artisan view:clear` karena Blade meng-cache nama view sebagai string.

### `resources/js/`

Ada tiga berkas, dan peran mereka jelas.

`app.js` adalah entry point yang dirujuk oleh `@vite` di layout. Isinya hanya meng-import `./bootstrap` dan `./echo`.

`bootstrap.js` berisi konfigurasi axios, yaitu `window.axios` plus header `X-Requested-With`. Ini boilerplate bawaan Laravel, tapi aplikasi ini tidak pernah melakukan request HTTP dari JavaScript.

`echo.js` mendefinisikan `window.Echo` dan `window.Pusher` secara global, dengan broadcaster yang di-hardcode menjadi `pusher`.

Keduanya diekspos ke `window` karena berkas Blade membacanya langsung sebagai `window.Echo.channel(...)` di dalam `<script type="module">`, bukan lewat bundling.

### `database/`

Tujuh migrasi. Tiga di antaranya adalah tabel domain aplikasi, empat lagi bawaan Laravel untuk users, cache, jobs, dan sessions.

Nama berkas migrasi untuk tiga tabel domain sengaja dibuat berawalan `0000_...` dan `0001_00_00_...`, sementara tabel bawaan Laravel berawalan `0001_01_01_...`. Ini bukan kebetulan. Tabel `lokets` punya foreign key ke `layanans`, dan `users` punya foreign key ke `lokets`, jadi tabel domain harus dibuat lebih dulu.

Berkas `database/database.sqlite` masih ada di repository dan isinya hanya tabel-tabel bawaan Laravel. Tidak ada `layanans`, `lokets`, maupun `antrians` di dalamnya. Itu sisa dari `php artisan migrate` pertama sebelum migrasi custom ditulis. Tidak terpakai, karena `.env` menunjuk ke MySQL.

### `routes/`

Hanya `web.php` yang berisi rute aplikasi. Tidak ada `routes/api.php` sama sekali. `bootstrap/app.php:11-16` memanggil `withRouting()` tanpa parameter `api:`, jadi tidak ada endpoint API yang terdaftar. Ini aplikasi web murni.

`routes/channels.php` punya satu callback otorisasi untuk channel privat `App.Models.User.{id}`, tapi tidak ada event yang memakainya. Kedua event di proyek ini memakai channel publik, sehingga endpoint `/broadcasting/auth` tidak pernah dipanggil.

### `config/`

Semua dua belas berkas masih bawaan Laravel, belum ada yang disesuaikan. `config/app.php` masih memakai `timezone => 'UTC'` secara hardcoded. Kalau ingin waktu lokal Indonesia, ubah ke `Asia/Jakarta`.

Ada satu hal yang perlu diketahui di `config/filesystems.php`. Disk `local` punya `serve` bernilai true, dan itu yang mendaftarkan dua rute otomatis `GET /storage/{path}` dan `PUT /storage/{path}`. Rute itu milik framework, bukan aplikasi ini.

### `public/` dan `.htaccess` di root

Ada dua berkas `.htaccess`, dan keduanya punya alasan berbeda. Yang di `public/` adalah bawaan Laravel untuk server Apache. Isinya menyembunyikan daftar direktori, meneruskan header Authorization dan X-XSRF-Token, lalu meneruskan semua request yang tidak cocok dengan berkas fisik ke `index.php`.

Yang ada di root repository cuma empat baris dan hanya menulis ulang semua request ke `public/`. Ini untuk shared hosting yang document root-nya tidak bisa diarahkan ke folder `public/`. Kalau pakai Nginx atau VPS dengan document root yang sudah benar, berkas ini tidak perlu dan sebaiknya dihapus.

### `tests/`

Hanya dua berkas contoh bawaan Laravel yang belum dihapus. `tests/Unit/ExampleTest.php` memanggil `assertTrue(true)`, dan `tests/Feature/ExampleTest.php` melakukan `$this->get('/')` lalu mengecek status 200. Belum ada satu pun test yang menyentuh logika domain. `AntrianService`, transisi status, `RoleMiddleware`, dan observer belum diuji sama sekali.

---

## Alur kerja lengkap

### 1. Pelanggan mengambil nomor

```
Pelanggan          Kios (/)        AntrianService        Database
     |                 |                   |                  |
     |-- pilih layanan>|                   |                  |
     |<-- grid tombol --|                  |                  |
     |                 | Layanan::where(status,true)           |
     |                 |                   |                  |
     |-- POST /ambil-antrian -->|         |                  |
     |                 |-- ambilNomor() -->|                  |
     |                 |                   | transaction {   |
     |                 |                   |  lockForUpdate()->| baris terakhir
     |                 |                   |<-- A041 (locked) -| hari ini
     |                 |                   | hitung A042      |
     |                 |                   | INSERT --------->|
     |                 |                   |  status=menunggu |
     |                 |                   | event(AntrianBaru)
     |                 |<-- nomor A042 ----|                  |
     |<-- "Nomor Anda: A042" --------------|                  |
```

Sambil itu, event `AntrianBaru` dikirim ke channel `sisa-antrian.{layanan_id}`. Setiap panel petugas yang layanannya sama menerima sinyal itu dan menaikkan angka sisa antrean tanpa reload halaman.

### 2. Petugas memanggil nomor

```
Petugas        AntrianController   AntrianService   Observer     Display
     |               |                    |            |            |
     |-- POST /panel/panggil -->|        |            |            |
     |               | cek loket_id user  |            |            |
     |               |-- panggilBerikutnya(loketId) ->|            |
     |               |                    | lockForUpdate()      |
     |               |                    | UPDATE status       |
     |               |                    |  = dipanggil ------>|
     |               |                    |    updated()        |
     |               |                    |    isDirty(status)  |
     |               |                    |    broadcast() ---->|
     |               |                    |            |-- nomor.dipanggil ->|
     |               |<-- Antrian --------|            |   A042 / Loket 1   |
     |<-- flash success -------------------|            |            | layar berubah
```

Perintah `UPDATE status = dipanggil` di `AntrianService.php:82` adalah titik yang menyalakan observer. Service tidak pernah memanggil broadcast secara langsung untuk kasus ini.

Kalau antrean kosong, `AntrianKosongException` dilempar, `render()`-nya menangkapnya, dan petugas melihat pesan "Tidak ada antrian yang menunggu saat ini untuk layanan ini." tanpa halaman error.

### 3. Memproses dan menutup antrian

Dari panel, petugas melihat nomor besar dan dua tombol. `SELESAI` mengirim `POST /panel/antrian/{id}/selesai`, sedangkan `LEWATI` mengirim `POST /panel/antrian/{id}/lewati`. Keduanya memanggil service dengan pasangan `(id, loket_id milik petugas)`, jadi petugas tidak bisa menutup antrian milik loket lain. Setelah berhasil, panel kembali ke kondisi siap memanggil berikutnya karena `$antrianAktif` sudah tidak ada.

---

## Basis data

Lima tabel kalau hanya menghitung yang dipakai aplikasi, atau sepuluh kalau ikut memhitungkan tabel bawaan Laravel.

### `layanans` - jenis layanan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, primary key | |
| `nama_layanan` | string | Wajib. Tampil di kios dan papan display |
| `kode_prefix` | string(5) | Awalan nomor, misalnya `A` |
| `status` | boolean, default `true` | `true` berarti tampil di kios |
| `created_at`, `updated_at` | timestamp | |

### `lokets` - meja layanan

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, primary key | |
| `layanan_id` | foreign key ke `layanans.id` | `cascadeOnDelete` |
| `nama_loket` | string | Tampil di papan display |
| `status` | boolean, default `true` | |
| `created_at`, `updated_at` | timestamp | |

### `antrians` - tiket

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, primary key | |
| `layanan_id` | foreign key ke `layanans.id` | `cascadeOnDelete` |
| `loket_id` | foreign key ke `lokets.id`, nullable | `nullOnDelete`. NULL selama masih menunggu |
| `nomor_antrian` | string | Contohnya `A001` atau `B012` |
| `status` | enum | `menunggu`, `dipanggil`, `selesai`, `dilewati` |
| `waktu_ambil` | timestamp, `useCurrent()` | Diisi otomatis oleh database |
| `waktu_dipanggil` | timestamp, nullable | Diisi saat status jadi `dipanggil` |
| `waktu_selesai` | timestamp, nullable | Diisi saat `selesai` dan juga saat `dilewati` |
| `tanggal` | date | Dasar penomoran harian |
| `created_at`, `updated_at` | timestamp | |

Kolom `status` berupa enum di level database, bukan enum PHP. Empat nilai itu dibaca sebagai string mentah di seluruh kode.

### `users` - petugas

Tabel bawaan Laravel yang dimodifikasi, dengan dua kolom tambahan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `loket_id` | foreign key ke `lokets.id`, nullable | `nullOnDelete`. Mengikat petugas ke loket |
| `role` | enum dengan satu nilai `admin`, default `admin` | Saat ini belum ada role lain |

Kolom `name`, `email`, `password`, `email_verified_at`, dan `remember_token` tetap seperti bawaan. Tidak ada tabel `password_reset_tokens`, jadi fitur lupa password tidak tersedia.

### Tabel bawaan Laravel

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, dan `sessions`. Semuanya ikut termigrasi karena session, cache, dan queue diarahkan ke database di `.env`. Tapi tidak ada satu pun job yang di-dispatch di proyek ini. Tabel `jobs` hanya terbawa dari skeleton.

---

## Model dan relasi

Empat model, semuanya kecil dan lugas dibaca.

```
Layanan (1) ----< Loket (1) ----< User
    |                            (hasOne, lewat loket_id)
    |
    +-----------< Antrian >------+
                (layanan_id dan loket_id)
```

**`User`** punya `$fillable` berisi `name`, `email`, `password`, `loket_id`, dan `role`. Relasinya `loket(): BelongsTo`. Cast `password` menjadi `hashed`, jadi jangan pernah memanggil `Hash::make` manual kalau mengisi lewat mass assignment.

**`Layanan`** punya relasi `lokets(): HasMany` dan `antrians(): HasMany`. Kolom `status` di-cast menjadi boolean.

**`Loket`** punya relasi `layanan(): BelongsTo`, `antrians(): HasMany`, dan `user(): HasOne` ke `User`. Relasi `user()` jarang dipakai di kode. Panel lebih sering membaca `$user->loket` dari sisi sebaliknya.

**`Antrian`** punya relasi `layanan(): BelongsTo` dan `loket(): BelongsTo`. Tiga kolom timestamp di-cast menjadi `datetime`, dan `tanggal` menjadi `date`. Observer-nya didaftarkan di `AppServiceProvider`, bukan di konstruktor model.

---

## Daftar rute

Tujuh rute aplikasi, tanpa API dan tanpa rute kustom lain.

| Method | URI | Nama | Handler | Middleware |
|---|---|---|---|---|
| GET | `/` | `kios.index` | `AntrianController@kios` | - |
| POST | `/ambil-antrian` | `kios.ambil` | `AntrianController@ambil` | - |
| GET | `/display-board` | `display.index` | `AntrianController@displayBoard` | - |
| GET | `/login` | `login` | `AuthController@showLoginForm` | - |
| POST | `/login` | `login.post` | `AuthController@login` | - |
| POST | `/logout` | `logout` | `AuthController@logout` | - |
| GET | `/panel` | `panel.index` | `AntrianController@panelPetugas` | `auth`, `role:admin` |
| POST | `/panel/panggil` | `panel.panggil` | `AntrianController@panggilBerikutnya` | `auth`, `role:admin` |
| POST | `/panel/antrian/{id}/selesai` | `panel.selesai` | `AntrianController@selesai` | `auth`, `role:admin` |
| POST | `/panel/antrian/{id}/lewati` | `panel.lewati` | `AntrianController@lewati` | `auth`, `role:admin` |

Itu sepuluh baris tabel untuk tujuh rute aplikasi, karena `kios`, `display`, dan `panel` masing-masing punya endpoint GET dan POST.

Beberapa rute berikut terdaftar otomatis oleh framework, bukan oleh aplikasi ini. `GET /up` untuk health check, `POST /broadcasting/auth` untuk otorisasi channel privat, dan `GET /storage/{path}` untuk menyajikan berkas dari disk lokal.

Semua aksi yang mengubah state memakai POST dengan token `@csrf`. Tidak ada aksi yang mempercayai query string, termasuk `panggil`, `selesai`, dan `lewati` yang secara intuitif mungkin terasa seperti tombol biasa. Tetap POST supaya tidak bisa dipicu dari prefetch atau dari gambar yang disisipkan diam-diam di halaman lain.

---

## Realtime

Ada dua channel publik dan dua event. Arahnya satu, dari server ke browser.

### Channel `sisa-antrian.{layanan_id}` dengan event `.antrian.baru`

Dikirim dari `AntrianService.php:50` setiap kali tiket baru dibuat. Event-nya adalah `AntrianBaru`, dan isinya cuma `['layanan_id' => ...]`.

Disubscribe di `panel.blade.php:98`. Di dalam listener, angka sisa antrean dinaikkan satu dan berkedip warna cyan selama satu detik supaya petugas tahu ada pelanggan baru. ID layanan diambil dari loket petugas yang login, jadi setiap petugas hanya menerima update untuk layanannya sendiri.

### Channel `display-antrian` dengan event `.nomor.dipanggil`

Dikirim dari `AntrianObserver.php:25` setiap kali ada antrian yang berubah status menjadi `dipanggil`. Isinya tiga field, didefinisikan di `app/Events/NomorDipanggil.php:51`.

```php
[
    'nomor_antrian' => 'A042',
    'loket'         => 'Loket 1 (CS)',
    'layanan'       => 'Customer Service',
]
```

Disubscribe di `display.blade.php:40`. Ketiga elemen DOM diperbarui di tempat, tanpa reload dan tanpa request ke server. Ada satu reflow paksa lewat `void nomorEl.offsetWidth` supaya animasi flicker-nya ter-trigger ulang.

### Kenapa channel privat tidak dipakai

Keduanya memakai `Channel` yang bersifat publik, bukan `PrivateChannel`. Artinya siapa pun yang tahu nama channel-nya bisa berlangganan dan melihat nomor antrian beserta nama loket. Untuk sistem yang benar-benar dipakai publik, keputusan ini perlu ditinjau ulang. Channel privat hanya require mengubah `broadcastOn()` di kedua event, karena `routes/channels.php` dan endpoint `/broadcasting/auth` sudah tersedia.

---

## Menjalankan pengujian

```bash
php artisan test
```

Atau lewat PHPUnit langsung:

```bash
./vendor/bin/phpunit
```

Tapi harus jujur soal apa yang ada. Yang ada baru dua berkas contoh bawaan Laravel. `tests/Unit/ExampleTest.php` memanggil `assertTrue(true)`, dan `tests/Feature/ExampleTest.php` melakukan `$this->get('/')` lalu mengecek status 200. `RefreshDatabase` di berkas itu masih dikomentari, jadi pengujiannya memakai database yang sedang aktif di `.env`, yaitu database pengembangan Anda, bukan database sementara.

Kalau mau mulai menulis test yang serius, `AntrianService` adalah tempat paling berharga untuk ditutupi. Empat transisi statusnya mudah diprediksi, dan yang paling penting untuk diuji justru perilaku balapan. Dua permintaan `ambilNomor()` yang berurutan untuk layanan yang sama harus menghasilkan dua nomor berbeda dan berurutan.

Sebagian besar nilai di `phpunit.xml` sudah disiapkan untuk pengujian, misalnya `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, dan `BCRYPT_ROUNDS=4`. Tapi override SQLite in-memory di baris 25 dan 26 masih dikomentari. Kalau ingin test yang benar-benar terisolasi, aktifkan dua baris itu.

---

## Deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Daftar periksa sebelum aplikasi dionlinekan.

**Rename `resources/views/Layouts/` dan `Auth/` ke huruf kecil.** Ini yang paling penting, karena di server Linux huruf besar-kecil pada path view itu sensitive dan seluruh aplikasi akan gagal.

**Isi semua variabel Pusher.** `BROADCAST_CONNECTION=pusher` beserta `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`, `VITE_PUSHER_APP_KEY`, dan `VITE_PUSHER_APP_CLUSTER`. Tanpa itu, papan display diam.

**Setel `APP_DEBUG=false` dan `APP_ENV=production`.** `.env.example` bawaan mengaktifkan keduanya, dan `APP_DEBUG=true` akan membocorkan stack trace beserta kredensial database ke siapa pun yang menyebabkan halaman error.

**Setel `APP_URL`** ke domain sebenarnya.

**Hapus blok kredensial uji** di `resources/views/Auth/Login.blade.php:44-51`. Email, password, dan loket tujuan tercetak di halaman login.

**Ubah timezone** di `config/app.php:68` dari `UTC` ke `Asia/Jakarta` kalau waktu yang ditampilkan harus sesuai zona waktu lokal. Perhatikan juga kolom `tanggal` yang dipakai untuk penomoran harian, karena itu dihitung dari `now()`.

**Keluarkan `role` dari daftar `$fillable`** di `app/Models/User.php:31`. Saat ini tidak ada endpoint yang memicu masalah ini, tapi begitu ada, kolom `role` jadi bisa diisi bebas dari request.

**Tambahkan rate limiting** di `POST /login`. `AuthController@login` tidak memakai middleware `throttle`, jadi percobaan password bisa diulang tanpa batas.

**Hapus `laravel.zip` dari root repository.** Ukurannya sekitar 46 MB, kemungkinan besar memuat `.env` beserta kredensial, dan tidak ada alasan untuk menyimpannya di version control.

Kalau aplikasi ini dipasang di shared hosting, `.htaccess` di root sudah mengarahkannya ke `public/`. Pastikan `APP_ENV=production` benar-benar aktif sebelum aplikasi dionlinekan.

---

## Yang perlu diperbaiki

Beberapa hal yang belum rapi, dicatat apa adanya supaya tidak kaget saat dipakai di produksi. Ini bukan daftar panjang, dan bukan hal-hal yang bisa dibiarkan begitu saja.

**Kapitalisasi folder view.** Sudah dibahas di atas. Ini yang paling fatal karena gejalanya jelas dan besar. Seluruh halaman gagal di Linux.

**`.env.example` tidak punya variabel Pusher.** Hasil clone baru tidak akan punya papan display yang jalan. Variabel Pusher sebaiknya masuk ke `.env.example`, bukan diisi manual oleh setiap orang.

**Kredensial demo tercetak di halaman login.** Ada di `resources/views/Auth/Login.blade.php:44-51`. Baiknya tidak dirender kalau `APP_ENV` bernilai `production`.

**Kelas Tailwind yang tidak terdefinisi.** `panel.blade.php` dan `display.blade.php` memakai sekitar dua puluh kelas yang tidak ada di mana pun, yaitu `led-cyan`, `led-white`, `led-amber`, `led-mint`, `board-panel`, `board-bg`, `glow-amber`, dan `animate-led-flicker`. Semuanya berada di luar `tailwind.config` inline milik layout dan di luar blok `@theme` di `resources/css/app.css`, jadi dirender tanpa style. Akibatnya panel petugas terlihat lebih polos dari yang sebenarnya dimaksud. Tombolnya tidak pernah glow dan teksnya tidak punya warna LED.

**Bug di kios.** `kios.blade.php:31` menulis `$layanan->kode`, padahal nama kolomnya adalah `kode_prefix` dan tidak ada atribut `kode`. Karena operator `??` menelan nilai `null`, huruf besar di tiap kartu selalu jatuh ke fallback, yaitu huruf pertama nama layanan. Untuk Customer Service hasilnya kebetulan `C`, bukan `A`.

**Tailwind dimuat dua kali.** Play CDN di dalam `<head>` dan Tailwind v4 hasil build Vite. Keduanya jalan, dan masing-masing menyapu class dari berkas yang sama. CSS hasil build sekarang 62 KB, sebagian besar isinya terduplikasi. Memindahkan token warna ke blok `@theme` di `resources/css/app.css` akan memungkinkan CDN dihapus.

**Font tidak sinkron.** `resources/css/app.css:10` mendeklarasikan `Instrument Sans` sebagai `--font-sans`, sementara layout memuat `Sora` dan `JetBrains Mono` dari Google Fonts. Instrument Sans tidak pernah dimuat, jadi tidak akan pernah dipakai.

**`echo.js` di-import dua kali.** Sekali di `resources/js/app.js:2` dan sekali lagi di `bootstrap.js:12`. Tidak merusak apa pun berkat cache modul ES, tapi tetap redundan.

**Berkas sqlite yang menyesatkan.** `database/database.sqlite` masih ada dan hanya berisi tabel-tabel Laravel bawaan. Kalau ada yang menjalankan `php artisan migrate` dengan `DB_CONNECTION=sqlite`, orang itu akan mendapat database yang terlihat berhasil tapi tidak punya tabel aplikasi sama sekali.

**Seeder dan factory yang belum lengkap.** `DatabaseSeeder` tidak idempotent. `UserFactory` tidak mengisi `loket_id` dan `role`, jadi user hasil factory tidak bisa masuk ke panel karena `panelPetugas()` akan gagal saat `$user->loket` bernilai null.

**Endpoint ambil tidak mengecek status layanan.** `AntrianController@ambil()` hanya memvalidasi `exists:layanans,id`, tidak sampai mengecek `status = true`. Kios memang menyembunyikan layanan nonaktif, tapi request yang dibuat langsung ke endpoint tetap diterima.

**Broadcast dipanggil di dalam transaksi.** `event()` di `AntrianService.php:50` dipanggil sebelum `DB::transaction` selesai commit. Dengan driver broadcast yang berbasis antrean, event bisa sampai ke browser sebelum datanya benar-benar committed. Kalau nanti berpindah ke `ShouldBroadcastNow` atau ke antrean sungguhan, pindahkan `event()` keluar dari closure transaksi.

**Nol coverage test.** Semua bagian yang sudah dibahas di atas belum diuji. Kalau cuma boleh menambah satu test, tambahkan untuk `AntrianService`, khususnya untuk membuktikan penguncian baris bekerja.

---

## Lisensi

MIT, mengikuti `composer.json`.

Dibangun dengan Laravel 12.
