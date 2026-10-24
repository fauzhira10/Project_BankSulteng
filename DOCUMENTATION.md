# Dokumentasi Projek: Sistem Jurnal Keluhan Nasabah (Bank Sulteng)

## 📌 1. Deskripsi Projek
Projek **Jurnal_Banksulteng** adalah aplikasi web berbasis **Laravel 12/13** yang dirancang khusus untuk memfasilitasi pencatatan, validasi, monitoring, dan pengelolaan **Jurnal Keluhan Transaksi Nasabah** di lingkungan Bank Sulteng.

### Tujuan & Manfaat Utama:
- **Autentikasi Username Petugas**: Halaman login admin berbasis **Username** murni (`username: admin`) tanpa perlu registrasi mandiri.
- **Tabel Data Keluhan Ultra Bersih (Single Action "Detail")**:
  - Kolom **Aksi** di tabel utama hanya memiliki 1 tombol: **Detail**, membuat tata letak tabel sangat bersih, rapi, dan memberikan ruang maksimum bagi kolom data nasabah, cabang, transaksi, dan nominal.
- **Pusat Aksi Terintegrasi di Modal Rincian**:
  - Tombol **Edit Data Jurnal** (kuning/amber) dan tombol **Hapus Data** (merah) ditempatkan berdampingan secara proporsional di bagian bawah (*footer*) pop-up modal rincian.
  - Mengklik tombol Hapus Data pada modal rincian akan membuka dialog konfirmasi hapus data permanen dengan double-confirmation.
- **Modal Konfirmasi Hapus Data Ekstra Aman**: Dialog konfirmasi interaktif dengan rincian data nasabah dan peringatan permanen sebelum eksekusi penghapusan data.
- **Standar Seluruh Input Wajib Diisi**: Menjamin kelengkapan data perbankan dengan mewajibkan seluruh 16 field pengaduan jurnal keluhan nasabah.
- **Pencarian Bebas Huruf Besar/Kecil (*Case-Insensitive*)**: Pencarian otomatis mendeteksi kata kunci baik ditulis huruf besar, kecil, maupun campuran (`budi`, `BUDI`, `Budi`) tanpa peduli kapitalisasi huruf.
- **Penanda Teks Latar Kuning (*Yellow Highlight*)**: Kata yang cocok otomatis disorot dengan latar belakang kuning stabilo dengan tetap mempertahankan huruf besar/kecil asli data nasabah.
- **Master Data 41 Kantor Cabang**: Mendukung seluruh jaringan kantor cabang, KCP, dan Bank Lain di seluruh wilayah Sulawesi Tengah & Jakarta.
- **Pencatatan Terpusat**: Menggantikan pencatatan manual keluhan nasabah ke dalam sistem web yang terstruktur.
- **Sidebar Navigasi Modern**: Memudahkan transisi antar menu "Input Jurnal Keluhan" dan "Data Keluhan", lengkap dengan info username aktif dan tombol logout.
- **Deteksi Keluhan Berulang (Anti-Duplikat Bertingkat)**: Mencegah klaim ganda atas transaksi keluhan nasabah yang sama. Kombinasi **Nama Nasabah + No. Resi + Tanggal Transaksi** yang sama persis ditolak, sedangkan **Nama Nasabah + No. Resi** yang sama dengan tanggal berbeda hanya diperingatkan dan dapat dilanjutkan setelah petugas menyetujui. Peringatannya muncul sejak petugas mengetik, lengkap dengan rincian keluhan lama.
- **Otomatisasi Channel & Biaya Admin**: Mempercepat pengisian form dengan mekanisme *auto-fill* berbasis AJAX saat jenis transaksi dipilih.
- **Keamanan & Kepatuhan**: Rekam jejak audit (*audit trail*) berantai hash yang mencatat pembuatan, perubahan, dan penghapusan jurnal, keputusan atas pengaduan, pembukaan berkas KTP, serta percobaan login yang gagal. Keutuhannya dapat diperiksa dengan `php artisan audit:periksa`. Lihat bagian 8.4.
- **Blockchain Jejak Audit**: Catatan audit ditambang menjadi blok berantai (merkle root + proof of work), lalu hash tiap blok dijangkarkan ke smart contract di Ethereum Sepolia, sehingga pemalsuan jejak audit, bahkan oleh pemegang akses basis data, tetap ketahuan. Lihat bagian 8.11.

---

## 📊 2. Status & Progres Pengerjaan Projek

### Ringkasan Pencapaian (Milestone Progress)

```text
[████████████████████] 100% - Fase 1: Basis Data & Master Data (41 Cabang & 33 Transaksi)
[████████████████████] 100% - Fase 2: Formulir Frontend (Seluruh Input Wajib Diisi) & Auto-Fill AJAX
[████████████████████] 100% - Fase 3: Backend Controller & Validasi Hard Anti-Duplikat
[████████████████████] 100% - Fase 4: Seeding 33 Jenis Transaksi & 10 Channel Resmi
[████████████████████] 100% - Fase 5: Modul Autentikasi Admin (Login Berbasis Username & Logout)
[████████████████████] 100% - Fase 6: Layout Sidebar & Modul Data Keluhan (Aksi Terpusat di Modal Detail, Live Search)
```

### Tabel Status Pengerjaan Modul:

| No | Modul / Fitur | Target Pekerjaan | Status | Progres |
|:---|:---|:---|:---:|:---:|
| 1 | **Skema Database & Migrasi** | Tabel `users` (+ kolom `username`), `master_cabangs`, `master_transaksis`, `jurnals`, `audit_trails` | **Selesai** | **100%** |
| 2 | **Master Data Transaksi** | 33 jenis transaksi dan 10 channel resmi diinput ke database | **Selesai** | **100%** |
| 3 | **Master Data 41 Cabang** | Input lengkap 41 kantor cabang, KCP, dan Bank Lain resmi Bank Sulteng | **Selesai** | **100%** |
| 4 | **Autentikasi Username Admin** | Halaman login admin bersih berbasis username, akun default siap pakai, proteksi auth middleware | **Selesai** | **100%** |
| 5 | **Sidebar Navigasi Bank Sulteng** | Navigasi responsif (Input Jurnal & Data Keluhan), jam real-time WITA, mobile drawer | **Selesai** | **100%** |
| 6 | **Formulir Keluhan (Semua Wajib)** | Tampilan web 2-kolom responsif di mana seluruh field berstatus Wajib Diisi (`*`) | **Selesai** | **100%** |
| 7 | **Fitur Edit Data Jurnal** | Formulir edit data keluhan dengan pre-fill data, auto-fill AJAX, dan tombol edit via modal Detail | **Selesai** | **100%** |
| 8 | **Modal Konfirmasi Hapus Data** | Dialog konfirmasi interaktif dengan rincian data nasabah sebelum penghapusan permanen | **Selesai** | **100%** |
| 9 | **Fitur Auto-Fill AJAX** | Auto-fill biaya admin dan channel saat memilih jenis transaksi | **Selesai** | **100%** |
| 10 | **Deteksi Keluhan Berulang** | Dua tingkat: kembar persis (Nama + Resi + Tanggal) ditolak, berulang (Nama + Resi) diperingatkan; pemeriksaan langsung saat mengetik, panel rincian keluhan lama, penanda & filter di Data Keluhan | **Selesai** | **100%** |
| 11 | **Case-Insensitive Live Search & Highlight** | Pencarian instan otomatis tanpa peduli huruf besar/kecil dengan highlight kuning | **Selesai** | **100%** |
| 12 | **Tabel Data Keluhan Ultra Rapi** | Tabel daftar jurnal 9 kolom (termasuk kolom Channel terpisah) dengan single button "Detail" di kolom aksi | **Selesai** | **100%** |
| 13 | **Pusat Aksi di Modal Detail** | Pop-up modal rincian 16 field lengkap dengan tombol Edit Data dan Hapus Data berdampingan | **Selesai** | **100%** |
| 14 | **Laporan Rekapitulasi Keluhan Bulanan** | Matriks 12 bulan (Jan-Des), tab drilldown bulanan, 8 kelompok klaim standar, dan export Excel identik | **Selesai** | **100%** |
| 15 | **Monitoring Mesin ATM Bermasalah** | Peringkat terminal ATM berdasarkan jumlah keluhan terbanyak, KPI cards, filter cabang/periode, modal drill-down nasabah, dan export Excel | **Selesai** | **100%** |
| 16 | **Blockchain Jejak Audit** | Blok berantai dengan merkle root & proof of work, Block Explorer dengan bukti Merkle, jangkar ke smart contract Ethereum Sepolia | **Selesai** (jangkar Sepolia menunggu dompet & deploy kontrak) | **95%** |

