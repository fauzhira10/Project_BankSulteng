<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\MasterCabang;
use App\Models\MasterTransaksi;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    /**
     * Definisi struktur 8 Kelompok Kategori Laporan sesuai standar Bank Sulteng
     */
    private function getReportStructure()
    {
        return [
            // 1. MESIN ATM BANK SULTENG (Group 1)
            '1_HEADER_ATM_SULTENG'        => ['no' => '1', 'sub' => '',  'group' => 'MESIN ATM BANK SULTENG', 'label' => 'MESIN ATM BANK SULTENG', 'is_header' => true],
            '1_A_ATM_SULTENG_TARIK_TUNAI' => ['no' => '',  'sub' => 'A', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'TARIK TUNAI'],
            '1_B_ATM_SULTENG_SETOR_TUNAI' => ['no' => '',  'sub' => 'B', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'SETOR TUNAI'],
            '1_C_ATM_SULTENG_TRANSFER'    => ['no' => '',  'sub' => 'C', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'TRANSFER'],
            '1_D_ATM_SULTENG_PULSA'       => ['no' => '',  'sub' => 'D', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'PULSA'],
            '1_E_ATM_SULTENG_PLN'         => ['no' => '',  'sub' => 'E', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'PLN'],
            '1_F_ATM_SULTENG_PEMBAYARAN'  => ['no' => '',  'sub' => 'F', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'PEMBAYARAN'],
            '1_G_ATM_SULTENG_CCTV'        => ['no' => '',  'sub' => 'G', 'group' => 'MESIN ATM BANK SULTENG', 'label' => 'CCTV'],
            
            // 2 s/d 5. Direct Categories
            '2_EDC'                       => ['no' => '2', 'sub' => '',  'group' => 'EDC', 'label' => 'EDC', 'single' => true],
            '3_LAKU_PANDAI'               => ['no' => '3', 'sub' => '',  'group' => 'LAKU PANDAI (TAMASYA)', 'label' => 'LAKU PANDAI (TAMASYA)', 'single' => true],
            '4_SMS_BANKING'               => ['no' => '4', 'sub' => '',  'group' => 'SMS BANKING', 'label' => 'SMS BANKING', 'single' => true],
            '5_MOBILE_BANKING'            => ['no' => '5', 'sub' => '',  'group' => 'MOBILE BANKING', 'label' => 'MOBILE BANKING', 'single' => true],
            
            // 6. MESIN ATM BANK LAIN (Group 6)
            '6_HEADER_ATM_LAIN'           => ['no' => '6', 'sub' => '',  'group' => 'MESIN ATM BANK LAIN', 'label' => 'MESIN ATM BANK LAIN', 'is_header' => true, 'theme' => 'blue'],
            '6_A_ATM_LAIN_TARIK_TUNAI'    => ['no' => '',  'sub' => 'A', 'group' => 'MESIN ATM BANK LAIN', 'label' => 'TARIK TUNAI', 'theme' => 'blue'],
            '6_B_ATM_LAIN_TRANSFER'       => ['no' => '',  'sub' => 'B', 'group' => 'MESIN ATM BANK LAIN', 'label' => 'TRANSFER', 'theme' => 'blue'],
            
            // 7 & 8. Other Channels
            '7_EDC_BANK_LAIN'             => ['no' => '7', 'sub' => '',  'group' => 'EDC BANK LAIN', 'label' => 'EDC BANK LAIN', 'single' => true, 'theme' => 'blue'],
            '8_QRIS'                      => ['no' => '8', 'sub' => '',  'group' => 'QRIS', 'label' => 'QRIS', 'single' => true, 'theme' => 'blue'],
        ];
    }

    /**
     * Helper pemetaan jenis transaksi & channel ke kode kategori laporan
     */
    private function mapCategoryKey($jenis, $channel)
    {
        $j = strtoupper(trim((string)$jenis));
        $c = strtoupper(trim((string)$channel));

        // 8. QRIS
        if (str_contains($j, 'QRIS') || str_contains($c, 'QRIS')) {
            return '8_QRIS';
        }

        // 7. EDC BANK LAIN
        if (str_contains($j, 'EDC BANK LAIN') || str_contains($c, 'EDC BANK LAIN')) {
            return '7_EDC_BANK_LAIN';
        }

        // 4. SMS BANKING
        if (str_contains($c, 'SMS') || str_contains($j, 'SMS')) {
            return '4_SMS_BANKING';
        }

        // 5. MOBILE BANKING
        if (str_contains($c, 'MOBILE') || str_contains($j, 'MBANKING') || str_contains($j, 'MOBILE')) {
            return '5_MOBILE_BANKING';
        }

        // 3. LAKU PANDAI
        if (str_contains($c, 'LAKU PANDAI') || str_contains($j, 'LAKU PANDAI') || str_contains($j, 'TAMASYA')) {
            return '3_LAKU_PANDAI';
        }

        // 2. EDC (Debit)
        if ($c === 'DEBIT' || $j === 'EDC' || (str_contains($j, 'EDC') && !str_contains($j, 'BANK LAIN'))) {
            return '2_EDC';
        }

        // 6. MESIN ATM BANK LAIN
        if (str_contains($c, 'ATM BERSAMA') || str_contains($c, 'ATM LINK') || str_contains($j, 'DI BANK LAIN') || str_contains($j, 'MESIN BANK LAIN')) {
            if (str_contains($j, 'TARIK TUNAI') || str_contains($j, 'TARIK')) {
                return '6_A_ATM_LAIN_TARIK_TUNAI';
            }
            if (str_contains($j, 'TRANSFER')) {
                return '6_B_ATM_LAIN_TRANSFER';
            }
            return '6_A_ATM_LAIN_TARIK_TUNAI';
        }

        // 1. MESIN ATM BANK SULTENG
        if (str_contains($j, 'CCTV')) {
            return '1_G_ATM_SULTENG_CCTV';
        }
        if (str_contains($j, 'SETOR TUNAI') || str_contains($j, 'CRM')) {
            return '1_B_ATM_SULTENG_SETOR_TUNAI';
        }
        if (str_contains($j, 'TARIK TUNAI') || str_contains($j, 'TARIK')) {
            return '1_A_ATM_SULTENG_TARIK_TUNAI';
        }
        if (str_contains($j, 'TRANSFER')) {
            return '1_C_ATM_SULTENG_TRANSFER';
        }
        if (str_contains($j, 'PLN')) {
            return '1_E_ATM_SULTENG_PLN';
        }
        if (str_contains($j, 'PULSA') || str_contains($j, 'TSEL') || str_contains($j, 'XL')) {
            return '1_D_ATM_SULTENG_PULSA';
        }
        if (str_contains($j, 'TELKOM') || str_contains($j, 'PEMBAYARAN') || str_contains($j, 'PEMBELIAN') || str_contains($j, 'BPJS') || str_contains($j, 'HALO') || str_contains($j, 'DANA') || str_contains($j, 'GOPAY') || $c === 'FINNET') {
            return '1_F_ATM_SULTENG_PEMBAYARAN';
        }

        return '1_A_ATM_SULTENG_TARIK_TUNAI';
    }

    /**
     * Halaman Utama Rekapitulasi Laporan Penyelesaian Keluhan Nasabah
     */
    public function index(Request $request)
    {
        // 1. Deteksi Daftar Tahun yang tersedia di Database
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
        $selectedMonth = (int)$request->input('bulan', date('n')); // 1..12
        $selectedCabang = $request->input('master_cabang_id');
        $selectedStatus = $request->input('status');

        // 2. Siapkan Struktur Matriks 12 Bulan
        $structure = $this->getReportStructure();
        
        $matrix = [];
        $rowTotals = [];
        foreach ($structure as $key => $meta) {
            $matrix[$key] = array_fill(1, 12, 0);
            $rowTotals[$key] = 0;
        }

        $colTotals = array_fill(1, 12, 0);
        $grandTotal = 0;

        // 3. Query Data Transaksi pada Tahun Terpilih
        $query = Jurnal::with(['masterCabang', 'masterTransaksi'])
            ->whereYear('tgl_transaksi', $selectedYear);

        if (!empty($selectedCabang)) {
            $query->where('master_cabang_id', $selectedCabang);
        }

        if (!empty($selectedStatus)) {
            $query->where('status', $selectedStatus);
        }

        $jurnals = $query->get();

        // 4. Hitung Agregasi Matriks 12 Bulan
        foreach ($jurnals as $j) {
            $month = (int)date('n', strtotime($j->tgl_transaksi));
            if ($month < 1 || $month > 12) continue;

            $jenis = $j->masterTransaksi->jenis_transaksi ?? '';
            $channel = $j->masterTransaksi->channel ?? '';
            $catKey = $this->mapCategoryKey($jenis, $channel);

            if (isset($matrix[$catKey])) {
                $matrix[$catKey][$month]++;
                $rowTotals[$catKey]++;
                $colTotals[$month]++;
                $grandTotal++;
            }
        }

        // 5. Hitung Ringkasan & Drill-down untuk Bulan Aktif
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $activeMonthName = $monthNames[$selectedMonth] ?? 'Semua Bulan';
        $activeMonthTotal = $colTotals[$selectedMonth] ?? 0;

        // Rincian Kategori Terbanyak di Bulan Terpilih
        $monthCategoryRank = [];
        foreach ($structure as $key => $meta) {
            if ($meta['is_header'] ?? false) continue;
            $count = $matrix[$key][$selectedMonth] ?? 0;
            if ($count > 0) {
                $lbl = (!empty($meta['sub'])) ? "{$meta['group']} ({$meta['label']})" : $meta['label'];
                $monthCategoryRank[] = [
                    'label' => $lbl,
                    'count' => $count,
                    'percentage' => $activeMonthTotal > 0 ? round(($count / $activeMonthTotal) * 100, 1) : 0
                ];
            }
        }
        usort($monthCategoryRank, fn($a, $b) => $b['count'] <=> $a['count']);

        // Data Master untuk Filter
        $cabangs = MasterCabang::orderBy('kode_cabang')->get();

        return view('laporan_rekap', compact(
            'availableYears',
            'selectedYear',
            'selectedMonth',
            'selectedCabang',
            'selectedStatus',
            'structure',
            'matrix',
            'rowTotals',
            'colTotals',
            'grandTotal',
            'monthNames',
            'activeMonthName',
            'activeMonthTotal',
            'monthCategoryRank',
            'cabangs'
        ));
    }

    /**
     * Ekspor Laporan Rekapitulasi Tahunan ke Format Excel (.xlsx) Identik
     */
    public function exportExcel(Request $request)
    {
        $selectedYear = (int)$request->input('tahun', date('Y'));
        $selectedCabang = $request->input('master_cabang_id');
        $selectedStatus = $request->input('status');

        $structure = $this->getReportStructure();
        $matrix = [];
        $rowTotals = [];
        foreach ($structure as $key => $meta) {
            $matrix[$key] = array_fill(1, 12, 0);
            $rowTotals[$key] = 0;
        }
        $colTotals = array_fill(1, 12, 0);
        $grandTotal = 0;

        $query = Jurnal::with(['masterCabang', 'masterTransaksi'])
            ->whereYear('tgl_transaksi', $selectedYear);

        if (!empty($selectedCabang)) {
            $query->where('master_cabang_id', $selectedCabang);
        }
        if (!empty($selectedStatus)) {
            $query->where('status', $selectedStatus);
        }

        $jurnals = $query->get();
        foreach ($jurnals as $j) {
            $month = (int)date('n', strtotime($j->tgl_transaksi));
            if ($month < 1 || $month > 12) continue;

            $jenis = $j->masterTransaksi->jenis_transaksi ?? '';
            $channel = $j->masterTransaksi->channel ?? '';
            $catKey = $this->mapCategoryKey($jenis, $channel);

            if (isset($matrix[$catKey])) {
                $matrix[$catKey][$month]++;
                $rowTotals[$catKey]++;
                $colTotals[$month]++;
                $grandTotal++;
            }
        }

        $cabangModel = !empty($selectedCabang) ? MasterCabang::find($selectedCabang) : null;
        $cabangText = $cabangModel ? " - KANTOR CABANG " . strtoupper($cabangModel->nama_cabang) : " - SELURUH CABANG";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('REKAP_KELUHAN_' . $selectedYear);

        // Styling Palette
        $greenHeaderBg = 'D9EAD3'; // Lembut hijau
        $greenSubBg    = 'EAF2EC';
        $blueHeaderBg  = '00A2E8'; // Biru khas ATM Bank Lain di template
        $blueSubBg     = 'D9EDF7';
        $peachTitleBg  = 'FCE4D6'; // Warna peach judul

        // 1. JUDUL UTAMA
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', "LAPORAN PENYELESAIAN KELUHAN NASABAH TAHUN {$selectedYear}{$cabangText}");
        $sheet->getStyle('A2:P2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $peachTitleBg]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // 2. HEADER TABEL
        $sheet->setCellValue('A4', 'NO');
        $sheet->mergeCells('A4:A5');
        $sheet->setCellValue('B4', '');
        $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('C4', 'JENIS KLAIM');
        $sheet->mergeCells('C4:C5');

        $monthHeaders = [
            'D' => 'JANUARI', 'E' => 'FEBRUARI', 'F' => 'MARET', 'G' => 'APRIL',
            'H' => 'MEI', 'I' => 'JUNI', 'J' => 'JULI', 'K' => 'AGUSTUS',
            'L' => 'SEPTEMBER', 'M' => 'OKTOBER', 'N' => 'NOVEMBER', 'O' => 'DESEMBER'
        ];

        foreach ($monthHeaders as $col => $mName) {
            $sheet->setCellValue("{$col}4", $mName);
            $sheet->mergeCells("{$col}4:{$col}5");
        }
        $sheet->setCellValue('P4', 'TOTAL');
        $sheet->mergeCells('P4:P5');

        $sheet->getStyle('A4:P5')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9.5],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(18);

        // 3. BARIS DATA KATEGORI
        $row = 6;
        foreach ($structure as $key => $meta) {
            $isHeader = $meta['is_header'] ?? false;
            $isSingle = $meta['single'] ?? false;

            $sheet->setCellValue("A{$row}", $meta['no'] ?? '');
            $sheet->setCellValue("B{$row}", $meta['sub'] ?? '');

            if ($isHeader || $isSingle) {
                $sheet->setCellValue("C{$row}", $meta['label']);
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            } else {
                $sheet->setCellValue("C{$row}", '   ' . $meta['label']);
            }

            // Fill background colors according to Bank Sulteng standard template
            if ($isHeader) {
                $color = ($meta['theme'] ?? '') === 'blue' ? $blueHeaderBg : $greenHeaderBg;
                $sheet->getStyle("A{$row}:P{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                $sheet->getStyle("A{$row}:P{$row}")->getFont()->setBold(true);
            } elseif ($isSingle) {
                $color = ($meta['theme'] ?? '') === 'blue' ? $blueSubBg : $greenSubBg;
                $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
            }

            // Month values
            $mIdx = 1;
            foreach ($monthHeaders as $col => $mName) {
                if ($isHeader) {
                    $sheet->setCellValue("{$col}{$row}", '-');
                } else {
                    $val = $matrix[$key][$mIdx] ?? 0;
                    $sheet->setCellValue("{$col}{$row}", $val > 0 ? $val : '-');
                }
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $mIdx++;
            }

            // Total per row
            if ($isHeader) {
                $sheet->setCellValue("P{$row}", '-');
            } else {
                $rTot = $rowTotals[$key] ?? 0;
                $sheet->setCellValue("P{$row}", $rTot > 0 ? $rTot : '-');
            }
            $sheet->getStyle("P{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("P{$row}")->getFont()->setBold(true);

            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        // 4. BARIS TOTAL KLAIM
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL KLAIM');
        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $mIdx = 1;
        foreach ($monthHeaders as $col => $mName) {
            $cTot = $colTotals[$mIdx] ?? 0;
            $sheet->setCellValue("{$col}{$row}", $cTot > 0 ? $cTot : '-');
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $mIdx++;
        }

        $sheet->setCellValue("P{$row}", $grandTotal > 0 ? $grandTotal : '-');
        $sheet->getStyle("P{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10.5],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE599']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        // 5. BORDERS & AUTO WIDTH
        $sheet->getStyle("A4:P{$row}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']],
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(4);
        $sheet->getColumnDimension('C')->setWidth(36);
        foreach ($monthHeaders as $col => $mName) {
            $sheet->getColumnDimension($col)->setWidth(11);
        }
        $sheet->getColumnDimension('P')->setWidth(13);

        $fileName = 'Laporan_Rekapitulasi_Keluhan_BankSulteng_' . $selectedYear . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            if (ob_get_length()) ob_end_clean();
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
