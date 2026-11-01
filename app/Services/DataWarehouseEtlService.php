<?php

namespace App\Services;

use App\Models\DimCabang;
use App\Models\DimTransaksi;
use App\Models\DimWaktu;
use App\Models\FactKeluhan;
use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service ETL (Extract, Transform, Load) untuk Data Warehouse E-Jurnal Keluhan.
 * Bertanggung jawab mengekstrak data operasional harian, mentransformasikannya
 * menjadi skema multidimensi, dan memuatnya ke tabel dimensi & fakta.
 */
class DataWarehouseEtlService
{
    /**
     * Nama-nama bulan dalam bahasa Indonesia.
     */
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Nama-nama hari dalam bahasa Indonesia.
     */
    private const NAMA_HARI = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu', 7 => 'Minggu',
    ];

    /**
     * Jalankan pipeline ETL secara lengkap.
     *
     * @param (callable(int $current, int $total, string $step): void)|null $progressCallback
     * @return array{
     *     total_ekstrak: int,
     *     total_fakta: int,
     *     dim_waktu_baru: int,
     *     dim_cabang_baru: int,
     *     dim_transaksi_baru: int,
     *     durasi_ms: float
     * }
     */
    public function jalankanEtl(?callable $progressCallback = null, bool $fresh = false): array
    {
        $mulai = microtime(true);

        if ($fresh) {
            FactKeluhan::truncate();
            DimWaktu::truncate();
            DimCabang::truncate();
            DimTransaksi::truncate();
        }

        // ==========================================
        // 1. EXTRACT (Mengekstrak Data Operasional)
        // ==========================================
        if ($progressCallback) {
            $progressCallback(0, 100, 'Mengekstrak data transaksi operasional...');
        }

        $jurnals = $this->ekstrakDataOperasional();
        $totalEkstrak = $jurnals->count();

        // ==========================================
        // 2. TRANSFORM & 3. LOAD DIMENSI
        // ==========================================
        if ($progressCallback) {
            $progressCallback(30, 100, 'Mentransformasi & memperbarui tabel dimensi...');
        }

        $waktuMap     = $this->sinkronisasiDimensiWaktu($jurnals);
        $cabangMap    = $this->sinkronisasiDimensiCabang();
        $transaksiMap = $this->sinkronisasiDimensiTransaksi();

        // ==========================================
        // 4. TRANSFORM & LOAD TABEL FAKTA
        // ==========================================
        if ($progressCallback) {
            $progressCallback(60, 100, 'Memuat data fakta keluhan ke Data Warehouse...');
        }

        $totalFaktaDimuat = $this->muatTabelFakta($jurnals, $waktuMap, $cabangMap, $transaksiMap, $progressCallback);

        $durasi = round((microtime(true) - $mulai) * 1000, 2);

        if ($progressCallback) {
            $progressCallback(100, 100, 'Sinkronisasi ETL selesai.');
        }

        return [
            'total_ekstrak'       => $totalEkstrak,
            'total_fakta'         => $totalFaktaDimuat,
            'dim_waktu_baru'      => DimWaktu::count(),
            'dim_cabang_baru'     => DimCabang::count(),
            'dim_transaksi_baru'  => DimTransaksi::count(),
            'durasi_ms'           => $durasi,
        ];
    }

    /**
     * Tahap 1 (EXTRACT): Ambil data dari tabel operasional jurnals beserta relasinya.
     */
    protected function ekstrakDataOperasional(): Collection
    {
        return Jurnal::with(['masterCabang', 'masterTransaksi'])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Tahap 2 & 3: Transformasi dan muat tabel Dimensi Waktu.
     * Mengembalikan mapping: 'Y-m-d' => dim_waktu_id
     */
    protected function sinkronisasiDimensiWaktu(Collection $jurnals): array
    {
        $dates = $jurnals->pluck('tgl_transaksi')
            ->filter()
            ->unique()
            ->values();

        $mapping = [];

        foreach ($dates as $dateStr) {
            $carbon = Carbon::parse($dateStr);
            $formatted = $carbon->format('Y-m-d');
            $bulan = (int) $carbon->format('n');
            $hari  = (int) $carbon->format('j');
            $dayOfWeek = (int) $carbon->dayOfWeek;

            $dimWaktu = DimWaktu::firstOrCreate(
                ['tanggal' => $formatted],
                [
                    'tahun'      => (int) $carbon->format('Y'),
                    'kuartal'    => 'Q' . ceil($bulan / 3),
                    'bulan'      => $bulan,
                    'nama_bulan' => self::NAMA_BULAN[$bulan] ?? $carbon->format('F'),
                    'hari'       => $hari,
                    'nama_hari'  => self::NAMA_HARI[$dayOfWeek] ?? $carbon->format('l'),
                ]
            );

            $mapping[$formatted] = $dimWaktu->id;
        }

        return $mapping;
    }

    /**
     * Tahap 2 & 3: Transformasi dan muat tabel Dimensi Cabang.
     * Mengembalikan mapping: master_cabang_id => dim_cabang_id
     */
    protected function sinkronisasiDimensiCabang(): array
    {
        $masterCabangs = MasterCabang::all();
        $mapping = [];

        foreach ($masterCabangs as $mc) {
            $dim = DimCabang::updateOrCreate(
                ['master_cabang_id' => $mc->id],
                [
                    'kode_cabang' => $mc->kode_cabang,
                    'nama_cabang' => $mc->nama_cabang,
                ]
            );
            $mapping[$mc->id] = $dim->id;
        }

        return $mapping;
    }

    /**
     * Tahap 2 & 3: Transformasi dan muat tabel Dimensi Transaksi.
     * Mengembalikan mapping: master_transaksi_id => dim_transaksi_id
     */
    protected function sinkronisasiDimensiTransaksi(): array
    {
        $masterTransaksis = MasterTransaksi::all();
        $mapping = [];

        foreach ($masterTransaksis as $mt) {
            $dim = DimTransaksi::updateOrCreate(
                ['master_transaksi_id' => $mt->id],
                [
                    'jenis_transaksi' => $mt->jenis_transaksi,
                    'channel'         => $mt->channel ?: '(Tanpa Channel)',
                ]
            );
            $mapping[$mt->id] = $dim->id;
        }

        return $mapping;
    }

    /**
     * Tahap 3 (LOAD): Transformasi logika bisnis dan muat rekaman ke tabel FactKeluhan.
     */
    protected function muatTabelFakta(
        Collection $jurnals,
        array $waktuMap,
        array $cabangMap,
        array $transaksiMap,
        ?callable $progressCallback = null
    ): int {
        $count = 0;
        $total = $jurnals->count();

        // Gunakan chunking dalam database transaction
        DB::transaction(function () use ($jurnals, $waktuMap, $cabangMap, $transaksiMap, &$count, $total, $progressCallback) {
            foreach ($jurnals as $index => $j) {
                $tanggalStr = $j->tgl_transaksi ? Carbon::parse($j->tgl_transaksi)->format('Y-m-d') : null;
                $dimWaktuId = $tanggalStr && isset($waktuMap[$tanggalStr]) ? $waktuMap[$tanggalStr] : null;

                // Jika tanggal transaksi null, pasang tanggal hari ini sebagai fallback aman
                if (! $dimWaktuId) {
                    $todayStr = Carbon::today()->format('Y-m-d');
                    $dimWaktu = DimWaktu::firstOrCreate(
                        ['tanggal' => $todayStr],
                        [
                            'tahun'      => (int) Carbon::today()->format('Y'),
                            'kuartal'    => 'Q' . ceil(Carbon::today()->month / 3),
                            'bulan'      => Carbon::today()->month,
                            'nama_bulan' => self::NAMA_BULAN[Carbon::today()->month],
                            'hari'       => Carbon::today()->day,
                            'nama_hari'  => self::NAMA_HARI[Carbon::today()->dayOfWeek],
                        ]
                    );
                    $dimWaktuId = $dimWaktu->id;
                }

                $dimCabangId    = $cabangMap[$j->master_cabang_id] ?? null;
                $dimTransaksiId = $transaksiMap[$j->master_transaksi_id] ?? null;

                // Lewati bila data referensi master hilang
                if (! $dimCabangId || ! $dimTransaksiId) {
                    continue;
                }

                // Kalkulasi Metrik Fakta (Business Metrics Transformation)
                $statusNormalized = trim($j->status ?: 'Menunggu');
                $isSelesai = in_array(strtolower($statusNormalized), ['done', 'success'], true);

                // Data Cleansing: Normalisasi durasi penyelesaian jika ada input tanggal yang terbalik
                $durasiHari = 0;
                if (! empty($j->tgl_terima) && ! empty($j->tgl_selesai)) {
                    $terima = Carbon::parse($j->tgl_terima);
                    $selesai = Carbon::parse($j->tgl_selesai);
                    $durasiHari = (int) abs($terima->diffInDays($selesai));
                }

                FactKeluhan::updateOrCreate(
                    ['jurnal_id' => $j->id],
                    [
                        'dim_waktu_id'             => $dimWaktuId,
                        'dim_cabang_id'            => $dimCabangId,
                        'dim_transaksi_id'         => $dimTransaksiId,
                        'status'                   => $statusNormalized,
                        'is_selesai'               => $isSelesai,
                        'nominal_transaksi'        => (float) ($j->nominal_transaksi ?? 0),
                        'biaya_admin'              => (float) ($j->biaya_admin ?? 0),
                        'durasi_penyelesaian_hari' => $durasiHari,
                    ]
                );

                $count++;

                if ($progressCallback && ($index % 25 === 0 || $index === $total - 1)) {
                    $percent = 60 + (int) (($index / max(1, $total)) * 40);
                    $progressCallback($percent, 100, "Memuat fakta keluhan ({$count}/{$total})...");
                }
            }
        });

        return $count;
    }

    /**
     * Dapatkan ringkasan statistik Data Warehouse untuk keperluan pelaporan / dashboard.
     */
    public function getStatistikDataWarehouse(): array
    {
        return [
            'total_fakta'             => FactKeluhan::count(),
            'total_selesai'           => FactKeluhan::where('is_selesai', true)->count(),
            'total_pending'           => FactKeluhan::where('is_selesai', false)->count(),
            'total_nominal_transaksi' => (float) FactKeluhan::sum('nominal_transaksi'),
            'total_biaya_admin'       => (float) FactKeluhan::sum('biaya_admin'),
            'rata_rata_durasi_hari'   => (float) round(FactKeluhan::where('durasi_penyelesaian_hari', '>', 0)->avg('durasi_penyelesaian_hari') ?? 0, 1),
            'jumlah_dimensi_cabang'   => DimCabang::count(),
            'jumlah_dimensi_channel'  => DimTransaksi::distinct('channel')->count('channel'),
        ];
    }
}
