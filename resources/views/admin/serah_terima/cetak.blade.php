<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SERAH TERIMA PENYELESAIAN NASABAH - {{ $tanggalIndonesia ?? date('d F Y') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        .no-print {
            max-width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .btn {
            padding: 7px 15px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back { background: #475569; color: #fff; }
        .btn-print { background: #1e3a8a; color: #fff; }

        .sheet-a4 {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm 20mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .title-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .title-header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
            letter-spacing: 0.5px;
        }

        .title-header h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 0;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table-data th, .table-data td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .table-data th {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .note-mandat {
            font-weight: bold;
            font-size: 11px;
            margin: 15px 0 35px 0;
        }

        .ttd-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .ttd-box {
            width: 45%;
            text-align: center;
        }

        .ttd-space {
            height: 70px;
        }

        .blockchain-seal {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px dashed #94a3b8;
            font-size: 9px;
            color: #64748b;
            font-family: monospace;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .sheet-a4 {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="{{ route('admin.serah-terima.index') }}" class="btn btn-back">⬅ Kembali</a>
        <div>
            <strong>{{ $nomorDokumen ?? 'DOKUMEN SERAH TERIMA' }}</strong> &bull; Total: {{ $total['total_tiket'] ?? 0 }} Tiket
        </div>
        <button type="button" onclick="window.print()" class="btn btn-print">🖨️ Cetak Dokumen</button>
    </div>

    <div class="sheet-a4">
        <div class="title-header">
            <h2>SERAH TERIMA PENYELESAIAN NASABAH</h2>
            <h3>PER TANGGAL {{ $tanggalIndonesia ?? strtoupper(date('d F Y')) }}</h3>
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 25%;">Nomor Tiket</th>
                    <th style="width: 28%;">Nama</th>
                    <th style="width: 20%;">Nomor Rekening</th>
                    <th style="width: 12%;">Nominal</th>
                    <th style="width: 10%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jurnals as $index => $j)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $j->no_tiket ?: '-' }}</td>
                    <td>{{ strtoupper($j->nama_nasabah) }}</td>
                    <td class="text-center">{{ $j->no_rekening ?: '-' }}</td>
                    <td class="text-right font-bold">{{ number_format($j->nominal_transaksi, 0, ',', '.') }}</td>
                    <td class="text-center font-bold">CLOSED</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center italic">Tidak ada data tiket terpilih.</td>
                </tr>
                @endforelse
                @if(isset($total))
                <tr>
                    <td colspan="4" class="text-right font-bold">TOTAL DIBUKUKAN:</td>
                    <td class="text-right font-bold">{{ number_format($total['total_nominal'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center font-bold">CLOSED</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="note-mandat">
            MOHON UNTUK DIBUKUKAN HARI INI {{ $tanggalIndonesia ?? strtoupper(date('d F Y')) }}
        </div>

        <div class="ttd-container">
            <div class="ttd-box">
                <div>Yang Menyerahkan,</div>
                <div style="font-size: 10px; color: #475569; margin-top: 2px;">Staf Layanan Keluhan & Monitoring Transaksi Kartu</div>
                <div class="ttd-space"></div>
                <div class="font-bold" style="text-decoration: underline;">MUJADID</div>
                <div style="font-size: 10px;">Staf Layanan Keluhan</div>
            </div>
            <div class="ttd-box">
                <div>Yang Menerima,</div>
                <div style="font-size: 10px; color: #475569; margin-top: 2px;">Bagian Pembukuan / Operasional PT Bank Sulteng</div>
                <div class="ttd-space"></div>
                <div class="font-bold" style="text-decoration: underline;">( .................................................. )</div>
                <div style="font-size: 10px;">Petugas Pembukuan</div>
            </div>
        </div>

        <div class="blockchain-seal">
            🔐 Otentikasi Integritas Dokumen (Blockchain Hash): {{ $blockchainHash ?? '-' }}<br>
            ID Dokumen: {{ $nomorDokumen ?? '-' }} &bull; Diverifikasi oleh Sistem E-Jurnal Bank Sulteng
        </div>
    </div>

</body>
</html>