---

## 🔑 3. Kredensial & Peran Pengguna

| Peran | Hak Akses |
|:---|:---|
| **Admin Pusat** (Divisi IT) | Kelola Jurnal Keluhan, verifikasi pengaduan cabang, laporan, monitoring ATM, manajemen pengguna. |
| **CS Cabang** | Kirim pengaduan nasabah + lampiran, pantau status pengaduan cabangnya sendiri. |

### Cara Memperoleh Akun

**Tidak ada kata sandi bawaan di server produksi.** Akun Admin Pusat pertama dibuat
langsung di server dengan kata sandi yang diketik petugas dan tidak pernah tersimpan
di dalam kode:

```powershell
php artisan admin:buat --superadmin
```

Pada lingkungan **pengembangan** (`APP_ENV=local`), `php artisan migrate:fresh --seed`
membuat akun uji `admin` / `admin123` dan CS contoh `cs.palu` / `cs12345`. Kedua akun
ini **tidak pernah dibuat di luar lingkungan pengembangan**, dan seeder tidak akan
menimpa kata sandi akun yang sudah ada.

URL login sama untuk kedua peran: `http://127.0.0.1:8000/login`. Setelah login, Admin diarahkan ke `/` dan CS ke `/cs`. Akun CS lain dibuat Admin lewat menu **Manajemen Pengguna** (`/pengguna`) dan wajib terikat ke satu kantor cabang. Akun tidak dihapus, hanya **dinonaktifkan**.

---

## 📨 3a. Modul Pengaduan CS Cabang → Admin Pusat

### Alur & Siklus Status

```text
CS kirim pengaduan ──► Terkirim ──► Diterima ──► Diproses ──► Selesai
   (boleh edit/hapus)      │           │   (masuk jurnal)  (jurnal Done/Success)
                           └───────────┴──► Ditolak (catatan pusat wajib / jurnal Rejected)
```

| Status | Label di CS | Pemicu |
|:---|:---|:---|
| `Terkirim` | Menunggu Verifikasi Pusat | CS mengirim formulir. CS masih boleh mengedit/menghapus. |
| `Diterima` | Diterima Pusat | Admin klik **Terima**. Data terkunci untuk CS. |
| `Diproses` | Dalam Proses Jurnal | Admin klik **Input ke Jurnal** → form jurnal terisi otomatis → disimpan (`jurnal_id` terisi). |
| `Selesai` | Selesai | Status jurnal diubah menjadi `Done` / `Success`. |
| `Ditolak` | Ditolak | Admin klik **Tolak** (wajib alasan) atau status jurnal `Rejected`. |

Sinkronisasi status jurnal → pengaduan berjalan otomatis (observer). Menghapus jurnal mengembalikan pengaduan ke `Diterima`.

### Skala Tampilan Adaptif
Ukuran tampilan sistem **menyesuaikan lebar layar secara otomatis**. Pada laptop 14 inci ukurannya tetap seperti biasa, lalu membesar bertahap pada monitor yang lebih besar sehingga teks tidak terasa mengecil dan pengguna tidak perlu melakukan zoom.

| Lebar layar | Contoh perangkat | Ukuran teks isi | Teks terkecil |
|:---|:---|:---:|:---:|
| 1366 px | Laptop 14 inci | 14,0 px | 11,5 px |
| 1920 px | Monitor 22–24 inci | 15,0 px | 12,3 px |
| 2560 px | Monitor 27 inci QHD | 16,2 px | 13,3 px |
| 3840 px | Monitor 4K | 16,6 px | 13,7 px |

Seluruh elemen ikut berskala: tinggi tombol, lebar sidebar, lebar kolom tabel, dan jarak antarelemen, sehingga proporsi tampilan tetap terjaga. Pengaturan ukuran huruf bawaan browser pengguna juga tetap dihormati, jadi pengguna yang memperbesar teks lewat pengaturan browser tetap terlayani. Dokumen cetak A4 pada menu Cetak sengaja dikecualikan agar hasil cetaknya tetap presisi.

Ukuran teks terkecil dinaikkan menjadi 11,5 piksel dan warna teks keterangan dipertegas agar memenuhi standar keterbacaan WCAG AA.

### Dialog Konfirmasi
Tindakan penting seperti **Terima Pengaduan**, **Tolak Pengaduan**, menghapus lampiran, dan menonaktifkan akun pengguna memakai **modal konfirmasi bergaya sistem**, bukan dialog bawaan browser. Setiap dialog menampilkan ikon berwarna, judul, rincian data yang terdampak, serta tombol Batal dan tombol lanjut yang warnanya menyesuaikan tingkat risiko tindakan. Modal dapat ditutup dengan tombol Batal, tombol Escape, atau mengklik area di luar kotak.

### Field Formulir Pengaduan CS (`/cs/pengaduan/input`)

| Bagian | Field | Keterangan |
|:---|:---|:---|
| Data Pelapor | `nama_pelapor`*, `master_cabang_id`* (asal cabang, dipilih manual; default cabang akun CS), `kategori`*, `sub_kategori`, `sub_kategori_2` | Kategori berupa teks bebas. Pengaduan terlihat oleh CS pengirim, CS lain di cabang akun yang sama, dan CS di cabang asal yang dipilih. |
| Data Nasabah | `nama_nasabah`*, `no_hp`*, `no_ktp`* (16 digit), `no_rekening`*, `no_kartu` | |
| Data Transaksi | `master_transaksi_id`*, `channel`* (prinsipal), `no_resi`*, `terminal_transaksi`, `nominal_transaksi`* (Rp), `tgl_transaksi`* | Channel terisi otomatis dari jenis transaksi. |
| Lampiran | `foto_ktp`* , `buku_tabungan`, `kartu_atm`, `form_keluhan`, `lainnya` | Maks 5 berkas / jenis, 5 MB / berkas (JPG/PNG/WEBP/PDF). **Format berkas selalu sama dengan kiriman CS** dan tidak pernah dikonversi ke PDF. Foto beresolusi besar diperkecil otomatis agar hemat penyimpanan. |
| Keterangan | `kronologi`* (min. 20 karakter) | |

