<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan Dashboard Utama Sistem Jurnal Keluhan
     */
    public function index(Request $request)
    {
        // === 1. Setup Filter Periode ===
        $periode      = $request->input('periode');
        $tahun        = $request->input('tahun'); // Backward compat
        $startCustom  = $request->input('start');
        $endCustom    = $request->input('end');

        [$rangeStart, $rangeEnd, $periodLabel] = $this->resolvePeriod($periode, $tahun, $startCustom, $endCustom);

        // === 2. Filter Cabang & Channel ===
        $selectedCabangIds = array_values(array_filter(array_map('intval', (array) $request->input('master_cabang_id', []))));
        $selectedChannels  = array_values(array_filter((array) $request->input('channel', []), fn($v) => is_string($v) && trim($v) !== ''));

        // === 3. Base Query ===
        $baseQuery = Jurnal::query();
        if ($rangeStart) $baseQuery->where('tgl_transaksi', '>=', $rangeStart);
        if ($rangeEnd)   $baseQuery->where('tgl_transaksi', '<=', $rangeEnd);
        if (!empty($selectedCabangIds)) {
            $baseQuery->whereIn('master_cabang_id', $selectedCabangIds);
        }
        if (!empty($selectedChannels)) {
            $baseQuery->whereHas('masterTransaksi', function ($q) use ($selectedChannels) {
                $q->whereIn('channel', $selectedChannels);
            });
        }

        // === 4. KPI Inti ===
        $totalKasus   = (clone $baseQuery)->count();
        $totalNominal = (float) (clone $baseQuery)->sum('nominal_transaksi');

        // === 5. Agregasi Mesin ATM (existing behavior) ===
        $atmQuery = (clone $baseQuery)
            ->whereNotNull('terminal_transaksi')
            ->where('terminal_transaksi', '!=', '')
            ->where('terminal_transaksi', '!=', '-');

        $atmGrouped = (clone $atmQuery)
            ->select('terminal_transaksi', DB::raw('COUNT(*) as total_keluhan'), DB::raw('SUM(nominal_transaksi) as total_nominal'))
            ->groupBy('terminal_transaksi')
            ->orderByDesc('total_keluhan')
            ->orderByDesc('total_nominal')
            ->get();

        $totalMesin = $atmGrouped->count();
        $topAtm     = $atmGrouped->first();
        $topAtms    = $atmGrouped->take(10);

        // === 6. Ringkasan Status ===
        $statusStats = [
            'menunggu' => (clone $baseQuery)->where('status', 'Menunggu')->count(),
            'success'  => (clone $baseQuery)->where('status', 'Success')->count(),
            'done'     => (clone $baseQuery)->where('status', 'Done')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'Rejected')->count(),
        ];

        // === 7. Tingkat Penyelesaian = (Done + Success) / Total × 100 ===
        // Status kustom yang diketik manual sengaja tidak dihitung tuntas, sejalan
        // dengan kartu ringkasan di halaman Data Keluhan.
        $resolvedCount  = $statusStats['done'] + $statusStats['success'];
        $resolutionRate = $totalKasus > 0 ? round(($resolvedCount / $totalKasus) * 100, 1) : 0.0;

        // === 8. Rata-rata Lama Penyelesaian (hari) — hitung di PHP agar DB-agnostic ===
        $resolvedRecords = (clone $baseQuery)
            ->whereNotNull('tgl_selesai')
            ->whereNotNull('tgl_terima')
            ->select('tgl_selesai', 'tgl_terima')
            ->get();

        $avgResolutionDays = 0.0;
        if ($resolvedRecords->isNotEmpty()) {
            $totalDays = 0;
            foreach ($resolvedRecords as $r) {
                $totalDays += Carbon::parse($r->tgl_terima)->diffInDays(Carbon::parse($r->tgl_selesai));
            }
            $avgResolutionDays = round($totalDays / $resolvedRecords->count(), 1);
        }

        // === 9. Monthly Trend (12 bulan terakhir) ===
        $trendAnchor = $rangeEnd ? Carbon::parse($rangeEnd) : Carbon::now();
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $mStart = $trendAnchor->copy()->subMonths($i)->startOfMonth()->format('Y-m-d');
            $mEnd   = $trendAnchor->copy()->subMonths($i)->endOfMonth()->format('Y-m-d');
            $label  = $trendAnchor->copy()->subMonths($i)->locale('id')->translatedFormat('M Y');

            $q = Jurnal::query()->whereBetween('tgl_transaksi', [$mStart, $mEnd]);
            if (!empty($selectedCabangIds)) $q->whereIn('master_cabang_id', $selectedCabangIds);
            if (!empty($selectedChannels)) {
                $q->whereHas('masterTransaksi', function ($qq) use ($selectedChannels) {
                    $qq->whereIn('channel', $selectedChannels);
                });
            }

            $monthlyTrend[] = [
                'month'   => $mStart,
                'label'   => $label,
                'count'   => (int) (clone $q)->count(),
                'nominal' => (float) (clone $q)->sum('nominal_transaksi'),
            ];
        }

        // === 10. Channel Breakdown ===
        $channelBreakdown = (clone $baseQuery)
            ->join('master_transaksis', 'jurnals.master_transaksi_id', '=', 'master_transaksis.id')
            ->select(
                'master_transaksis.channel',
                DB::raw('COUNT(*) as total_kasus'),
                DB::raw('SUM(jurnals.nominal_transaksi) as total_nominal')
            )
            ->groupBy('master_transaksis.channel')
            ->orderByDesc('total_kasus')
            ->get()
            ->map(function ($row) {
                return [
                    'channel' => $row->channel ?: '(Tanpa Channel)',
                    'count'   => (int) $row->total_kasus,
                    'nominal' => (float) $row->total_nominal,
                ];
            })
            ->values();

        // === 11. Top 10 Cabang Bermasalah ===
        $branchRanking = (clone $baseQuery)
            ->join('master_cabangs', 'jurnals.master_cabang_id', '=', 'master_cabangs.id')
            ->select(
                'master_cabangs.id',
                'master_cabangs.kode_cabang',
                'master_cabangs.nama_cabang',
                DB::raw('COUNT(*) as total_kasus'),
                DB::raw('SUM(jurnals.nominal_transaksi) as total_nominal')
            )
            ->groupBy('master_cabangs.id', 'master_cabangs.kode_cabang', 'master_cabangs.nama_cabang')
            ->orderByDesc('total_kasus')
            ->limit(10)
            ->get();

        // === 12. Top 5 Jenis Gangguan / Transaksi ===
        $topIssues = (clone $baseQuery)
            ->join('master_transaksis', 'jurnals.master_transaksi_id', '=', 'master_transaksis.id')
            ->select(
                'master_transaksis.jenis_transaksi',
                'master_transaksis.channel',
                DB::raw('COUNT(*) as total_kasus'),
                DB::raw('SUM(jurnals.nominal_transaksi) as total_nominal')
            )
            ->groupBy('master_transaksis.id', 'master_transaksis.jenis_transaksi', 'master_transaksis.channel')
            ->orderByDesc('total_kasus')
            ->limit(5)
            ->get();

        // === 13. Riwayat Keluhan Terbaru ===
        $recentJurnals = (clone $baseQuery)
            ->with(['masterCabang', 'masterTransaksi'])
            ->latest('id')
            ->limit(5)
            ->get();

        // === 14. Backlog Perlu Tindak Lanjut (Menunggu > 7 hari) ===
        $backlogThreshold = Carbon::now()->subDays(7)->format('Y-m-d');
        $pendingQ = Jurnal::with(['masterCabang', 'masterTransaksi'])
            ->where('status', 'Menunggu')
            ->whereNotNull('tgl_terima')
            ->where('tgl_terima', '<=', $backlogThreshold);

        if (!empty($selectedCabangIds)) {
            $pendingQ->whereIn('master_cabang_id', $selectedCabangIds);
        }
        if (!empty($selectedChannels)) {
            $pendingQ->whereHas('masterTransaksi', function ($q) use ($selectedChannels) {
                $q->whereIn('channel', $selectedChannels);
            });
        }

        $pendingOverdue = $pendingQ
            ->orderBy('tgl_terima', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($j) {
                $j->days_pending = (int) Carbon::parse($j->tgl_terima)->diffInDays(Carbon::now());
                return $j;
            });

        // === 15. Sparkline Data (30 hari terakhir) ===
        $sparklineAnchor  = $rangeEnd ? Carbon::parse($rangeEnd) : Carbon::now();
        $sparklineKasus   = [];
        $sparklineNominal = [];
        $sparklineLabels  = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $sparklineAnchor->copy()->subDays($i)->format('Y-m-d');
            $qDay = Jurnal::query()->whereDate('tgl_transaksi', $day);
            if (!empty($selectedCabangIds)) $qDay->whereIn('master_cabang_id', $selectedCabangIds);
            if (!empty($selectedChannels)) {
                $qDay->whereHas('masterTransaksi', function ($qq) use ($selectedChannels) {
                    $qq->whereIn('channel', $selectedChannels);
                });
            }
            $sparklineKasus[]   = (int) (clone $qDay)->count();
            $sparklineNominal[] = (float) (clone $qDay)->sum('nominal_transaksi');
            $sparklineLabels[]  = $day;
        }

        // === 16. Perbandingan Periode Sebelumnya (delta KPI) ===
        $prevKasus = 0; $prevNominal = 0.0; $prevResolutionRate = 0.0;
        if ($rangeStart && $rangeEnd) {
            $rs        = Carbon::parse($rangeStart);
            $re        = Carbon::parse($rangeEnd);
            $daysDiff  = (int) $rs->diffInDays($re);
            $prevEnd   = $rs->copy()->subDay()->format('Y-m-d');
            $prevStart = $rs->copy()->subDay()->subDays($daysDiff)->format('Y-m-d');

            $prevQ = Jurnal::query()->whereBetween('tgl_transaksi', [$prevStart, $prevEnd]);
            if (!empty($selectedCabangIds)) $prevQ->whereIn('master_cabang_id', $selectedCabangIds);
            if (!empty($selectedChannels)) {
                $prevQ->whereHas('masterTransaksi', function ($qq) use ($selectedChannels) {
                    $qq->whereIn('channel', $selectedChannels);
                });
            }
            $prevKasus         = (clone $prevQ)->count();
            $prevNominal       = (float) (clone $prevQ)->sum('nominal_transaksi');
            $prevResolvedCount = (clone $prevQ)->whereIn('status', ['Done', 'Success'])->count();
            $prevResolutionRate = $prevKasus > 0 ? round(($prevResolvedCount / $prevKasus) * 100, 1) : 0.0;
        }

        $previousPeriodStats = [
            'kasus'            => $prevKasus,
            'nominal'          => $prevNominal,
            'resolutionRate'   => $prevResolutionRate,
            'delta_kasus'      => $prevKasus > 0     ? round((($totalKasus   - $prevKasus)   / $prevKasus)   * 100, 1) : null,
            'delta_nominal'    => $prevNominal > 0   ? round((($totalNominal - $prevNominal) / $prevNominal) * 100, 1) : null,
            'delta_resolution' => $prevResolutionRate > 0 ? round($resolutionRate - $prevResolutionRate, 1) : null,
        ];

        // === 17. Master Data untuk Filter Dropdown ===
        $totalCabang = MasterCabang::count();
        $allCabang   = MasterCabang::orderBy('kode_cabang')->get(['id', 'kode_cabang', 'nama_cabang']);
        $allChannels = MasterTransaksi::whereNotNull('channel')
            ->where('channel', '!=', '')
            ->select('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->toArray();

        // === 18. Available Years (Backward compat) ===
        $yearsFromDb = Jurnal::selectRaw('YEAR(tgl_transaksi) as yr')
            ->whereNotNull('tgl_transaksi')
            ->groupBy('yr')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->toArray();

        $currentYear = (int) date('Y');
        $validYears = array_filter(array_unique(array_merge([$currentYear, 2024, 2025, 2026], $yearsFromDb)), function ($y) {
            return is_numeric($y) && $y >= 2020 && $y <= 2030;
        });
        rsort($validYears);
        $availableYears = array_values($validYears);
        $selectedYear   = $tahun ? (int) $tahun : null;

        // === 19. Filter Count untuk badge indikator ===
        $activeFilterCount = 0;
        if (!empty($periode) && $periode !== 'semua') $activeFilterCount++;
        if (!empty($selectedCabangIds))              $activeFilterCount++;
        if (!empty($selectedChannels))               $activeFilterCount++;

        return view('dashboard', compact(
            // Existing keys (backward compat)
            'totalKasus', 'totalMesin', 'topAtm', 'totalNominal',
            'statusStats', 'topAtms', 'topIssues', 'recentJurnals',
            'totalCabang', 'availableYears', 'selectedYear',
            // New keys
            'monthlyTrend', 'channelBreakdown', 'branchRanking',
            'avgResolutionDays', 'resolutionRate', 'previousPeriodStats',
            'pendingOverdue', 'sparklineKasus', 'sparklineNominal', 'sparklineLabels',
            'periodLabel', 'allCabang', 'allChannels',
            'periode', 'selectedCabangIds', 'selectedChannels',
            'rangeStart', 'rangeEnd', 'activeFilterCount', 'resolvedCount'
        ));
    }

    /**
     * Menerjemahkan preset periode menjadi rentang tanggal.
     *
     * @return array{0: string|null, 1: string|null, 2: string} [start, end, label]
     */
    private function resolvePeriod(?string $periode, ?string $tahun, ?string $startCustom, ?string $endCustom): array
    {
        $now = Carbon::now();

        // Backward compat: parameter ?tahun= tanpa ?periode= → filter tahun
        if (empty($periode) && !empty($tahun)) {
            $y = (int) $tahun;
            return [
                Carbon::create($y, 1, 1)->startOfYear()->format('Y-m-d'),
                Carbon::create($y, 12, 31)->endOfYear()->format('Y-m-d'),
                'Tahun ' . $y,
            ];
        }

        // Format periode "tahun_2026", "tahun_2025", dsb.
        if (!empty($periode) && preg_match('/^tahun_(\d{4})$/', $periode, $matches)) {
            $y = (int) $matches[1];
            return [
                Carbon::create($y, 1, 1)->startOfYear()->format('Y-m-d'),
                Carbon::create($y, 12, 31)->endOfYear()->format('Y-m-d'),
                'Tahun ' . $y,
            ];
        }

        return match ($periode) {
            'hari_ini' => [
                $now->copy()->format('Y-m-d'),
                $now->copy()->format('Y-m-d'),
                'Hari Ini (' . $now->locale('id')->translatedFormat('d M Y') . ')',
            ],
            'minggu_ini' => [
                $now->copy()->startOfWeek()->format('Y-m-d'),
                $now->copy()->endOfWeek()->format('Y-m-d'),
                'Minggu Ini',
            ],
            'bulan_ini' => [
                $now->copy()->startOfMonth()->format('Y-m-d'),
                $now->copy()->endOfMonth()->format('Y-m-d'),
                $now->locale('id')->translatedFormat('F Y'),
            ],
            'bulan_lalu' => [
                $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
                $now->copy()->subMonth()->locale('id')->translatedFormat('F Y'),
            ],
            '3_bulan' => [
                $now->copy()->subMonths(2)->startOfMonth()->format('Y-m-d'),
                $now->copy()->endOfMonth()->format('Y-m-d'),
                '3 Bulan Terakhir',
            ],
            'tahun_ini' => [
                $now->copy()->startOfYear()->format('Y-m-d'),
                $now->copy()->endOfYear()->format('Y-m-d'),
                'Tahun Ini (' . $now->year . ')',
            ],
            'tahun_lalu' => [
                $now->copy()->subYear()->startOfYear()->format('Y-m-d'),
                $now->copy()->subYear()->endOfYear()->format('Y-m-d'),
                'Tahun Lalu (' . $now->copy()->subYear()->year . ')',
            ],
            'custom' => ($startCustom && $endCustom)
                ? [
                    $startCustom,
                    $endCustom,
                    'Kustom: ' . Carbon::parse($startCustom)->locale('id')->translatedFormat('d M Y')
                        . ' — ' . Carbon::parse($endCustom)->locale('id')->translatedFormat('d M Y'),
                ]
                : [null, null, 'Semua Periode'],
            default => [null, null, 'Semua Periode'],
        };
    }
}
