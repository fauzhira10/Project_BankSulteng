<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\MasterAtm;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AtmMonitoringController extends Controller
{
    /**
     * Dashboard & Rekapitulasi Keluhan Mesin ATM
     */
    public function index(Request $request)
    {
        // 1. Deteksi Tahun yang Tersedia di Database
        $yearsFromDb = Jurnal::selectRaw('YEAR(tgl_transaksi) as yr')
            ->whereNotNull('tgl_transaksi')
            ->groupBy('yr')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->toArray();

        $currentYear = (int)date('Y');
        $validYears = array_filter(array_unique(array_merge([$currentYear, 2024, 2025, 2026], $yearsFromDb)), function($y) {
            return is_numeric($y) && $y >= 2020 && $y <= 2030;
        });
        rsort($validYears);
        $availableYears = array_values($validYears);

        $selectedYear = (int)$request->input('tahun', $availableYears[0] ?? $currentYear);
        $selectedMonth = $request->filled('bulan') ? (int)$request->input('bulan') : null;
        $selectedCabang = $request->input('master_cabang_id');
        $searchKeyword = trim((string)$request->input('q'));

        // 2. Base Query Data Keluhan Berdasarkan Filter
        $baseQuery = Jurnal::with(['masterCabang', 'masterTransaksi'])
            ->whereNotNull('terminal_transaksi')
            ->where('terminal_transaksi', '!=', '')
            ->where('terminal_transaksi', '!=', '-')
            ->whereYear('tgl_transaksi', $selectedYear);

        if (!empty($selectedMonth)) {
            $baseQuery->whereMonth('tgl_transaksi', $selectedMonth);
        }

        if (!empty($selectedCabang)) {
            $baseQuery->where('master_cabang_id', $selectedCabang);
        }

        if (!empty($searchKeyword)) {
            $baseQuery->where(function ($q) use ($searchKeyword) {
                $cleanKeyword = strtoupper(trim($searchKeyword));

                // 1. Channel non-mesin (Bank Lain, Mobile Banking)
                if (str_contains($cleanKeyword, 'BANK LAIN') || str_contains($cleanKeyword, 'MOBILE') || str_contains($cleanKeyword, 'SMS')) {
                    $q->where('terminal_transaksi', 'LIKE', "%{$searchKeyword}%");
                } elseif (str_contains($cleanKeyword, '-')) {
                    // 2. Format gabungan "ID - PROFIL" (misal: "452 - GRG.KCU1")
                    $parts = explode('-', $cleanKeyword, 2);
                    $idPart = trim($parts[0] ?? '');
                    $profilPart = trim($parts[1] ?? '');

                    $q->where('terminal_transaksi', '=', $cleanKeyword)
                      ->orWhere('terminal_transaksi', '=', $profilPart)
                      ->orWhere('terminal_transaksi', '=', "{$idPart} - {$profilPart}")
                      ->orWhere('terminal_transaksi', 'LIKE', "% - {$profilPart}")
                      ->orWhere('terminal_transaksi', 'LIKE', "{$profilPart} %");
                } elseif (preg_match('/^[A-Z0-9]+\.[A-Z0-9]+$/i', $cleanKeyword)) {
                    // 3. Nama profil mesin spesifik (misal: GRG.KCU1)
                    $q->where('terminal_transaksi', '=', $cleanKeyword)
                      ->orWhere('terminal_transaksi', 'LIKE', "% - {$cleanKeyword}")
                      ->orWhere('terminal_transaksi', 'LIKE', "{$cleanKeyword} %");
                } else {
                    // 4. Cek ke master ATM jika mencari ID LUNO
                    $atmMatch = MasterAtm::findAtmInfo($cleanKeyword);
                    if ($atmMatch && !empty($atmMatch['profil'])) {
                        $prof = $atmMatch['profil'];
                        $q->where('terminal_transaksi', '=', $prof)
                          ->orWhere('terminal_transaksi', 'LIKE', "% - {$prof}")
                          ->orWhere('terminal_transaksi', 'LIKE', "{$prof} %");
                    } else {
                        $q->where('terminal_transaksi', 'LIKE', "%{$searchKeyword}%");
                    }
                }

                $q->orWhereHas('masterCabang', function ($cQ) use ($searchKeyword) {
                    $cQ->where('nama_cabang', 'LIKE', "%{$searchKeyword}%")
                       ->orWhere('kode_cabang', 'LIKE', "%{$searchKeyword}%");
                });
            });
        }

        // 3. Agregasi Data per Mesin ATM / Terminal dengan Deteksi Multi-Cabang
        $allJurnals = $baseQuery->get();

        $grouped = [];
        $totalKasus = 0;
        $totalNominal = 0;

        foreach ($allJurnals as $j) {
            $termKey = strtoupper(trim($j->terminal_transaksi));
            $cabangId = $j->master_cabang_id;
            $cabangNama = $j->masterCabang->nama_cabang ?? 'Kantor Cabang';
            $cabangKode = $j->masterCabang->kode_cabang ?? '-';
            $jenisTransaksi = $j->masterTransaksi->jenis_transaksi ?? 'Transaksi ATM';

            if (!isset($grouped[$termKey])) {
                $grouped[$termKey] = [
                    'terminal'         => $termKey,
                    'master_cabang_id' => $cabangId,
                    'cabang_nama'      => $cabangNama,
                    'cabang_kode'      => $cabangKode,
                    'cabangs'          => [],
                    'total_keluhan'    => 0,
                    'total_nominal'    => 0,
                    'jenis_counts'     => [],
                ];
            }

            $grouped[$termKey]['total_keluhan']++;
            $grouped[$termKey]['total_nominal'] += (float)$j->nominal_transaksi;
            $totalKasus++;
            $totalNominal += (float)$j->nominal_transaksi;

            // Catat seluruh cabang yang melaporkan terminal ini
            if (!isset($grouped[$termKey]['cabangs'][$cabangKode])) {
                $grouped[$termKey]['cabangs'][$cabangKode] = [
                    'id'    => $cabangId,
                    'kode'  => $cabangKode,
                    'nama'  => $cabangNama,
                    'count' => 0,
                ];
            }
            $grouped[$termKey]['cabangs'][$cabangKode]['count']++;

            // Hitung frekuensi jenis transaksi di mesin ini
            if (!isset($grouped[$termKey]['jenis_counts'][$jenisTransaksi])) {
                $grouped[$termKey]['jenis_counts'][$jenisTransaksi] = 0;
            }
            $grouped[$termKey]['jenis_counts'][$jenisTransaksi]++;
        }

        // Urutkan mesin ATM berdasarkan jumlah keluhan terbanyak (DESC), lalu nominal (DESC)
        uasort($grouped, function ($a, $b) {
            if ($b['total_keluhan'] === $a['total_keluhan']) {
                return $b['total_nominal'] <=> $a['total_nominal'];
            }
            return $b['total_keluhan'] <=> $a['total_keluhan'];
        });

        // Format informasi cabang & jenis keluhan dominan untuk setiap mesin
        $allAtms = [];
        foreach ($grouped as $key => $data) {
            $branchCount = count($data['cabangs']);
            if ($branchCount > 1) {
                $data['is_multi_cabang'] = true;
                // Urutkan cabang dengan keluhan terbanyak
                uasort($data['cabangs'], fn($a, $b) => $b['count'] <=> $a['count']);
                $branchDetails = [];
                foreach ($data['cabangs'] as $cb) {
                    $branchDetails[] = "{$cb['nama']} ({$cb['count']})";
                }
                $data['cabang_nama'] = "Multi-Cabang ({$branchCount} Cabang)";
                $data['cabang_kode'] = 'MULTI';
                $data['cabang_detail'] = implode(', ', $branchDetails);
                $data['cabang_list'] = array_values($data['cabangs']);
            } else {
                $firstBranch = reset($data['cabangs']);
                $data['is_multi_cabang'] = false;
                $data['cabang_nama'] = $firstBranch['nama'] ?? $data['cabang_nama'];
                $data['cabang_kode'] = $firstBranch['kode'] ?? $data['cabang_kode'];
                $data['cabang_detail'] = $data['cabang_nama'];
                $data['cabang_list'] = array_values($data['cabangs']);
            }

            arsort($data['jenis_counts']);
            $topIssues = [];
            $i = 0;
            foreach ($data['jenis_counts'] as $jName => $jCount) {
                if ($i++ >= 2) break;
                $cleanJName = preg_replace('/^(ATM_|MBANKING_|SMS BANKING |CRM_)/', '', $jName);
                $topIssues[] = "{$cleanJName} ({$jCount}x)";
            }
            $data['dominant_issues'] = implode(', ', $topIssues);
            $data['atm_info'] = MasterAtm::findAtmInfo($data['terminal']);
            $allAtms[] = $data;
        }

        // 4. Data Master untuk Filter & Ringkasan KPI
        $cabangs = MasterCabang::orderBy('kode_cabang')->get();
        $atmsGrouped = MasterAtm::getAtmsGroupedByCabang();
        $totalMesin = count($allAtms);
        $topAtm = $allAtms[0] ?? null;

        // 5. Pagination (Pilihan: 10, 50, 100 baris)
        $perPage = (int)$request->input('per_page', 10);
        if (!in_array($perPage, [10, 50, 100])) {
            $perPage = 10;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = array_slice($allAtms, ($currentPage - 1) * $perPage, $perPage);
        $atms = new LengthAwarePaginator(
            $currentItems,
            $totalMesin,
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('atm_monitoring', compact(
            'availableYears',
            'selectedYear',
            'selectedMonth',
            'selectedCabang',
            'searchKeyword',
            'atms',
            'atmsGrouped',
            'totalMesin',
            'totalKasus',
            'totalNominal',
            'topAtm',
            'cabangs',
            'monthNames',
            'perPage'
        ));
    }
}