Nomor pengaduan dibuat otomatis: `PGD-{kode_cabang}-{YYYYMMDD}-{urut}`.

### Keamanan & Penyimpanan Lampiran
Lampiran (berisi KTP) disimpan di disk **privat** `storage/app/private/pengaduan/{id}/` dan hanya dapat dibuka lewat `/pengaduan/{id}/lampiran/{lampiranId}` oleh Admin Pusat atau CS yang berwenang. Tidak diperlukan `storage:link`.

### Format & Optimalisasi Berkas
**Format berkas selalu dipertahankan**: JPG tetap JPG, PNG tetap PNG, WEBP tetap WEBP, dan PDF tidak pernah disentuh sama sekali. Yang dioptimalkan hanya gambar, yaitu koreksi orientasi foto ponsel (EXIF) dan pengecilan resolusi bila sisi terpanjang melebihi `lebar_maks_gambar` (bawaan 2000 piksel), lalu disimpan ulang dengan format yang sama.

| Berkas | Sebelum | Sesudah |
|:---|:---|:---|
| Foto ponsel 4000 × 3000 piksel | 4,3 MB | 0,5 MB (hemat 88%) |
| Foto/scan ≤ 2000 piksel | tidak diubah | tidak diubah |
| Berkas PDF | tidak diubah | tidak diubah |

Tiga pengaman agar berkas tidak pernah menjadi lebih buruk:
1. Gambar yang sudah kecil dan orientasinya benar disimpan apa adanya tanpa dikodekan ulang.
2. Bila hasil olahan ternyata lebih besar dari berkas asli, berkas asli yang dipakai (kasus PNG berblok warna datar).
3. Kanal transparansi hanya dipertahankan bila berkas aslinya memang punya transparansi.

Pengaturan ada di `config/pengaduan.php`: `lebar_maks_gambar`, `kualitas_gambar`, `kompresi_png`, `max_ukuran_file_kb`, `max_file_per_jenis`.

---

## 🏛️ 4. Master Data 41 Kantor Cabang & KCP Bank Sulteng

| No | Kode | Nama Kantor Cabang / KCP | No | Kode | Nama Kantor Cabang / KCP |
|:---|:---:|:---|:---|:---:|:---|
| 1 | `000` | BANK LAIN | 22 | `302` | KCP WAKAI |
| 2 | `001` | CABANG UTAMA | 23 | `303` | KCP TENTENA |
| 3 | `002` | CABANG TOLI TOLI | 24 | `304` | KCP PENDOLO |
| 4 | `003` | CABANG POSO | 25 | `305` | KCP NAPU |
| 5 | `004` | CABANG LUWUK | 26 | `401` | CABANG KOLONODALE |
| 6 | `005` | CABANG BUNGKU | 27 | `402` | CABANG BANGGAI LAUT |
| 7 | `006` | CABANG SALAKAN | 28 | `403` | KCP BETELEME |
| 8 | `007` | CABANG SIGI | 29 | `404` | KCP BATUI |
| 9 | `008` | CABANG PALU BARAT | 30 | `405` | KCP TOILI |
| 10 | `009` | CABANG JAKARTA | 31 | `411` | KCP MAMOSALATO |
| 11 | `101` | CABANG DONGGALA | 32 | `412` | KCP TOMATA |
| 12 | `102` | CABANG PARIGI | 33 | `413` | KCP BATURUBE |
| 13 | `103` | KCP LAMBUNU | 34 | `501` | KCP BAHOMOTEFE |
| 14 | `104` | KCP LABEAN | 35 | `502` | KCP BAHODOPI |
| 15 | `105` | KCP TOLAI | 36 | `701` | KCP KULAWI |
| 16 | `106` | KCP TINOMBO | 37 | `801` | KCP TAWAELI |
| 17 | `107` | KCP TINOMBALA | 38 | `406` | KCP MASAMA |
| 18 | `201` | CABANG BUOL | 39 | `306` | KCP TAMBARANA |
| 19 | `202` | KCP SONI | 40 | `407` | KCP BUNTA |
| 20 | `211` | KCP PALELEH | 41 | `108` | KCP KOTARAYA |
| 21 | `301` | CABANG AMPANA | | | |

---

## 📝 5. Rincian Field Input Formulir Jurnal (Semua Wajib Diisi)

| No | Label Form | Nama Input | Tipe Input | Sifat | Keterangan |
|:---|:---|:---|:---|:---|:---|
| 1 | **Nama Nasabah** | `nama_nasabah` | Text | **Wajib (`*`)** | Nama lengkap nasabah pelapor. |
| 2 | **No. Rekening** | `no_rekening` | Text | **Wajib (`*`)** | Nomor rekening nasabah yang didebet. |
| 3 | **No. Resi / Trace Number** | `no_resi` | Text | **Wajib (`*`)** | Nomor resi/trace transaksi ATM/EDC/Mobile. |
| 4 | **Nomor Kartu ATM/Debit** | `no_kartu` | Text | **Wajib (`*`)** | Nomor kartu ATM/Debit nasabah. |
| 5 | **Nomor Tiket Keluhan** | `no_tiket` | Text | **Wajib (`*`)** | Diketik petugas sesuai berkas. Terkunci otomatis hanya bila jurnal berasal dari pengaduan CS. Lihat bagian penomoran tiket di bawah. |
| 6 | **Cabang Transaksi / Pelapor** | `master_cabang_id` | Select | **Wajib (`*`)** | Dropdown 41 daftar kantor cabang Bank Sulteng. |
| 7 | **Jenis Transaksi** | `master_transaksi_id` | Select | **Wajib (`*`)** | Dropdown 33 jenis transaksi (memicu auto-fill). |
| 8 | **Channel Transaksi (Otomatis)** | `channel` | Text (Readonly) | Otomatis | Terisi otomatis sesuai channel jenis transaksi. |
| 9 | **Biaya Admin (Pilihan)** | `biaya_admin` | Select | **Wajib (`*`)** | Dropdown pilihan biaya admin: `- (Rp 0)`, `Rp 1.000`, `Rp 1.500`, `Rp 1.750`, `Rp 2.000`, `Rp 2.500`, `Rp 2.750`, `Rp 3.000`, `Rp 6.500`, `Rp 7.500` / sesuai Excel saat import. |
| 10 | **Biaya / Nominal Transaksi** | `nominal_transaksi` | Number | **Wajib (`*`)** | Jumlah nominal uang yang dikeluhkan nasabah (Rp). |
| 11 | **Terminal Transaksi / Mesin** | `terminal_transaksi` | Text | **Wajib (`*`)** | ID Mesin ATM / Terminal EDC. |
| 12 | **Tanggal Transaksi Bermasalah** | `tgl_transaksi` | Date | **Wajib (`*`)** | Tanggal saat nasabah melakukan transaksi yang bermasalah. |
| 13 | **Tanggal Terima Keluhan** | `tgl_terima` | Date | **Wajib (`*`)** | Tanggal saat cabang/petugas menerima pengaduan nasabah. |
| 14 | **Tanggal Selesai Penanganan** | `tgl_selesai` | Date | Opsional | Tanggal saat keluhan selesai diproses (boleh kosong / `-` jika belum selesai). |
| 15 | **Status Keluhan** | `status` | Teks + daftar pilihan | **Wajib (`*`)** | **Dapat diketik manual** atau dipilih dari daftar standar: **`-` (Belum Ditentukan)**, **Menunggu**, **Success**, **Done**, **Rejected** (otomatis `-` jika kosong di Excel). Lihat catatan status kustom di bawah. |
| 16 | **Keterangan Log / Kronologi** | `keterangan_log` | Textarea | Opsional | Catatan kronologi keluhan atau detail tindak lanjut (default `-`). |

