<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERAH TERIMA PENYELESAIAN NASABAH — {{ $tanggalIndonesia ?? strtoupper(date('d F Y')) }}</title>
    <meta name="description" content="Dokumen Resmi Serah Terima Penyelesaian Pengaduan Nasabah PT Bank Sulteng — {{ $nomorDokumen ?? '' }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        /* ============================================================
           SERAH TERIMA — Layout Cetak A4 Resmi
           PT Bank Pembangunan Daerah Sulawesi Tengah
           Standar Kerja Operasional | Layanan Keluhan Nasabah
        ============================================================ */

        *, *::before, *::after {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            background-color: #f1f5f9;
            margin: 0;
            padding: 18px 16px 80px;
            color: #000;
        }

        /* ─── Toolbar (hilang saat cetak) ─────────────────────── */
        .toolbar {
            max-width: 210mm;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .toolbar-info {
            font-size: 11px;
            color: #475569;
        }
        .toolbar-info strong { color: #0f172a; }
        .toolbar-actions { display: flex; gap: 8px; }

        .btn {
            padding: 7px 16px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 11px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: filter .15s, transform .12s;
        }
        .btn:hover { filter: brightness(1.08); transform: scale(1.02); }
        .btn-back  { background: #475569; color: #fff; }
        .btn-print { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; box-shadow: 0 3px 10px rgba(37,99,235,.3); }

        /* ─── Kertas A4 ────────────────────────────────────────── */
        .sheet-a4 {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 16mm 20mm 12mm;
            box-shadow: 0 4px 20px rgba(0,0,0,.09);
            position: relative;
        }

        /* ─── Kop Dokumen ──────────────────────────────────────── */
        .doc-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 2.5px solid #000;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 0 0 5px;
        }
        .doc-subtitle {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 3px;
        }
        .doc-nomor {
            font-size: 10px;
            color: #475569;
            margin: 0;
        }

        /* ─── Tabel Utama ──────────────────────────────────────── */
        .tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .tbl th,
        .tbl td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: middle;
        }
        .tbl thead th {
            background-color: #f0f4f8;
            font-weight: bold;
            text-align: center;
            font-size: 10.5px;
            letter-spacing: 0.2px;
        }
        .tbl tbody tr:nth-child(even) td { background-color: #f9fafb; }
        .tbl tfoot td {
            font-weight: bold;
            background-color: #f0f4f8;
        }
        .tc  { text-align: center; }
        .tr  { text-align: right; }
        .tl  { text-align: left; }
        .fw  { font-weight: bold; }
        .fm  { font-family: 'Courier New', monospace; font-size: 10px; }

        /* ─── Mandat Pembukuan ─────────────────────────────────── */
        .mandat {
            font-weight: bold;
            font-size: 11.5px;
            margin: 18px 0 28px;
            padding: 10px 14px;
            border: 1px solid #000;
            border-radius: 4px;
            background: #fffbeb;
            letter-spacing: 0.3px;
        }

        /* ─── Kolom Tanda Tangan ───────────────────────────────── */
        .ttd-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 22px;
        }
        .ttd-col {
            width: 47%;
            text-align: center;
        }
        .ttd-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .ttd-jabatan {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 4px;
        }
        .ttd-space {
            height: 68px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }
        .ttd-nama {
            font-weight: bold;
            font-size: 11px;
            text-decoration: underline;
        }
        .ttd-sub {
            font-size: 9.5px;
            color: #475569;
            margin-top: 1px;
        }

        /* ─── Blockchain Seal / Stempel Audit ─────────────────── */
        .bc-seal {
            margin-top: 36px;
            padding-top: 9px;
            border-top: 1px dashed #94a3b8;
            font-size: 8.5px;
            color: #64748b;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        .bc-seal .bc-icon { margin-right: 3px; }

        /* ─── Print Media Query ────────────────────────────────── */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .sheet-a4 {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: unset;
                padding: 14mm 18mm 10mm;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    {{-- ==================== TOOLBAR (no-print) ==================== --}}
    <div class="toolbar">
        <div class="toolbar-info">
            <strong>{{ $nomorDokumen ?? 'DOKUMEN SERAH TERIMA' }}</strong>
            &bull;
            <span>{{ $total['total_tiket'] ?? 0 }} Tiket</span>
            &bull;
            <span>{{ $total['total_nominal_rupiah'] ?? 'Rp 0' }}</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('admin.serah-terima.index') }}" class="btn btn-back">
                ⬅ Kembali
            </a>
            <button type="button" onclick="window.print()" class="btn btn-print" id="btnCetakDokumen">
                🖨️ Cetak Dokumen
            </button>
        </div>
    </div>

    {{-- ==================== KERTAS A4 ==================== --}}
    <div class="sheet-a4">

        {{-- Kop Dokumen --}}
        <div class="doc-header">
            <p class="doc-title">Serah Terima Penyelesaian Nasabah</p>
            <p class="doc-subtitle">Per Tanggal {{ $tanggalIndonesia ?? strtoupper(now()->locale('id')->translatedFormat('d F Y')) }}</p>
            <p class="doc-nomor">No. Dokumen: {{ $nomorDokumen ?? '—' }}</p>
        </div>

        {{-- Tabel Data Tiket --}}
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:5%">No.</th>
                    <th style="width:22%">Nomor Tiket</th>
                    <th style="width:28%">Nama</th>
                    <th style="width:20%">Nomor Rekening</th>
                    <th style="width:14%">Nominal</th>
                    <th style="width:11%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jurnals as $index => $j)
                <tr>
                    <td class="tc">{{ $index + 1 }}</td>
                    <td class="fw fm">{{ $j->no_tiket ?: '—' }}</td>
                    <td>{{ strtoupper($j->nama_nasabah) }}</td>
                    <td class="tc fm">{{ $j->no_rekening ?: '—' }}</td>
                    <td class="tr fw">Rp {{ number_format($j->nominal_transaksi, 0, ',', '.') }}</td>
                    <td class="tc fw">CLOSED</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="tc" style="font-style:italic; padding: 12px;">
                        Tidak ada data tiket terpilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="tr fw" style="padding-right: 8px;">TOTAL DIBUKUKAN:</td>
                    <td class="tr fw">Rp {{ number_format($total['total_nominal'] ?? 0, 0, ',', '.') }}</td>
                    <td class="tc fw">{{ $total['total_tiket'] ?? 0 }} Tiket</td>
                </tr>
            </tfoot>
        </table>

        {{-- Mandat Pembukuan --}}
        <div class="mandat">
            MOHON UNTUK DIBUKUKAN HARI INI
            {{ $tanggalIndonesia ?? strtoupper(now()->locale('id')->translatedFormat('d F Y')) }}
        </div>

        {{-- Kolom Tanda Tangan --}}
        <div class="ttd-row">
            {{-- Kiri: Yang Menyerahkan --}}
            <div class="ttd-col">
                <div class="ttd-title">Yang Menyerahkan,</div>
                <div class="ttd-jabatan">Staf Layanan Keluhan & Monitoring Transaksi Kartu</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">MUJADID</div>
                <div class="ttd-sub">Staf Layanan Keluhan</div>
            </div>

            {{-- Kanan: Yang Menerima --}}
            <div class="ttd-col">
                <div class="ttd-title">Yang Menerima,</div>
                <div class="ttd-jabatan">Bagian Pembukuan / Operasional PT Bank Sulteng</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
                <div class="ttd-sub">Petugas Pembukuan / Operasional</div>
            </div>
        </div>

        {{-- Blockchain Seal / Stempel Audit Hash --}}
        <div class="bc-seal">
            <span class="bc-icon">🔐</span>
            <strong>Otentikasi Integritas Dokumen (Blockchain Hash):</strong>
            {{ $blockchainHash ?? '—' }}<br>
            <span>ID Dokumen: {{ $nomorDokumen ?? '—' }}</span>
            &nbsp;&bull;&nbsp;
            <span>Diverifikasi oleh Sistem E-Jurnal Keluhan PT Bank Sulteng</span>
            &nbsp;&bull;&nbsp;
            <span>Dicetak: {{ now()->locale('id')->translatedFormat('d F Y, H:i') }} WITA</span>
        </div>

    </div>

</body>
</html>
