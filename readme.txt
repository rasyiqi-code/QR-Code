=== QR Code Generator & Secure Validator ===
Contributors: rasyiqi
Tags: qr code, validator, document verification, digital signature, secure validation
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Sistem verifikasi & penerbitan dokumen digital mandiri berbasis QR Code offline, tanda tangan basah Media Library, dan segel kriptografi SHA-256 terenkripsi.

== Description ==

QR Code Generator & Secure Validator adalah plugin WordPress premium yang dirancang untuk menerbitkan dokumen terverifikasi digital secara mandiri. Plugin ini memungkinkan administrator membuat data validasi dokumen, menyematkan berkas tanda tangan basah/dokumen resmi langsung dari Media Library WordPress, menghasilkan QR Code secara offline tanpa dependensi API eksternal, dan menyajikan halaman pembuktian keaslian dokumen (validity landing page) yang dilengkapi segel integritas kriptografi SHA-256 secara real-time.

= Fitur Utama =
* **Offline QR Code Generator**: Membuat kode QR secara instan dan aman secara lokal menggunakan pustaka PHP bawaan, tanpa mengirim data ke pihak ketiga.
* **Format Unduhan PNG Berkualitas Tinggi**: Diintegrasikan langsung dengan PHP GD Library untuk menghasilkan unduhan QR Code berekstensi .png beresolusi tinggi, siap cetak.
* **Sematkan Dokumen & Tanda Tangan Basah**: Integrasi langsung dengan WordPress Media Library untuk mengunggah berkas TTD basah (transparan PNG) atau berkas dokumen (PDF).
* **Visualisasi Preview Berkas Pintar**: Tampilan TTD basah transparan (mix-blend-mode: multiply) atau ikon format dokumen dinamis (PDF, Word, Excel, ZIP).
* **Segel Kriptografi SHA-256**: Mengikat token validasi dan data dokumen secara real-time untuk menjamin integritas data terhadap manipulasi database tidak sah.
* **Desain Landing Page Premium**: Estetika mewah dengan aksen warna Merah Marun, Emas, dan Hitam Logam, serta efek kartu melayang (glassmorphism) yang responsif.
* **Aksi Cetak Instan**: Menyediakan tombol cetak bukti validasi untuk pencetakan fisik lembar verifikasi yang rapi.

== Installation ==

1. Unduh atau salin folder plugin `qr-code-validator` ke direktori plugin WordPress Anda di `/wp-content/plugins/`.
2. Masuk ke Dashboard Admin WordPress -> **Plugins** -> **Installed Plugins**.
3. Temukan **QR Code Generator & Secure Validator**, lalu klik **Activate**.
4. Plugin akan secara otomatis membuat tabel database baru `wp_qr_codes` untuk menampung data verifikasi secara aman.

== Screenshots ==

1. Daftar dokumen dan aksi pengunduhan file PNG QR Code di dasbor admin.
2. Form editor admin dengan tombol sematkan berkas dari Media Library.
3. Landing page pembuktian keaslian dokumen dengan kunci kriptografi SHA-256 dinamis.

== Changelog ==

= 1.0.0 =
* Rilis versi stabil pertama.
* Integrasi pustaka generator QR Code offline.
* Pembuatan unduhan format PNG tajam berkualitas tinggi.
* Integrasi uploader media library di panel admin.
* Implementasi segel verifikasi kriptografi SHA-256.
* Desain ulang landing page dengan skema warna premium (Marun, Emas, Hitam).