### Penomoran Tiket Keluhan
Setiap keluhan memiliki **satu nomor tiket** yang dipakai dari awal sampai selesai. Nomor dibuat otomatis oleh sistem dan tidak dapat diketik petugas.

```text
BS-2026090412345
│   │        └── 5 angka acak yang belum terpakai pada tanggal tersebut
│   └─────────── Tanggal (YYYYMMDD)
└─────────────── Awalan tetap Bank Sulteng
```

Ada dua jalur masuk keluhan, dan penomorannya berbeda.

**Jalur 1 — Keluhan dari CS cabang (nomor dibuat sistem).** Nomor lahir saat CS mengirim keluhan, lalu dibawa terus ke Jurnal Keluhan tanpa dibuat ulang, sehingga CS dan Admin Pusat selalu merujuk nomor yang sama.

```text
CS kirim keluhan ──► Nomor tiket dibuat ──► Admin verifikasi ──► Masuk jurnal
                     BS-2026090412345         (nomor sama)        (nomor sama)
```

**Jalur 2 — Berkas fisik yang sampai ke Divisi IT (nomor diketik petugas).** Berkas menempuh beberapa tahap sebelum tiba di Divisi IT:

```text
CS cabang ──► Penyelia cabang ──► Divisi Literasi ──► Admin Divisi IT
```

Karena itu **Tanggal Terima Keluhan** pada form jurnal adalah tanggal berkas selesai diperiksa Divisi Literasi, **bukan** tanggal CS menerima keluhan dari nasabah. Nomor tiket tidak boleh dibuat dari tanggal tersebut, sehingga petugas mengetiknya sendiri sesuai berkas yang diterima.

| Hal | Ketentuan |
|:---|:---|
| Jalur pengaduan CS | Nomor dibuat sistem saat pengaduan dikirim, memakai tanggal pengiriman. Pada form jurnal nomor tampil **terkunci** dan tidak dapat diubah, agar rujukan CS cabang tetap sama. |
| Jalur jurnal langsung | Nomor **diketik petugas** sesuai berkas, wajib diisi, dan bebas format karena bisa saja mengikuti penomoran lama. |
| Perapian penulisan | Nomor berformat BS ditulis rapat tanpa spasi. Ketikan seperti `BS - 2026081347674`, `BS- 2026…`, `bs - 2026…`, bahkan `BS 2026…` yang lupa tanda hubung, otomatis dirapikan menjadi `BS-2026081347674` — baik saat mengetik di form, saat disimpan, maupun saat import Excel. Nomor manual berupa teks bebas seperti `PRO AKTIF` **tidak ikut diubah**. |
| Angka acak (jalur CS) | Diambil dengan pembangkit acak kriptografis, lalu dipastikan belum terpakai pada tanggal yang sama, dicek ke data pengaduan maupun jurnal. |
| Kapasitas | 100.000 nomor untuk setiap tanggal. |
| Bila bentrok | Sistem mencoba angka lain hingga 25 kali, lalu beralih mencari angka bebas secara berurutan sehingga pengiriman tidak pernah gagal. |
| Bersamaan | Pembuatan nomor dikunci di tingkat database, jadi dua CS yang mengirim pada detik yang sama tidak mendapat nomor kembar. |
| Saat diedit | Jurnal jalur langsung boleh dikoreksi nomornya. Jurnal jalur pengaduan CS nomornya tetap. |
| Data lama | Nomor tiket lama hasil impor Excel dibiarkan apa adanya. |

### Status Keluhan yang Diketik Manual
Kolom **Status Keluhan** pada form Input Jurnal maupun Edit Jurnal berupa isian teks yang menyatu dengan daftar pilihan. Petugas dapat mengetik status apa pun sesuai kondisi penanganan, misalnya `Menunggu Konfirmasi Bank Lain` atau `Sedang Investigasi Vendor ATM`, atau memilih salah satu status standar dari daftar.

| Perilaku | Keterangan |
|:---|:---|
| Penyeragaman penulisan | Ketikan yang sama dengan status standar diseragamkan otomatis, `done` menjadi `Done`, sehingga perhitungan statistik tidak terpecah. |
| Perapian spasi | Spasi berlebih dirapikan, `Menunggu   Dokumen` menjadi `Menunggu Dokumen`. |
| Batas panjang | Maksimal 50 karakter. |
| Filter data | Status kustom otomatis muncul pada dropdown filter halaman Data Keluhan di grup **Status Kustom (diketik manual)**. |
| Kartu statistik | Kartu ringkasan hanya menghitung status standar. Status kustom masuk hitungan **Total Keluhan** tetapi tidak pada kartu Menunggu / Success / Done / Rejected. |
| Lencana tabel | Status kustom tampil dengan lencana abu-abu netral. |
| Status pengaduan CS | Jurnal berstatus kustom membuat pengaduan cabang berstatus **Dalam Proses Jurnal**. Status **Selesai** hanya tercapai bila status jurnal `Done` atau `Success`, dan **Ditolak** bila `Rejected`. |

### Deteksi Keluhan Berulang
Sistem memeriksa apakah satu keluhan sudah pernah ditangani berdasarkan kombinasi **Nama Nasabah + Nomor Resi**. Pemeriksaan berjalan otomatis begitu kedua kolom itu terisi, jadi petugas mengetahuinya sebelum mengisi kolom-kolom lain.

```text
Isi Nama Nasabah + No. Resi
            │
            ▼
    ┌───────┴────────┬─────────────────┐
    ▼                ▼                 ▼
  AMAN            BERULANG           KEMBAR
 tidak ada     Nama & Resi sama,   Nama, Resi, DAN
 peringatan    tanggal berbeda     tanggal sama persis
    │                │                 │
    │          panel kuning       panel merah
    │          + konfirmasi       tombol Simpan
    │          saat menyimpan     dinonaktifkan
```

| Tingkat | Kondisi | Perlakuan |
|:---|:---|:---|
| **Kembar Persis** | Nama Nasabah, No. Resi, **dan** Tanggal Transaksi sama | **Ditolak.** Data seperti ini memang dilarang tersimpan dua kali. Perbarui jurnal yang sudah ada, atau perbaiki tanggal transaksinya. |
| **Berulang** | Nama Nasabah dan No. Resi sama, tanggal transaksi **berbeda** | **Diperingatkan, boleh dilanjutkan.** Petugas menekan **Saya paham, tetap simpan** pada dialog konfirmasi. |
| **Aman** | Selain itu | Tidak ada peringatan. |

Keluhan **Berulang** sengaja tidak diblokir. Nomor resi (*trace number*) mesin ATM berputar dan dipakai ulang, sehingga satu nasabah dapat benar-benar memiliki nomor resi yang sama pada tanggal yang berbeda. Pada data yang ada saat ini pun terdapat 17 nomor resi yang dipakai oleh nasabah yang berbeda-beda, jadi nomor resi saja tidak pernah dijadikan patokan.

