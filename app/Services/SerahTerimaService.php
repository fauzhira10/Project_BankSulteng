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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

    /**
     * Mengkategorikan channel transaksi jurnal ke dalam 4 kelompok utama analitik:
     * ATM Tunai, Mobile Banking, EDC, atau QRIS.
     */
    public static function kategorikanChannel(mixed $jurnal): string
    {
        $rawChannel = '';
        $rawJenis = '';

        if (is_object($jurnal)) {
            $rawChannel = strtoupper(trim((string) (
                $jurnal->channel
                ?? $jurnal->masterTransaksi?->channel
                ?? $jurnal->pengaduan?->channel
                ?? ''
            )));
            $rawJenis = strtoupper(trim((string) (
                $jurnal->jenis_transaksi
                ?? $jurnal->masterTransaksi?->jenis_transaksi
                ?? $jurnal->pengaduan?->transaksi?->jenis_transaksi
                ?? ''
            )));
        } elseif (is_array($jurnal)) {
            $rawChannel = strtoupper(trim((string) ($jurnal['channel'] ?? $jurnal['master_transaksi']['channel'] ?? '')));
            $rawJenis = strtoupper(trim((string) ($jurnal['jenis_transaksi'] ?? $jurnal['master_transaksi']['jenis_transaksi'] ?? '')));
        }

        $gabungan = $rawChannel . ' ' . $rawJenis;

        if (str_contains($gabungan, 'QRIS')) {
            return 'QRIS';
        }

        if (
            str_contains($gabungan, 'MOBILE')
            || str_contains($gabungan, 'MBANKING')
            || str_contains($gabungan, 'M-BANKING')
            || str_contains($gabungan, 'SMS BANKING')
        ) {
            return 'Mobile Banking';
        }

        if (str_contains($gabungan, 'EDC') || str_contains($gabungan, 'DEBIT')) {
            return 'EDC';
        }

        return 'ATM Tunai';
    }

    /**
     * Menghitung rekap analitik komprehensif untuk tiket selesai:
     * 1. Volume & Nilai Pembukuan
     * 2. Kepatuhan SLA (Tepat Waktu vs Overdue)
     * 3. Distribusi Channel Transaksi (ATM Tunai, Mobile Banking, EDC, QRIS)
     *
     * @param iterable|Collection|array $jurnals
     * @return array<string, mixed>
     */
    public static function kalkulasiAnalitik($jurnals): array
    {
        $volume = self::kalkulasiTotal($jurnals);
        $totalTiket = $volume['total_tiket'];
        $totalNominal = $volume['total_nominal'];

        $tepatWaktu = 0;
        $terlambat = 0;
        $totalDurasiHari = 0;

        $channels = [
            'ATM Tunai' => ['jumlah_tiket' => 0, 'nominal' => 0.0, 'nominal_rupiah' => 'Rp 0', 'persentase' => 0.0],
            'Mobile Banking' => ['jumlah_tiket' => 0, 'nominal' => 0.0, 'nominal_rupiah' => 'Rp 0', 'persentase' => 0.0],
            'EDC' => ['jumlah_tiket' => 0, 'nominal' => 0.0, 'nominal_rupiah' => 'Rp 0', 'persentase' => 0.0],
            'QRIS' => ['jumlah_tiket' => 0, 'nominal' => 0.0, 'nominal_rupiah' => 'Rp 0', 'persentase' => 0.0],
        ];

        foreach ($jurnals as $jurnal) {
            $nominal = (float) (is_object($jurnal) ? ($jurnal->nominal_transaksi ?? 0) : ($jurnal['nominal_transaksi'] ?? 0));

            // 1. Evaluasi Kepatuhan SLA
            $tglTerima = is_object($jurnal) ? ($jurnal->tgl_terima ?? null) : ($jurnal['tgl_terima'] ?? null);
            $tglSelesai = is_object($jurnal) ? ($jurnal->tgl_selesai ?? null) : ($jurnal['tgl_selesai'] ?? null);
            $batasSla = 14;

            if (is_object($jurnal) && isset($jurnal->pengaduan) && $jurnal->pengaduan?->sla_hari) {
                $batasSla = (int) $jurnal->pengaduan->sla_hari;
            } elseif (is_array($jurnal) && isset($jurnal['sla_hari'])) {
                $batasSla = (int) $jurnal['sla_hari'];
            }

            $isOverdue = false;
            if ($tglTerima && $tglSelesai) {
                $durasi = abs(Carbon::parse($tglTerima)->startOfDay()->diffInDays(Carbon::parse($tglSelesai)->startOfDay()));
                $totalDurasiHari += $durasi;
                $isOverdue = $durasi > $batasSla;
            } elseif ($tglSelesai && is_object($jurnal) && $jurnal->pengaduan?->tgl_jatuh_tempo) {
                $isOverdue = Carbon::parse($tglSelesai)->startOfDay()->gt(
                    Carbon::parse($jurnal->pengaduan->tgl_jatuh_tempo)->startOfDay()
                );
            }

            if ($isOverdue) {
                $terlambat++;
            } else {
                $tepatWaktu++;
            }

            // 2. Evaluasi Distribusi Channel
            $kategoriChannel = self::kategorikanChannel($jurnal);
            $channels[$kategoriChannel]['jumlah_tiket']++;
            $channels[$kategoriChannel]['nominal'] += $nominal;
        }

        foreach ($channels as $nama => &$info) {
            $info['nominal_rupiah'] = 'Rp ' . number_format($info['nominal'], 0, ',', '.');
            $info['persentase'] = $totalNominal > 0
                ? round(($info['nominal'] / $totalNominal) * 100, 1)
                : 0.0;
        }
        unset($info);

        $persenTepatWaktu = $totalTiket > 0 ? round(($tepatWaktu / $totalTiket) * 100, 1) : 0.0;
        $persenTerlambat = $totalTiket > 0 ? round(($terlambat / $totalTiket) * 100, 1) : 0.0;
        $rataRataHari = $totalTiket > 0 ? round($totalDurasiHari / $totalTiket, 1) : 0.0;

        return [
            'volume' => $volume,
            'sla' => [
                'total_tiket' => $totalTiket,
                'tepat_waktu' => $tepatWaktu,
                'terlambat' => $terlambat,
                'overdue' => $terlambat,
                'persentase_tepat_waktu' => $persenTepatWaktu,
                'persentase_terlambat' => $persenTerlambat,
                'persentase_overdue' => $persenTerlambat,
                'rata_rata_hari' => $rataRataHari,
            ],
            'channels' => $channels,
        ];
    }

    /**
     * Menghasilkan konten CSV Pembukuan sesuai format standar operasional Bank Sulteng.
     *
     * @param iterable|Collection|array $jurnals
     */
    public static function generateCsvPembukuan($jurnals, ?Carbon $tanggal = null): string
    {
        $tgl = $tanggal ? $tanggal->copy() : now();
        $tanggalIndonesia = strtoupper($tgl->locale('id')->translatedFormat('d F Y'));

        $lines = [];
        $lines[] = 'SERAH TERIMA PENYELESAIAN NASABAH,,,';
        $lines[] = "PER TANGGAL {$tanggalIndonesia},,,";
        $lines[] = 'No.,Nomor Tiket,Nama,Nomor Rekening,Nominal,Keterangan';

        $no = 1;
        foreach ($jurnals as $jurnal) {
            $noTiket = is_object($jurnal) ? ($jurnal->no_tiket ?? '') : ($jurnal['no_tiket'] ?? '');
            $nama = is_object($jurnal) ? ($jurnal->nama_nasabah ?? '') : ($jurnal['nama_nasabah'] ?? '');
            $noRekening = is_object($jurnal) ? ($jurnal->no_rekening ?? '') : ($jurnal['no_rekening'] ?? '');
            $nominal = is_object($jurnal) ? ($jurnal->nominal_transaksi ?? 0) : ($jurnal['nominal_transaksi'] ?? 0);

            $noTiketClean = str_replace([',', "\r", "\n"], ' ', trim((string) $noTiket));
            $namaClean = strtoupper(str_replace([',', "\r", "\n"], ' ', trim((string) $nama)));
            $noRekeningClean = str_replace([',', "\r", "\n"], ' ', trim((string) $noRekening));
            $nominalInt = (int) round((float) $nominal);

            $lines[] = "{$no},{$noTiketClean},{$namaClean},{$noRekeningClean},{$nominalInt},CLOSED";
            $no++;
        }

        $lines[] = "MOHON UNTUK DIBUKUKAN HARI INI {$tanggalIndonesia},,,,,,";
        $lines[] = ',,,,"Yang Menerima,",,,,';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Menghasilkan berkas Spreadsheet Excel (.xlsx) resmi dengan tabel bergaris,
     * kop dokumen Bank Sulteng, format nominal Rupiah, dan kolom tanda tangan.
     *
     * @param iterable|Collection|array $jurnals
     */
    public static function generateExcelPembukuan($jurnals, ?Carbon $tanggal = null): Spreadsheet
    {
        $tgl = $tanggal ? $tanggal->copy() : now();
        $tanggalIndonesia = strtoupper($tgl->locale('id')->translatedFormat('d F Y'));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SERAH TERIMA');
        $sheet->setShowGridLines(true);

        // 1. Header Judul Dokumen (Baris 1 & 2)
        $sheet->setCellValue('A1', 'SERAH TERIMA PENYELESAIAN NASABAH');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setName('Arial')->setSize(13)->setBold(true)->getColor()->setRGB('0B2F64');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "PER TANGGAL {$tanggalIndonesia}");
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setName('Arial')->setSize(11)->setBold(true)->getColor()->setRGB('1E293B');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Header Tabel (Baris 4)
        $headers = [
            'A4' => 'No.',
            'B4' => 'Nomor Tiket',
            'C4' => 'Nama',
            'D4' => 'Nomor Rekening',
            'E4' => 'Nominal',
            'F4' => 'Keterangan',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        $headerStyle = [
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['rgb' => '0F172A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(24);

        // 3. Baris Data (Mulai Baris 5)
        $row = 5;
        $no = 1;
        $totalNominal = 0;

        foreach ($jurnals as $jurnal) {
            $noTiket = is_object($jurnal) ? ($jurnal->no_tiket ?? '') : ($jurnal['no_tiket'] ?? '');
            $nama = is_object($jurnal) ? ($jurnal->nama_nasabah ?? '') : ($jurnal['nama_nasabah'] ?? '');
            $noRekening = is_object($jurnal) ? ($jurnal->no_rekening ?? '') : ($jurnal['no_rekening'] ?? '');
            $nominal = (float) (is_object($jurnal) ? ($jurnal->nominal_transaksi ?? 0) : ($jurnal['nominal_transaksi'] ?? 0));
            $totalNominal += $nominal;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValueExplicit('B' . $row, (string) $noTiket, DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, strtoupper((string) $nama));
            $sheet->setCellValueExplicit('D' . $row, (string) $noRekening, DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $nominal);
            $sheet->setCellValue('F' . $row, 'CLOSED');

            // Format sel
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);

            $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$row}:F{$row}")->getFont()->setName('Arial')->setSize(10);
            $sheet->getRowDimension($row)->setRowHeight(20);

            $no++;
            $row++;
        }

        // 4. Baris Total (Row $row)
        $sheet->setCellValue('A' . $row, 'TOTAL DIBUKUKAN:');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $sheet->setCellValue('E' . $row, $totalNominal);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);

        $sheet->setCellValue('F' . $row, 'CLOSED');
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $row)->getFont()->setBold(true);

        $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
        $sheet->getRowDimension($row)->setRowHeight(22);

        // 5. Mandat Pembukuan
        $row += 2;
        $sheet->setCellValue("A{$row}", "MOHON UNTUK DIBUKUKAN HARI INI {$tanggalIndonesia}");
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setName('Arial')->setSize(11)->setBold(true)->getColor()->setRGB('0F172A');

        // 6. Kolom Tanda Tangan: Hanya Yang Menerima & Titik-titik
        $row += 2;
        $sheet->setCellValue("D{$row}", 'Yang Menerima,');
        $sheet->mergeCells("D{$row}:F{$row}");
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);

        // Ruang Tanda Tangan
        $row += 4;
        $sheet->setCellValue("D{$row}", '( .................................................. )');
        $sheet->mergeCells("D{$row}:F{$row}");
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true);

        // Lebar Kolom
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(34);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);

        return $spreadsheet;
    }
}
