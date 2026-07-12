# QR Code Generator & Secure Validator

**QR Code Generator & Secure Validator** adalah plugin WordPress premium yang dirancang untuk menerbitkan dokumen terverifikasi digital secara mandiri. Plugin ini memungkinkan administrator membuat data validasi dokumen, menyematkan berkas tanda tangan basah/dokumen resmi langsung dari Media Library WordPress, menghasilkan QR Code secara offline tanpa dependensi API eksternal, dan menyajikan halaman pembuktian keaslian dokumen (*validity landing page*) yang dilengkapi segel integritas kriptografi **SHA-256**.

---

## 🌟 Fitur Utama

- **Offline QR Code Generator**: Membuat kode QR secara instan dan aman secara lokal menggunakan pustaka PHP bawaan, tanpa mengirim data ke pihak ketiga (sangat mematuhi regulasi privasi data/GDPR).
- **Format Unduhan PNG Berkualitas Tinggi**: Generator diintegrasikan langsung dengan PHP GD Library untuk menghasilkan unduhan QR Code biner berekstensi `.png` beresolusi tinggi, siap cetak.
- **Sematkan Dokumen & Tanda Tangan Basah**: Integrasi langsung dengan **WordPress Media Library** sehingga administrator dapat mengunggah dan memilih berkas TTD basah (transparan PNG) atau berkas dokumen terkait (PDF/Word/Excel) hanya dengan satu klik.
- **Visualisasi Preview Berkas Pintar**:
  - Tanda tangan basah yang diunggah akan ditampilkan secara penuh (*full-width*) di atas kertas preview digital dengan efek transparansi otomatis (`mix-blend-mode: multiply`).
  - Berkas non-gambar (PDF, Word, Excel, ZIP) akan menampilkan ikon dokumen yang sesuai secara dinamis dengan warna resmi masing-masing format.
- **Segel Kriptografi SHA-256**: Mengikat token validasi, judul dokumen, dan waktu rilis menggunakan hashing kriptografi satu-arah **SHA-256** secara real-time untuk menjamin integritas data terhadap manipulasi database tidak sah.
- **Desain Landing Page Premium**: Halaman pembuktian validitas berestetika mewah dengan aksen warna Merah Marun, Emas, dan Hitam Logam, serta efek kartu melayang (*glassmorphism*) yang responsif.
- **Aksi Cetak Instan**: Menyediakan tombol cetak bukti validitas (*print-friendly*) untuk mencetak fisik lembar bukti verifikasi dengan tata letak rapi.

---

## 🔒 Arsitektur Keamanan & Standar Dev

Plugin ini dirancang dengan mematuhi pedoman keamanan penulisan kode WordPress standar industri (*WordPress VIP Coding Standards*):
1. **Proteksi Akses Langsung**: Seluruh berkas PHP dilindungi dengan pengecekan `ABSPATH` untuk mencegah eksekusi skrip langsung dari luar WordPress.
2. **Cek Kapabilitas Pengguna**: Membatasi semua fungsi administrasi (buat, edit, hapus, unduh) ketat pada akun dengan kemampuan administrator (`manage_options`).
3. **Pertahanan CSRF (Cross-Site Request Forgery)**: Melindungi setiap pengiriman formulir dan tombol aksi sensitif menggunakan Token Keamanan Nonce (`wp_create_nonce` dan `check_admin_referer`).
4. **Pencegahan SQL Injection**: Seluruh transaksi database kustom (CRUD) diimplementasikan menggunakan perintah aman `$wpdb->prepare()`, `$wpdb->insert()`, dan `$wpdb->update()`.
5. **Sanitasi & Escaping Output**:
   - Data input dibersihkan secara berlapis lewat `sanitize_text_field` dan `esc_url_raw()`.
   - Data keluaran HTML di-escape secara ketat dengan `esc_html()`, `esc_attr()`, `esc_url()`, dan `wp_kses_post()`.

---

## ⚙️ Instalasi

1. Unduh atau salin folder plugin `qr-code-validator` ke direktori plugin WordPress Anda di `/wp-content/plugins/`.
2. Masuk ke Dashboard Admin WordPress -> **Plugins** -> **Installed Plugins**.
3. Temukan **QR Code Generator & Validator**, lalu klik **Activate**.
4. Plugin akan secara otomatis membuat tabel database baru `wp_qr_codes` untuk menampung data verifikasi secara aman.

---

## 🚀 Panduan Penggunaan

1. **Membuat QR Dokumen Baru**:
   - Buka menu **QR Validator** berlambang tameng keamanan di sidebar admin.
   - Masuk ke tab **Tambah Dokumen Baru**.
   - Isi judul dokumen, deskripsi detail, serta pilih status keaslian (`VALID`, `EXPIRED`, atau `REVOKED`).
   - Tekan **Pilih Berkas Media** untuk mengunggah berkas tanda tangan basah (berupa gambar PNG transparan) atau file dokumen/PDF terkait.
   - Klik **Simpan & Hasilkan QR Code**.
2. **Mengelola Dokumen**:
   - Di tab **Daftar Dokumen QR**, Anda dapat menyalin tautan verifikasi, mengunduh gambar barcode QR berformat **PNG**, menyunting data, atau menghapus entri.
3. **Validasi Dokumen**:
   - Tempel atau scan QR Code untuk mengarahkan pengguna ke halaman pembuktian validitas resmi: `http://domain-anda.com/?qr_verify={TOKEN_UUID}`.

---

## 📁 Struktur Berkas Plugin

```text
qr-code-validator/
│
├── qr-code-validator.php               # File entri utama inisialisasi plugin
│
├── includes/
│   ├── class-qr-code-loader.php        # Loader utama & pengatur asset enqueue
│   ├── class-qr-code-db.php            # Manajemen CRUD database kustom
│   ├── class-qr-code-admin.php         # Handler dashboard admin & form editor
│   ├── class-qr-code-generator.php     # Engine generator QR Code (SVG & PNG GD)
│   └── class-qr-code-validator-handler.php # Handler render landing page validitas
│
├── assets/
│   ├── css/
│   │   ├── admin.css                   # Gaya visual dashboard admin
│   │   └── validator.css               # Gaya visual landing page verifikasi
│   └── js/
│       └── admin.js                    # Script interaksi admin (uploader media, copier)
│
└── lib/
    └── QRCode.php                      # Pustaka phpQRCode splitbrain (offline renderer)
```

---

## 📝 Lisensi

Plugin ini dirilis di bawah lisensi MIT. Hak cipta dilindungi oleh Rasyiqi | [Crediblemark.com](https://crediblemark.com).