| Bagian | Perilaku |
|:---|:---|
| Panel peringatan | Muncul tepat di bawah kolom No. Resi, memuat nomor tiket, status, tanggal transaksi, cabang, dan nominal keluhan lama, beserta tautan **Lihat jurnal ini**. |
| Pengaduan cabang | Pengaduan CS yang belum dijurnal ikut terdeteksi, sehingga keluhan yang sedang berjalan di cabang juga ketahuan. |
| Halaman Data Keluhan | Baris yang punya kembaran ditandai lencana **Berulang ×n**. Tersedia penyaring **Cek data berulang** pada panel filter. |
| Form Pengaduan CS | CS mendapat pemberitahuan ringkas bila keluhan itu sudah tercatat di pusat, **tanpa pernah menghalangi** pengiriman pengaduan. |
| Halaman Pengaduan Masuk | Peringatan tampil sebelum tombol **Input ke Jurnal**, sehingga Admin Pusat tahu lebih dulu. |
| Import Excel | Tidak pernah diblokir. Jumlah baris berulang dilaporkan pada pesan hasil import. |
| Edit jurnal | Jurnal yang sedang diubah tidak dilaporkan sebagai kembaran dirinya sendiri. |

Penulisan huruf besar/kecil dan spasi berlebih tidak memengaruhi hasil pemeriksaan: `budi santoso` tetap dikenali sama dengan `BUDI SANTOSO`.

---

## 📂 6. Struktur Berkas & Kode Program

```text
Jurnal_Banksulteng/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php          <-- Logika Login berbasis username, Logout, dan autentikasi admin
│   │   ├── Controller.php            <-- Base Controller Laravel
│   │   └── JurnalController.php        <-- Simpan, Edit, Update, Hapus, Live Search & API AJAX
│   └── Models/
│       ├── AuditTrail.php              <-- Model Audit Trail (hash chaining)
│       ├── Jurnal.php                  <-- Model Jurnal Keluhan (mass-assignment protected)
│       ├── MasterCabang.php            <-- Model Master Kantor Cabang (41 Cabang & KCP)
│       ├── MasterTransaksi.php         <-- Model Master Jenis Transaksi & Channel
│       └── User.php                    <-- Model Pengguna / Petugas (fillable: name, username, email, password)
├── database/
│   ├── migrations/                     <-- Berkas Migrasi Skema Database (+ username)
│   └── seeders/
│       ├── DatabaseSeeder.php          <-- Seeder Utama pemanggil MasterSeeder & UserSeeder
│       ├── MasterSeeder.php            <-- Seeder 33 Jenis Transaksi & 41 Data Cabang
│       └── UserSeeder.php              <-- Seeder Akun Admin Default (username: admin, pass: admin123)
├── resources/
│   └── views/
│       ├── auth/
│       │   └── login.blade.php         <-- Tampilan Login Bersih (Username & Eye Icon)
│       ├── layouts/
│       │   └── app.blade.php           <-- Master Layout Blade (Sidebar & Logout Bank Sulteng)
│       ├── jurnal_form.blade.php       <-- Tampilan Form Input Jurnal (Semua Input Wajib Diisi *)
│       ├── jurnal_edit.blade.php       <-- Tampilan Form Edit Jurnal Keluhan (Pre-filled + AJAX)
│       └── jurnal_data.blade.php       <-- Tampilan Data Keluhan (Tabel Single Button Detail, Modal Edit & Hapus)
├── routes/
│   └── web.php                         <-- Rute Web: '/login', '/logout', '/', '/jurnal/data', GET/PUT '/jurnal/{id}/edit', DELETE '/jurnal/{id}'
├── .env                                <-- Konfigurasi Database (MySQL) & App Key
└── DOCUMENTATION.md                    <-- Dokumen Panduan & Catatan Projek Ini
```

---

## 🚀 7. Panduan Menjalankan Sistem

1. Masuk ke direktori projek:
   ```powershell
   cd C:\bank-sulteng\projek\projek1\Jurnal_Banksulteng
   ```
2. Menjalankan server lokal:
   ```powershell
   php artisan serve
   ```
   *(Atau menggunakan path PHP Laragon: `& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" artisan serve`)*

3. Buka browser di: **`http://127.0.0.1:8000/login`**
4. Masuk memakai akun yang dibuat pada langkah setup (lihat bagian 3).
   Pada lingkungan pengembangan, seeder menyediakan akun uji `admin` / `admin123`.

---

## 🔒 8. Deploy & Pengerasan Keamanan

Daftar periksa wajib sebelum aplikasi ini dijalankan di server yang dapat diakses
pengguna lain. Selama masih di laptop pengembang, `.env.example` sudah memadai.

### 8.1 Berkas konfigurasi

Salin `.env.production.example` menjadi `.env` di server, lalu isi nilainya.
Empat baris yang paling menentukan:

| Kunci | Nilai wajib | Akibat bila salah |
|:---|:---|:---|
| `APP_ENV` | `production` | Laravel bersikap longgar terhadap galat. |
| `APP_DEBUG` | `false` | Halaman galat menampilkan **seluruh isi `.env`**, termasuk kata sandi database, kepada siapa pun yang memicu error. |
| `SESSION_SECURE_COOKIE` | `true` | Cookie sesi ikut terkirim lewat HTTP polos dan dapat disadap di jaringan kantor. |
| `DB_USERNAME` | bukan `root` | Satu celah SQL menjadi kendali penuh atas seluruh basis data server. |

### 8.2 Urutan pemasangan

```powershell
cp .env.production.example .env
php artisan key:generate
# Cadangkan APP_KEY dari .env ke brankas kata sandi SEKARANG, terpisah dari
# cadangan basis data. NIK nasabah disimpan terenkripsi dengan kunci ini dan
# tidak dapat dipulihkan tanpanya — lihat bagian 8.10.
php artisan migrate --force
php artisan db:seed --class=MasterSeeder --force   # hanya master data 41 cabang & 33 transaksi
php artisan admin:buat --superadmin                # akun admin, kata sandi diketik petugas
php artisan config:cache; php artisan route:cache; php artisan view:cache
```

Jangan menjalankan `php artisan db:seed` polos di server: gunakan `--class=MasterSeeder`
seperti di atas agar akun contoh tidak ikut terbawa.

Dan **jangan pernah menjalankan `php artisan key:generate` lagi** setelah server berisi
data: kunci baru membuat seluruh NIK yang sudah tersimpan tidak terbaca.

### 8.3 Pembatas percobaan login

Sejak pengerasan ini, halaman login memiliki dua lapis pembatas:

| Lapis | Aturan | Berkas |
|:---|:---|:---|
| Per akun | 5 percobaan gagal pada kombinasi username + alamat IP → dikunci 15 menit | `AuthController::pastikanBelumTerkunci()` |
| Per alamat IP | 20 permintaan per menit ke endpoint login | `routes/web.php` (`throttle:20,1`) |

