<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Keluhan & Hasil Pemeriksaan CCTV - {{ $jurnal->no_tiket }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
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
            padding: 30px 20px;
            color: #000;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .form-wrapper {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 35px auto;
            padding: 18mm 20mm 12mm 20mm;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        .table-form {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        .table-form td, .table-form th {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .table-form td.no-padding {
            padding: 0 !important;
        }

        .logo-cell {
            width: 25%;
            text-align: center;
            padding: 10px !important;
        }

        .logo-img {
            max-width: 140px;
            max-height: 48px;
            height: auto;
        }

        .title-cell {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .section-title {
            font-weight: bold;
            background-color: #ffffff;
            text-transform: uppercase;
        }

        .highlight-yellow {
            background-color: #ffff00 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
            font-weight: bold;
        }

        .text-red {
            color: #dc2626;
            font-weight: bold;
        }

        /* Tanda Tangan Form */
        .sig-header {
            text-align: center;
            font-weight: bold;
            padding: 6px 4px !important;
            font-size: 11px;
        }

        .sig-space-cell {
            height: 140px;
            padding: 0 !important;
            border-bottom: none !important;
        }

        .sig-name-cell {
            text-align: center;
            vertical-align: bottom !important;
            padding: 4px 4px 0 4px !important;
            border-top: none !important;
            border-bottom: none !important;
        }

        .sig-title-cell {
            text-align: center;
            vertical-align: top !important;
            padding: 2px 4px 10px 4px !important;
            border-top: none !important;
        }

        .sig-body {
            height: 200px;
            text-align: center;
            vertical-align: bottom !important;
            padding: 10px 6px 12px 6px !important;
        }

        .sig-space {
            height: 140px;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            font-size: 11px;
            margin-bottom: 3px;
            letter-spacing: 0.3px;
            text-align: center;
            display: inline-block;
        }

        .sig-title {
            font-size: 9px;
            color: #000;
            line-height: 1.35;
            padding: 0 2px;
            text-align: center;
            display: inline-block;
        }

        .sig-name.editable-text,
        .sig-title.editable-text {
            min-width: 0;
            cursor: text;
        }

        /* Tombol Aksi */
        .no-print {
            width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }

        .btn-print { background-color: #0284c7; color: #ffffff; border: none; }
        .btn-back { background-color: #64748b; color: #ffffff; border: none; }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        /* Editable Text Element */
        .editable-text {
            display: inline-block;
            min-width: 40px;
            padding: 2px 6px;
            border-radius: 4px;
            cursor: text;
            transition: all 0.2s ease;
        }

        .editable-text:hover {
            background-color: #fef08a;
            box-shadow: 0 0 0 1.5px #ca8a04;
        }

        .editable-text:focus {
            background-color: #fef9c3;
            box-shadow: 0 0 0 2px #0284c7;
            outline: none;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            .no-print { display: none !important; }
            html, body {
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .form-wrapper {
                border: none !important;
                width: 210mm !important;
                max-width: 210mm !important;
                box-sizing: border-box !important;
                padding: 18mm 20mm 12mm 20mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                min-height: auto !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .table-form {
                width: 100% !important;
                margin: 0 !important;
            }
            .table-form td.no-padding {
                padding: 0 !important;
            }
            .sig-space-cell {
                height: 130px !important;
                padding: 0 !important;
            }
            .sig-name-cell {
                padding: 3px 4px 0 4px !important;
            }
            .sig-title-cell {
                padding: 2px 4px 8px 4px !important;
            }
            .sig-body {
                height: 190px !important;
                padding: 6px 4px 8px 4px !important;
            }
            .sig-space {
                height: 130px !important;
            }
            .highlight-yellow {
                background-color: #ffff00 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .editable-text {
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                min-width: 0 !important;
                display: inline !important;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Aksi Layar & Studio TTD -->
    @include('cetak.partials.ttd_toolbar_and_modal', ['judulForm' => 'Cetak Formulir Permohonan Rekaman CCTV'])

    <div class="form-wrapper">
        <table class="table-form">
            <tr>
                <td class="logo-cell">
                    <img src="{{ asset('images/logo_bank_sulteng.png') }}" alt="Bank Sulteng" class="logo-img" onerror="this.outerHTML='<strong style=\'font-size:16px;color:#0284c7;\'>Bank Sulteng</strong>'">
                </td>
                <td class="title-cell">
                    FORM PENYELESAIAN KELUHAN NASABAH (CCTV)
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 15%; border: none; padding: 2px 0;">Deskripsi</td>
                            <td style="width: 2%; border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: bold;">Permintaan</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px 0;">Permintaan</td>
                            <td style="border: none; padding: 2px 0;">:</td>
                            <td style="border: none; padding: 2px 0; font-style: italic;">Terlampir Keluhan Nasabah</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- LEMBAR HELPDESK -->
            <tr>
                <td colspan="2" class="section-title">LEMBAR HELPDESK:</td>
            </tr>
            <tr>
                <td colspan="2" class="no-padding" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 15%; border-right: 1px solid #000; border-bottom: 1px solid #000;">No. Tiket</td>
                            <td style="width: 35%; border-right: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold;">: {{ $jurnal->no_tiket }}</td>
                            <td style="width: 15%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Tanggal Terima</td>
                            <td style="width: 35%; border-bottom: 1px solid #000;">: {{ \Carbon\Carbon::parse($jurnal->tgl_terima)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="border-right: 1px solid #000;">Nama Penerima</td>
                            <td style="border-right: 1px solid #000;">: <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama Penerima">MUJADID</span></td>
                            <td style="border-right: 1px solid #000;">Status</td>
                            <td style="font-weight: bold; text-transform: uppercase;">: {{ $jurnal->status == 'Done' ? 'SELESAI' : $jurnal->status }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="section-title">PERMASALAHAN:</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px;">
                    <div style="font-weight: bold; margin-bottom: 10px; text-transform: uppercase;">
                        : {{ $jurnal->permasalahan && $jurnal->permasalahan !== '-' ? $jurnal->permasalahan : ($jurnal->masterTransaksi->jenis_transaksi ?? 'PEMERIKSAAN REKAMAN CCTV ATM') }}
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-left: 0;">
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Nama</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0; font-weight: bold;">{{ $jurnal->nama_nasabah }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">No Rekening</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0; font-weight: bold;">{{ $jurnal->no_rekening }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">No Kartu ATM/No HP</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0;">{{ $jurnal->no_kartu }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Trace & Resi</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0;"><span class="highlight-yellow">{{ $jurnal->no_resi }}</span></td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Nominal Transaksi Keluhan</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0; font-weight: bold;">Rp {{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Tanggal Transaksi</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Cabang Transaksi</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0;">{{ (!empty($jurnal->masterCabang->kode_cabang) && trim($jurnal->masterCabang->kode_cabang) !== '-') ? trim($jurnal->masterCabang->kode_cabang) . ' - ' : '' }}{{ $jurnal->masterCabang->nama_cabang ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 3px 0;">Terminal Lokasi Transaksi</td>
                            <td style="width: 2%; border: none; padding: 3px 0; text-align: center;">:</td>
                            <td style="border: none; padding: 3px 0;"><span class="highlight-yellow">{{ $jurnal->terminal_transaksi }}</span></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="section-title" style="text-align: center;">TINDAK LANJUT PENYELESAIAN</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 25%; border: none; padding: 4px 0; font-weight: bold;">STATUS KLAIM</td>
                            <td style="width: 2%; border: none; padding: 4px 0; font-weight: bold; text-align: center;">:</td>
                            <td style="border: none; padding: 4px 0;" class="text-red">
                                {{ strtolower($jurnal->status) == 'rejected' ? 'KLAIM DITOLAK' : 'KLAIM DITERIMA' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 25%; border: none; padding: 4px 0; font-weight: bold; vertical-align: top;">KETERANGAN</td>
                            <td style="width: 2%; border: none; padding: 4px 0; font-weight: bold; vertical-align: top; text-align: center;">:</td>
                            <td style="border: none; padding: 4px 0; line-height: 1.5; white-space: pre-wrap;">{{ $jurnal->keterangan_log ?? 'Hasil penyelesaian CCTV ATM terlampir pada berita acara penyelesaian keluhan nasabah. Berita acara keluhan dan hasil data CCTV tidak untuk di serahkan ke nasabah' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" style="padding: 6px 10px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 25%; border: none; padding: 0;">Tanggal selesai</td>
                            <td style="width: 2%; border: none; padding: 0; text-align: center;">:</td>
                            <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->tgl_selesai ? \Carbon\Carbon::parse($jurnal->tgl_selesai)->translatedFormat('d F Y') : '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- FOOTER BLOCK TANDA TANGAN (4 KOLOM) -->
            @include('cetak.partials.ttd_block')
        </table>
    </div>

    <script>
        function handleKembali() {
            window.close();
            setTimeout(function() {
                if (!window.closed) {
                    window.location.href = "{{ route('jurnal.index') }}";
                }
            }, 150);
        }
    </script>
</body>
</html>