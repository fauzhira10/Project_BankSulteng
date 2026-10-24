<?php

namespace App\Models;

use App\Observers\JurnalObserver;
use App\Services\NomorTiketService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([JurnalObserver::class])]
class Jurnal extends Model
{
    /**
     * Kolom yang boleh diisi massal.
     *
     * Disebut satu per satu, bukan lewat $guarded, agar tidak ada kolom yang
     * ikut terisi hanya karena namanya kebetulan sama dengan nama input. Yang
     * paling penting: created_at dan updated_at kini tidak dapat dikirim dari
     * request, sehingga stempel waktu pencatatan keluhan tidak bisa dipalsukan.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_nasabah',
        'no_resi',
        'no_rekening',
        'no_kartu',
        'no_tiket',
        'master_cabang_id',
        'master_transaksi_id',
        'terminal_transaksi',
        'nominal_transaksi',
        'biaya_admin',
        'tgl_transaksi',
        'tgl_terima',
        'tgl_selesai',
        'status',
        'permasalahan',
        'keterangan_log',
        'ttd_data',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'ttd_data' => 'array',
    ];

    /**
     * Membuat nomor tiket baru. Dipakai oleh alur pengaduan CS cabang.
     * Jurnal yang diinput langsung Admin Pusat nomor tiketnya diketik petugas,
     * karena berasal dari proses sebelum berkas sampai ke Divisi IT.
     *
     * @see NomorTiketService
     */
    public static function generateNoTiket(mixed $tanggal = null): string
    {
        return NomorTiketService::buat($tanggal);
    }

    /**
     * Apakah pola nomor sesuai format resmi BS-{YYYYMMDD}{5 angka}.
     */
    public static function formatTiketValid(?string $nomor): bool
    {
        return NomorTiketService::formatValid($nomor);
    }

    /**
     * Bekal secukupnya untuk tombol-tombol aksi pada tabel Data Keluhan.
     *
     * Sengaja BUKAN seluruh baris. Markup tabel menanam bekal ini di dalam
     * atribut `onclick` setiap tombol, sehingga apa pun yang ikut di sini
     * tercetak di sumber halaman untuk seluruh baris sekaligus — dan itu
     * membatalkan penyamaran nomor rekening di kolom nasabah.
     *
     * `no_rekening` dan `no_kartu` karena itu tidak dibawa: modal rincian
     * mengambilnya lewat `GET /api/jurnal/{id}`, hanya untuk baris yang
     * benar-benar dibuka petugas. Kalau menambah kolom di sini, pastikan
     * kolom itu memang tidak apa-apa terbaca pada seluruh baris.
     *
     * @return array<string, mixed>
     */
    public function bekalTombol(): array
    {
        return [
            'id' => $this->id,
            'nama_nasabah' => $this->nama_nasabah,
            'no_resi' => $this->no_resi,
            'no_tiket' => $this->no_tiket,
            'nominal_transaksi' => $this->nominal_transaksi,
            'biaya_admin' => $this->biaya_admin,
            'status' => $this->status,
            'terminal_transaksi' => $this->terminal_transaksi,
            'keterangan_log' => $this->keterangan_log,
            'master_transaksi' => $this->masterTransaksi ? [
                'id' => $this->masterTransaksi->id,
                'jenis_transaksi' => $this->masterTransaksi->jenis_transaksi,
                'channel' => $this->masterTransaksi->channel,
                'biaya_admin' => $this->masterTransaksi->biaya_admin,
            ] : null,
        ];
    }

    public function masterCabang()
    {
        return $this->belongsTo(MasterCabang::class, 'master_cabang_id');
    }

    public function masterTransaksi()
    {
        return $this->belongsTo(MasterTransaksi::class, 'master_transaksi_id');
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class, 'jurnal_id');
    }

    /**
     * Pengaduan CS cabang yang menjadi sumber jurnal ini (jika ada).
     */
    public function pengaduan(): HasOne
    {
        return $this->hasOne(Pengaduan::class, 'jurnal_id');
    }

    /**
     * Mengambil snapshot konfigurasi TTD untuk tiket ini.
     * Jika belum tersimpan di database, gunakan pejabat default dari MasterPejabatTtd.
     *
     * @return array{mode: string, slots: array<int, array{pejabat_id: ?int, nama: string, jabatan: string, nip: string, ttd_image: string, is_kosong: bool}>}
     */
    public function getTtdConfig(): array
    {
        if (!empty($this->ttd_data) && is_array($this->ttd_data) && isset($this->ttd_data['slots'])) {
            return $this->ttd_data;
        }

        $defaults = MasterPejabatTtd::getAllDefault();
        $slots = [];

        for ($s = 1; $s <= 4; $s++) {
            $def = MasterPejabatTtd::DAFTAR_SLOT[$s];
            $pejabat = $defaults[$s] ?? null;

            $slots[$s] = [
                'pejabat_id' => $pejabat?->id,
                'nama' => $pejabat?->nama ?? $def['default_nama'],
                'jabatan' => $pejabat?->jabatan ?? $def['default_jabatan'],
                'nip' => $pejabat?->nip ?? '',
                'ttd_image' => $pejabat?->ttd_image ?? '',
                'is_kosong' => true, // Default saat cetak: kosong dulu untuk mode TTD basah
            ];
        }

        return [
            'mode' => 'manual',
            'slots' => $slots,
        ];
    }
}