Kata sandi yang terbukti benar langsung menolkan hitungan, sehingga penolakan karena
akun nonaktif atau sesi ganda tidak pernah ikut mengunci akun. Kunci sengaja memakai
**username + IP**, bukan username saja, agar pihak luar tidak dapat mengunci akun Admin
Pusat dari jauh. Konsekuensinya, serangan tebak kata sandi yang tersebar dari banyak
alamat IP belum tertutup sepenuhnya — lapis penutupnya adalah MFA.

### 8.4 Rekam Jejak Audit

Setiap kejadian penting ditulis ke tabel `audit_trails` oleh `App\Services\AuditTrailService`
— satu-satunya penulis tabel itu. Yang terekam saat ini:

| Aksi | Pemicu |
|:---|:---|
| `jurnal.dibuat` / `jurnal.diubah` / `jurnal.dihapus` | `JurnalObserver`. Perubahan hanya mencatat kolom yang benar-benar berubah, lengkap dengan nilai sebelum dan sesudah. |
| `jurnal.reset_massal` | Penghapusan seluruh jurnal, beserta nama berkas cadangannya. |
| `pengaduan.diterima` / `pengaduan.ditolak` | Keputusan Admin Pusat, beserta alasan penolakan. |
| `lampiran.dibuka` | Siapa membuka berkas KTP nasabah, kapan, dari alamat IP mana. |
| `login.gagal` / `login.terkunci` | Percobaan masuk yang gagal dan penguncian akun. |

Setiap baris menyimpan pelaku (id + salinan username), alamat IP, peramban, serta
nilai lama dan baru. **Baris jejak tidak pernah ikut terhapus bersama datanya** —
menghapus jurnal justru menyisakan jejak `jurnal.dihapus` berisi seluruh isi baris
yang hilang.

**Rantai hash.** Tiap baris menyegel hash baris sebelumnya
(`hash_sekarang = sha256(hash_sebelumnya | sidik jari baris)`). Menyunting atau
menghapus satu baris langsung lewat phpMyAdmin akan memutus rantai pada baris
sesudahnya. Rantai hanya berguna bila diperiksa:

```powershell
php artisan audit:periksa
```

Perintah ini menyebutkan baris mana yang bermasalah dan keluar dengan kode gagal
bila rantainya putus.

**Pemeriksaannya sudah dijadwalkan harian** (`routes/console.php`, pukul 01.00),
dengan keluaran ditulis ke `storage/logs/audit-periksa.log`. Penjadwal Laravel
hanya berjalan bila ada satu tugas sistem yang memanggilnya setiap menit. Di
server Windows, buat satu tugas di **Task Scheduler** yang berulang tiap 1 menit:

```powershell
php artisan schedule:run
```

Tanpa tugas itu, jadwalnya tercatat tetapi tidak pernah dijalankan — periksa
dengan `php artisan schedule:list`.

> Catatan privasi: jejak audit ikut memuat nilai kolom seperti nomor rekening dan
> nomor kartu. Aksesnya harus dibatasi seketat data aslinya.

**Dua hal yang sengaja tidak ikut disegel hash**, keduanya ditemukan saat menguji
rantai di MySQL (bukan di SQLite yang dipakai test):

1. **Urutan kunci JSON.** Kolom `nilai_lama`/`nilai_baru` bertipe `json`, dan MySQL
   menyimpannya dalam bentuk biner miliknya sendiri sambil **mengurutkan ulang kunci
   objek**. Karena itu kuncinya diurutkan dulu sebelum dihash — kalau tidak, setiap
   baris yang memuat lebih dari satu kolom akan dilaporkan "diubah" padahal tidak ada
   yang menyentuhnya, dan alarm yang selalu berbunyi sama saja dengan tidak ada alarm.
2. **Kolom `jurnal_id`.** Foreign key-nya memakai `nullOnDelete`, jadi basis data
   sendiri yang mengosongkannya begitu jurnal terkait dihapus. Menyegelnya berarti
   setiap penghapusan jurnal memutus rantai — tepat pada kejadian yang paling perlu
   dipercaya. Tautan ke jurnalnya tetap tersegel lewat `auditable_type` +
   `auditable_id`, yang tidak punya foreign key.

### 8.5 Header Keamanan HTTP

