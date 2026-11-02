<?php

namespace App\Services;

use App\Models\BlokAudit;
use App\Models\Jurnal;
use App\Models\SerahTerima;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SerahTerimaService
{
    /**
     * Status jurnal yang dianggap sudah selesai dan siap dibukukan / diserahterimakan.
     */
    public const STATUS_SELESAI = ['done', 'success', 'closed', 'selesai'];

    /**
     * Menghasilkan nomor dokumen resmi serah terima perbankan.
     * Format: ST/YYYYMMDD/XXXX (contoh: ST/20261102/0001).
     */
    public static function buatNomorDokumen(?Carbon $tanggal = null): string
    {
        $tgl = $tanggal ? $tanggal->copy() : now();
        $dateStr = $tgl->format('Ymd');
        $prefix = "ST/{$dateStr}/";

        // Hitung dokumen yang sudah dibuat pada tanggal yang sama
        $countToday = SerahTerima::where('nomor_dokumen', 'LIKE', "{$prefix}%")->count();
        $urutan = str_pad((string) ($countToday + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$urutan}";
    }

    /**
     * Menghitung akumulasi total tiket dan total nominal rupiah dari daftar jurnal.
     *
     * @param iterable|Collection|array $jurnals
     * @return array{total_tiket: int, total_nominal: float, total_nominal_rupiah: string}
     */
    public static function kalkulasiTotal($jurnals): array
    {
        $totalTiket = 0;
        $totalNominal = 0.0;

        foreach ($jurnals as $jurnal) {
            $totalTiket++;
            $nominal = is_object($jurnal)
                ? ($jurnal->nominal_transaksi ?? 0)
                : ($jurnal['nominal_transaksi'] ?? 0);

            $totalNominal += (float) $nominal;
        }

        return [
            'total_tiket' => $totalTiket,
            'total_nominal' => $totalNominal,
            'total_nominal_rupiah' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
        ];
    }

    /**
     * Mengunci integritas dokumen ke dalam bukti kriptografis blockchain (SHA-256).
     *
     * @param SerahTerima|array $data
     */
    public static function kunciKeBlockchain($data): string
    {
        if ($data instanceof SerahTerima) {
            $nomorDokumen = $data->nomor_dokumen;
            $tanggal = $data->tanggal_serah_terima instanceof Carbon
                ? $data->tanggal_serah_terima->toDateString()
                : (string) $data->tanggal_serah_terima;
            $totalNominal = (float) $data->total_nominal;
            $tiketIds = $data->tiket_ids ?? [];
        } else {
            $nomorDokumen = $data['nomor_dokumen'] ?? self::buatNomorDokumen();
            $tanggal = (string) ($data['tanggal_serah_terima'] ?? $data['tanggal'] ?? now()->toDateString());
            $totalNominal = (float) ($data['total_nominal'] ?? 0);
            $tiketIds = $data['tiket_ids'] ?? [];
        }

        if (is_array($tiketIds)) {
            sort($tiketIds);
        }

        // Tautkan dengan hash blok audit terakhir untuk perantaian blockchain (chaining)
        $latestBlock = BlokAudit::latest('nomor')->first();
        $prevHash = $latestBlock?->hash_blok ?? BlokAudit::HASH_NOL;

        $payload = implode('|', [
            'DOKUMEN:SERAH_TERIMA_PEMBUKUAN',
            $nomorDokumen,
            $tanggal,
            number_format($totalNominal, 2, '.', ''),
            json_encode($tiketIds),
            $prevHash,
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Mengambil query Builder tiket pengaduan / jurnal yang statusnya sudah Selesai (CLOSED).
     *
     * @param array $filters
     * @return Builder
     */
    public static function ambilTiketSelesai(array $filters = []): Builder
    {
        $query = Jurnal::with(['masterCabang', 'masterTransaksi', 'pengaduan.cabang', 'pengaduan.transaksi'])
            ->where(function (Builder $q) {
                $q->whereIn(DB::raw('LOWER(TRIM(status))'), self::STATUS_SELESAI);
            });

        // 1. Filter Pencarian Keyword (Nama Nasabah, No Tiket, No Rekening, No Resi)
        if (!empty($filters['q'])) {
            $keyword = trim((string) $filters['q']);
            $query->where(function (Builder $sub) use ($keyword) {
                $sub->where('nama_nasabah', 'LIKE', "%{$keyword}%")
                    ->orWhere('no_tiket', 'LIKE', "%{$keyword}%")
                    ->orWhere('no_rekening', 'LIKE', "%{$keyword}%")
                    ->orWhere('no_resi', 'LIKE', "%{$keyword}%");
            });
        }

        // 2. Filter Tanggal Penyelesaian (tgl_selesai)
        if (!empty($filters['tgl_selesai'])) {
            $query->whereDate('tgl_selesai', $filters['tgl_selesai']);
        }

        // 3. Filter Cabang
        if (!empty($filters['master_cabang_id'])) {
            $query->where('master_cabang_id', $filters['master_cabang_id']);
        }

        // Urutkan dari penyelesaian terbaru
        return $query->orderByDesc('tgl_selesai')->orderByDesc('id');
    }

    /**
     * Membuat dan menyimpan record SerahTerima baru ke database.
     */
    public static function buatSerahTerima(array $tiketIds, ?Carbon $tanggal = null, ?User $user = null): SerahTerima
    {
        $tgl = $tanggal ? $tanggal->copy() : now();
        $nomorDokumen = self::buatNomorDokumen($tgl);

        $jurnals = Jurnal::whereIn('id', $tiketIds)->get();
        $kalkulasi = self::kalkulasiTotal($jurnals);

        $hash = self::kunciKeBlockchain([
            'nomor_dokumen' => $nomorDokumen,
            'tanggal_serah_terima' => $tgl->toDateString(),
            'total_nominal' => $kalkulasi['total_nominal'],
            'tiket_ids' => $tiketIds,
        ]);

        return SerahTerima::create([
            'nomor_dokumen' => $nomorDokumen,
            'tanggal_serah_terima' => $tgl->toDateString(),
            'total_tiket' => $kalkulasi['total_tiket'],
            'total_nominal' => $kalkulasi['total_nominal'],
            'tiket_ids' => array_values(array_map('intval', $tiketIds)),
            'blockchain_hash' => $hash,
            'created_by' => $user?->id ?? auth()->id(),
        ]);
    }
}