`App\Http\Middleware\HeaderKeamanan` memasang `X-Content-Type-Options: nosniff`,
`X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, dan
`Content-Security-Policy` pada seluruh respons; `Strict-Transport-Security`
menyusul otomatis begitu situs berjalan di atas HTTPS.

`nosniff` yang paling penting di sini: lampiran disajikan `inline`, dan tanpa header
itu peramban boleh menebak ulang tipe berkas KTP lalu memperlakukannya sebagai HTML.

CSP masih memuat `'unsafe-inline'` karena hampir seluruh view memakai `<script>` dan
style sebaris. Meski begitu, skrip dari domain luar, `<object>`, penyematan halaman
dalam frame, dan pengiriman formulir ke domain lain sudah tertutup. Menghapus
`'unsafe-inline'` menuntut nonce pada setiap blok skrip — pekerjaan tersendiri.

### 8.6 Penghapusan Seluruh Data Jurnal

Tombol **Hapus Semua Data** kini hanya tampil dan hanya dapat dijalankan oleh
**Admin Utama**. Sebelum menghapus, sistem meminta **kata sandi akun** diketik ulang
dan menulis **cadangan Excel otomatis** ke `storage/app/private/cadangan/`.
Tindakannya dicatat pada jejak audit lengkap dengan jumlah baris dan nama cadangan.

### 8.7 Kebijakan Kata Sandi

Satu aturan berlaku di kedua pintu pembuatan akun — form **Manajemen Pengguna**
dan perintah `admin:buat` — dan hanya ditulis sekali di `App\Rules\KataSandi`:

| Aturan | Nilai |
|:---|:---|
| Panjang minimum | 12 karakter |
| Isi | wajib memuat sedikitnya satu huruf dan satu angka |

Sebelumnya form web menerima 6 karakter sementara perintah server menuntut 12,
sehingga akun Admin Pusat yang dibuat lewat web bisa jauh lebih lemah daripada
yang dibuat di server, padahal keduanya membuka pintu yang sama.

Pemeriksaan terhadap daftar kata sandi bocor (HaveIBeenPwned) sengaja tidak
dipakai: aturan itu memanggil API lewat internet, dan bila server bank tidak
punya jalur keluar, pembuatan akun ikut gagal.

### 8.8 Lingkup Data Lintas Cabang

Endpoint `GET /api/duplikat/periksa` menerima nama nasabah dan nomor resi apa pun,
sehingga jawaban yang rinci membuatnya dapat dipakai satu akun cabang untuk
memetakan keluhan cabang lain hanya dengan mencoba banyak kombinasi.

Karena itu jawabannya dibedakan menurut peran:

| Peran | Yang diterima |
|:---|:---|
| **Admin Pusat** | Panel lengkap: nomor tiket, cabang asal, tanggal, nominal, status, tautan rincian, jumlah catatan. |
| **CS Cabang** | Hanya **ada** atau **tidak ada** — tanpa jumlah, tanpa kartu rincian, dan tanpa membedakan tanggal transaksi yang sama persis dari yang berbeda. |

Peringatan lintas cabang tetap muncul bagi CS (nasabah yang sama bisa mengadu
lewat cabang lain), tetapi rinciannya hanya ada pada Admin Pusat yang memang
berwenang melihat lintas cabang.

### 8.9 Penyamaran Nomor pada Layar Daftar

Nomor rekening kini tampil sebagai `••••1234` pada seluruh **layar daftar** —
Data Keluhan, Pengaduan Masuk, Daftar Pengaduan CS, dan Beranda CS. Jumlah titiknya
tetap empat, tidak mengikuti panjang aslinya, supaya panjang nomor pun tidak terbaca.
Aturannya hanya ditulis sekali di `App\Support\Penyamaran`.

| Tempat | Yang tampil |
|:---|:---|
| Layar daftar | `••••1234` |
| Panel rincian satu kasus | nomor utuh |
| Dokumen cetak (`resources/views/cetak/*`) | nomor utuh |

Alasannya: satu layar daftar memuat puluhan nasabah sekaligus, sehingga nomor yang
tercetak penuh ikut terbawa setiap kali layar difoto, dibagikan lewat berbagi layar,
atau sekadar terlihat orang yang lewat. Di panel rincian, petugas memang sedang
menangani satu kasus tertentu.

**Dua perubahan pendukung, dan tanpa keduanya penyamaran ini hanya menghibur mata:**

1. Atribut `data-search` pada setiap baris tabel Data Keluhan tidak lagi memuat
   nomor rekening dan nomor kartu. Atribut itu tercetak di sumber halaman, jadi
   nomor lengkap di sana membatalkan penyamaran di kolom sebelahnya. Pencarian
   nomor rekening/kartu tetap bekerja lewat sisi server — cukup tekan Enter,
   yang hilang hanya penyaringan seketika saat mengetik.
2. Tombol aksi pada tabel dulu menanam **seluruh baris** jurnal di dalam atribut
   `onclick`-nya. Sekarang tombol rincian hanya membawa `id` dan modalnya
   mengambil data lewat `GET /api/jurnal/{id}` untuk baris yang benar-benar
   dibuka; tombol lain memakai `Jurnal::bekalTombol()` yang tidak membawa nomor
   rekening maupun nomor kartu.
3. Panel keluhan berulang (`partials/panel_duplikat.blade.php`) dulu menanam
   seluruh baris jurnal **dan** seluruh baris pengaduan — termasuk NIK dan nomor
   HP — untuk sampai lima nasabah setiap kali panelnya muncul. Tombolnya kini
   hanya membawa `data-jurnal-id` / `data-pengaduan-id`, dan modalnya mengambil
   rincian lewat `GET /api/jurnal/{id}` atau `GET /api/pengaduan/{id}`. Endpoint
   pengaduan berada di dalam grup `role:admin` dan tetap melewati `PengaduanPolicy`.

> Ini tindakan **tampilan**, bukan kendali akses. Petugas yang melihat daftar tetap
> berwenang membuka rinciannya; yang dikurangi adalah paparan yang tidak disengaja.

NIK dan nomor HP **tidak** disamarkan: keduanya hanya muncul di panel rincian
pengaduan, tempat petugas justru perlu mencocokkannya dengan lampiran KTP nasabah.

### 8.10 Enkripsi NIK & Cadangan APP_KEY

Kolom `pengaduans.no_ktp` disimpan **terenkripsi**. Salinan basis data yang bocor —
lewat cadangan yang tertinggal, phpMyAdmin, atau berkas `.sql` yang dikirim ke pihak
lain — karena itu tidak langsung menjadi daftar nomor identitas nasabah.

Pembacaan dan penulisannya ditangani cast `encrypted` pada `App\Models\Pengaduan`,
sehingga tidak ada kode lain yang berubah: form CS, panel rincian, dan dokumen cetak
tetap menampilkan NIK utuh seperti biasa.

**Hanya kolom ini yang aman dienkripsi.**

| Kolom | Status | Alasan |
|:---|:---|:---|
| `no_ktp` | terenkripsi | Tidak dipakai indeks, pencarian, maupun deteksi duplikat. |
| `no_hp` | polos | Ikut dicari di `Pengaduan::scopeCari()` — jalur penelusuran saat nasabah menelepon menyusul laporannya. |
| `nama_nasabah`, `no_resi` | polos | Kunci deteksi keluhan berulang dan indeks `pengaduans_nama_resi_index`. |
| `no_rekening`, `no_kartu` | polos | Ikut dicari, dan `no_resi` + `nama_nasabah` menjadi kunci `jurnal_unique_kombinasi`. |

Mengenkripsi salah satu kolom pada baris "polos" akan mematikan pencarian atau
anti-duplikat, bukan sekadar memperlambatnya.

#### APP_KEY wajib dicadangkan terpisah

> **Sejak migrasi `2026_09_14_000001` dijalankan, isi kolom `no_ktp` bergantung
> sepenuhnya pada `APP_KEY`. Kunci yang hilang berarti NIK seluruh pengaduan tidak
> dapat dipulihkan — cadangan basis data pun tidak menolong, karena isinya ikut
> terenkripsi dengan kunci yang sama.**

Karena itu, sebelum menjalankan migrasi di server:

1. Salin nilai `APP_KEY` dari `.env` ke tempat penyimpanan rahasia milik bank
   (brankas kata sandi atau amplop tersegel), **terpisah dari cadangan basis data**.
   Menyimpan keduanya di satu tempat meniadakan gunanya enkripsi ini.
2. Pastikan cadangan `.env` ikut dalam prosedur pemulihan server, bukan hanya
   cadangan basis data.
3. Jangan pernah menjalankan `php artisan key:generate` di server yang sudah berisi
   data. Perintah itu mengganti kunci, dan seluruh NIK yang sudah tersimpan langsung
   tidak terbaca.

Bila enkripsi hendak dibatalkan, migrasinya dapat dijalankan mundur dan seluruh NIK
kembali tersimpan polos:

```powershell
php artisan migrate:rollback --step=1
```

Jalur maju maupun mundurnya aman dijalankan berulang: baris yang sudah terenkripsi
tidak dienkripsi dua kali, dan keduanya diuji di `tests/Feature/EnkripsiNikTest.php`.

### 8.11 Blockchain Jejak Audit

Rantai hash di bagian 8.4 menangkap penyuntingan satu baris, tetapi orang yang
memegang akses penuh ke basis data dapat mengubah satu baris lalu **menghitung
ulang seluruh hash sesudahnya**, dan rantainya terlihat utuh lagi. Blockchain ini
menutup celah itu dalam dua lapis.

**Lapis 1 — blockchain di dalam aplikasi** (`App\Services\BlockchainAuditService`,
tabel `blok_audits`, satu-satunya penulis tabel itu):

| Konsep | Wujudnya |
|:---|:---|
| Transaksi | Satu baris `audit_trails`. |
| Blok | Paling banyak `BLOCKCHAIN_UKURAN_BLOK` catatan (bawaan 10). Blok #N memuat catatan dengan id di rentang (akhir blok N-1, akhir blok N]. |
| Merkle root | Daun = hash tiap catatan yang **dihitung ulang dari isinya** (`AuditTrail::hitungHash`), bukan kolom `hash_sekarang`, sehingga mengubah isi sekaligus kolom hash-nya tetap ketahuan. Induk = `sha256(kiri . kanan)`; daun ganjil dipasangkan dengan dirinya sendiri (`App\Support\MerkleTree`). |
| Rantai | Kepala blok menyegel `hash_sebelumnya` (hash blok N-1). Genesis (#0) menunjuk 64 angka nol dan tidak berisi catatan. |
| Proof of work | `hash_blok = sha256(kepala | nonce)` wajib diawali `BLOCKCHAIN_KESULITAN` angka nol (bawaan 4, ≈65 ribu percobaan). |
| Anti-fork | `nomor`, `hash_sebelumnya`, dan `hash_blok` masing-masing ber-indeks unik. |

Catatan yang rantai hash-nya sudah rusak **tidak akan ditambang**. Penambang
berhenti dengan galat agar data palsu tidak ikut tersegel dan terlihat sah.

**Lapis 2 — jangkar Ethereum** (`App\Services\JangkarEthereumService`,
`blockchain/jangkar.mjs`, kontrak `blockchain/contracts/JangkarAudit.sol`):
tiap blok mengirim **hanya** nomor, hash blok, dan merkle root ke kontrak di
Ethereum Sepolia. Kontrak hanya menerima tulisan dari dompet pemiliknya dan
menolak menimpa nomor blok yang sudah tercatat. Pemalsu yang menambang ulang
seluruh rantai di server akan lolos pemeriksaan Lapis 1, tetapi hash bloknya
berbeda dengan yang tercatat di Ethereum. Skenario ini dipatok oleh tes
`tests/Feature/BlockchainAuditTest.php`.

> **Data nasabah tidak pernah dikirim ke Ethereum.** Data di blockchain publik
> tidak dapat dihapus, jadi yang dikirim hanya hash, yang tidak dapat dibalik
> menjadi data asli. Block Explorer juga tidak menampilkan `nilai_lama` /
> `nilai_baru`.

**Perintah:**

```powershell
php artisan blockchain:tambang            # tambang catatan baru + jangkarkan bila aktif
php artisan blockchain:tambang --penuh    # hanya blok yang sudah penuh
php artisan blockchain:periksa            # periksa rantai blok, PoW, merkle root
php artisan blockchain:periksa --jangkar  # ... dan cocokkan dengan Ethereum
```

Keduanya sudah terjadwal di `routes/console.php`: penambangan tiap 10 menit
(`storage/logs/blockchain.log`), pemeriksaan penuh setiap hari pukul 01.10
(`storage/logs/blockchain-periksa.log`). Jadwal ini butuh Task Scheduler yang sama
seperti bagian 8.4.

**Block Explorer:** menu *Keamanan → Blockchain Audit* (`/blockchain`, role admin).
Halamannya menampilkan status rantai, gambar lima blok terakhir, daftar blok,
rincian kepala blok, pohon Merkle, isi blok beserta **bukti Merkle** per catatan,
serta tombol *Tambang Blok*, *Jangkarkan ke Ethereum*, dan *Cocokkan dengan
Ethereum*.

#### Memasang jangkar Ethereum Sepolia

Semua langkah memakai **testnet**. ETH Sepolia tidak bernilai uang dan bisa
didapat gratis.

1. **Dompet.** Pasang MetaMask, buat akun **baru khusus untuk proyek ini**, lalu
   ekspor kunci privatnya (*Account details → Show private key*). Jangan pernah
   memakai dompet yang berisi ETH asli, karena kunci ini disimpan polos di `.env`.
2. **Saldo uji.** Minta ETH Sepolia dari faucet, misalnya Google Cloud Web3
   Faucet atau faucet Alchemy / Infura. Sekitar 0,05 ETH cukup untuk ratusan blok.
3. **RPC.** Pakai RPC publik `https://ethereum-sepolia-rpc.publicnode.com`, atau
   buat kunci gratis di Alchemy / Infura bila RPC publik sedang lambat.
4. **Isi `.env`:**
   ```dotenv
   ETH_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
   ETH_KUNCI_PRIVAT=0x...kunci-privat-dompet-uji...
   ```
5. **Pasang pustaka dan periksa dompet:**
   ```powershell
   cd blockchain
   npm install
   node jangkar.mjs info        # harus menampilkan jaringan "sepolia" dan saldo > 0
   ```
6. **Deploy kontrak** (sekali saja):
   ```powershell
   node jangkar.mjs deploy
   ```
   Salin `alamat` dan `blok_eth` dari keluarannya ke `.env`, lalu aktifkan:
   ```dotenv
   ETH_ALAMAT_KONTRAK=0x...alamat...
   ETH_BLOK_DEPLOY=...blok_eth...
   ETH_JANGKAR_AKTIF=true
   ```
7. `php artisan config:clear`, lalu tekan *Jangkarkan ke Ethereum* di Block
   Explorer atau jalankan `php artisan blockchain:tambang`. Setiap blok butuh
   ±12–30 detik menunggu konfirmasi Sepolia.

**Hal yang perlu diingat:**

- **Satu kontrak untuk satu rantai blok.** Bila tabel `blok_audits` dikosongkan
  (mis. `migrate:fresh`), blok baru akan memakai nomor yang sama dengan hash lain.
  Kontrak lama akan menolaknya sebagai *konflik*, dan memang seharusnya begitu.
  Deploy kontrak baru dan ganti `ETH_ALAMAT_KONTRAK`.
- Status **konflik** tidak pernah dicoba ulang otomatis. Artinya blok di server
  berbeda dengan yang sudah tercatat di Ethereum, sehingga harus ditelusuri.
- Server bank tanpa jalur keluar ke internet cukup membiarkan
  `ETH_JANGKAR_AKTIF=false`. Lapis 1 tetap berjalan penuh.
- `node` harus ada di PATH proses PHP (atau isi `NODE_BIN` dengan jalur lengkapnya).
- Batas yang jujur untuk dicatat di laporan: proof of work pada satu server
  **tidak** memberi konsensus seperti jaringan blockchain publik, karena hanya
  menaikkan biaya pemalsuan. Jaminan tidak-dapat-diubah yang sebenarnya datang
  dari Lapis 2.

**Skenario demo:**
1. Buka Block Explorer. Semua blok **VALID**, dan *Cocokkan dengan Ethereum*
   menunjukkan **Cocok**.
2. Ubah satu baris `audit_trails` lewat phpMyAdmin, misalnya kolom `username`.
3. Muat ulang. Blok terkait menjadi **RUSAK**, dan rinciannya menunjuk
   `Catatan audit #N diubah setelah masuk blok`. Bukti Merkle catatan itu gagal.
4. Kembalikan nilainya, dan blok kembali **VALID**. Hash hanya bergantung pada isi.

### 8.12 Yang belum dikerjakan

Butir berikut sudah teridentifikasi namun **belum** ada di dalam kode:

- Penyamaran NIK pada panel rincian pengaduan. Perlu disertai tombol "tampilkan
  penuh" dan pencatatan jejak audit seperti `lampiran.dibuka`, agar tidak
  menghalangi pencocokan dengan lampiran KTP.
- Serangan tebak kata sandi yang tersebar dari banyak alamat IP belum tertutup.
  Pembatas login mengunci per kombinasi username + alamat IP (bagian 8.3), jadi
  percobaan yang datang dari banyak alamat berbeda tidak terkena batasnya.
  Autentikasi dua faktor sempat dibangun lengkap untuk menutup celah ini, lalu
  **dihapus atas permintaan pengguna** dan tidak akan dipasang kembali.
- Menghapus `'unsafe-inline'` dari CSP; menuntut nonce pada setiap blok skrip.
