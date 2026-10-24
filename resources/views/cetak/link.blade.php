<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Keluhan & Slip Jurnal ATM LINK - {{ $jurnal->no_tiket }}</title>
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

        /* Container Formulir Cetak A4 */
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

        /* Header Logo & Title */
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
            font-size: 14.5px;
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
            padding: 0 2px;
        }

        .text-red {
            color: #dc2626;
            font-weight: bold;
        }

        /* Tanda Tangan Footer */
        .sig-header {
            text-align: center;
            font-weight: bold;
            padding: 8px 4px !important;
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

        /* SLIP JURNAL / NOTA DEBET STYLING */
        .page-break {
            display: none !important;
        }

        .slip-header-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: -1px;
        }

        .slip-header-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: middle;
        }

        .slip-title-main {
            font-weight: bold;
            font-size: 13px;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .slip-title-sub {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            line-height: 1.3;
        }

        .table-slip {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        .table-slip td, .table-slip th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        .slip-col-header {
            text-align: center;
            font-weight: bold;
            font-size: 11.5px;
            letter-spacing: 0.5px;
            background-color: #ffffff;
            padding: 5px 4px;
        }

        .ledger-grid {
            width: 100%;
            height: 60px;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .ledger-grid td {
            border: 1px solid #94a3b8 !important;
            padding: 0 !important;
            width: 20%;
        }

        /* Tombol Aksi Layar Monitor */
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

        /* Editable Text Element on Slip */
        .editable-text {
            display: inline-block;
            min-width: 140px;
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

        /* SLIP PAGE STYLING (HALAMAN 2: NOTA DEBET / SLIP JURNAL) */
        .slip-page {
            background: #ffffff;
            width: 210mm;
            margin: 0 auto 35px auto;
            padding: 18mm 20mm 12mm 20mm;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            page-break-before: always;
            break-before: page;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            .no-print { display: none !important; }
            .page-break { display: none !important; }
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
                min-height: 0 !important;
                height: auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .table-form {
                width: 100% !important;
                margin: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .table-form td, .table-form th {
                padding: 3.5px 6px !important;
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
            .slip-page {
                border: none !important;
                box-shadow: none !important;
                width: 210mm !important;
                max-width: 210mm !important;
                box-sizing: border-box !important;
                padding: 18mm 20mm 12mm 20mm !important;
                margin: 0 auto !important;
                min-height: 0 !important;
                height: auto !important;
                background: transparent !important;
                page-break-before: always !important;
                break-before: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .table-slip {
                width: 100% !important;
                margin: 0 !important;
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
                outline: none !important;
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
    @include('cetak.partials.ttd_toolbar_and_modal', ['judulForm' => 'Cetak Keluhan & Slip ATM LINK'])

    <!-- ================= HALAMAN 1: FORM PENYELESAIAN KELUHAN NASABAH ================= -->
    <div class="form-wrapper">
        <table class="table-form">
            <!-- HEADER -->
            <tr>
                <td class="logo-cell">
                    <img src="{{ asset('images/logo_bank_sulteng.png') }}" alt="Bank Sulteng" class="logo-img" onerror="this.outerHTML='<strong style=\'font-size:16px;color:#0284c7;\'>Bank Sulteng</strong>'">
                </td>
                <td class="title-cell">
                    FORM PENYELESAIAN KELUHAN NASABAH
                </td>
            </tr>

            <!-- DESKRIPSI -->
            <tr>
                <td colspan="2" style="padding: 4px 8px; font-weight: bold; border-bottom: 1px solid #000;">
                    Deskripsi : Permintaan
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 4px 8px; border-bottom: 1px solid #000;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 25%; border: none; padding: 0; font-weight: bold;">Permintaan</td>
                            <td style="width: 2%; border: none; padding: 0; text-align: center;">:</td>
                            <td style="border: none; padding: 0; font-style: italic; font-weight: bold;">Terlampir Keluhan Nasabah</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- LEMBAR HELPDESK -->
            <tr>
                <td colspan="2" class="section-title">LEMBAR HELPDESK</td>
            </tr>
            <tr>
                <td colspan="2" class="no-padding" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                        <tr>
                            <td style="width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 8px;">No. Tiket</td>
                            <td style="width: 30%; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold;">: {{ $jurnal->no_tiket }}</td>
                            <td style="width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 8px;">Tanggal Terima</td>
                            <td style="width: 30%; border-bottom: 1px solid #000; padding: 4px 8px;">: {{ \Carbon\Carbon::parse($jurnal->tgl_terima)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="width: 20%; border-right: 1px solid #000; padding: 4px 8px;">Nama Penerima</td>
                            <td style="width: 30%; border-right: 1px solid #000; padding: 4px 8px;">: <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama Penerima">MUJADID</span></td>
                            <td style="width: 20%; border-right: 1px solid #000; padding: 4px 8px;">Status</td>
                            <td style="width: 30%; font-weight: bold; text-transform: uppercase; padding: 4px 8px;">: {{ $jurnal->status == 'Done' ? 'SELESAI' : strtoupper($jurnal->status) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- PERMASALAHAN -->
            <tr>
                <td colspan="2" class="section-title">PERMASALAHAN</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px 12px 16px 12px;">
                    <div style="font-weight: bold; margin-top: 6px; margin-bottom: 22px; text-transform: uppercase; margin-left: 175px;">
                        : &nbsp; {{ $jurnal->permasalahan && $jurnal->permasalahan !== '-' ? $jurnal->permasalahan : ($jurnal->masterTransaksi->jenis_transaksi ?? 'TARIK TUNAI ATM LINK GAGAL, SALDO TERDEBET') }}
                    </div>

                    <table style="width: auto; margin-left: 195px; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 2.5px 0; width: 195px;">Nama</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0; font-weight: bold;">{{ $jurnal->nama_nasabah }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">No Rekening</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0;"><span class="font-bold">{{ $jurnal->no_rekening }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">No Kartu ATM</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0; font-weight: bold;">{{ $jurnal->no_kartu }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Trace & Resi</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0;"><span class="highlight-yellow" style="padding: 1px 4px;">{{ $jurnal->no_resi }}</span></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Nominal Transaksi Keluhan</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0;">
                                <div style="display: inline-flex; justify-content: space-between; width: 120px;">
                                    <span>Rp</span>
                                    <span style="font-weight: bold;">{{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Biaya Admin</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0;">
                                <div style="display: inline-flex; justify-content: space-between; width: 120px;">
                                    <span>Rp</span>
                                    <span>{{ number_format($jurnal->biaya_admin ?? $jurnal->masterTransaksi->biaya_admin ?? 0, 0, ',', '.') ?: '-' }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Tanggal Transaksi</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Cabang Transaksi</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0; font-weight: bold;">{{ (!empty($jurnal->masterCabang->kode_cabang) && trim($jurnal->masterCabang->kode_cabang) !== '-') ? trim($jurnal->masterCabang->kode_cabang) . ' - ' : '' }}{{ $jurnal->masterCabang->nama_cabang ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0;">Terminal Lokasi Transaksi</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center;">:</td>
                            <td style="border: none; padding: 2.5px 0;"><span class="highlight-yellow" style="padding: 1px 4px;">{{ $jurnal->terminal_transaksi }}</span></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- TINDAK LANJUT PENYELESAIAN -->
            <tr>
                <td colspan="2" class="section-title" style="text-align: center;">TINDAK LANJUT PENYELESAIAN</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 10px 12px 16px 12px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 2.5px 0; width: 175px; font-weight: bold;">STATUS KLAIM</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center; font-weight: bold; width: 20px;">:</td>
                            <td style="border: none; padding: 2.5px 0;" class="text-red">
                                KLAIM DITERIMA
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2.5px 0; width: 175px; font-weight: bold; vertical-align: top;">KETERANGAN</td>
                            <td style="border: none; padding: 2.5px 8px; text-align: center; font-weight: bold; vertical-align: top; width: 20px;">:</td>
                            <td style="border: none; padding: 2.5px 0; line-height: 1.5; white-space: pre-wrap;">{{ $jurnal->keterangan_log && trim($jurnal->keterangan_log) !== '-' ? $jurnal->keterangan_log : 'Berdasarkan hasil pemeriksaan log JALIN Status Transaksi tersebut gagal (ERROR CODE 00), status Transaksi ( PEMBATALAN ) Dana Masih ada dipenampungan Kewajiban LINK TELKOM. Claim di Terima.' }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 14px; margin-left: 195px; font-size: 11px; color: #000;">
                        <p style="margin: 0 0 6px 0;">Maka akan di kreditkan kepada Nasabah Bank Sulteng sebagai berikut:</p>
                        <table style="width: auto; border-collapse: collapse;">
                            <tr>
                                <td style="border: none; padding: 2px 0; width: 195px;">Nama</td>
                                <td style="border: none; padding: 2px 8px; text-align: center;">:</td>
                                <td style="border: none; padding: 2px 0; font-weight: bold;">{{ $jurnal->nama_nasabah }}</td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 2px 0; width: 195px;">No Rekening</td>
                                <td style="border: none; padding: 2px 8px; text-align: center;">:</td>
                                <td style="border: none; padding: 2px 0; font-weight: bold;">{{ $jurnal->no_rekening }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>

            <!-- TANGGAL SELESAI -->
            <tr>
                <td colspan="2" style="padding: 6px 12px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 2px 0; width: 175px; font-weight: bold;">Tanggal selesai</td>
                            <td style="border: none; padding: 2px 8px; text-align: center; font-weight: bold; width: 20px;">:</td>
                            <td style="border: none; padding: 2px 0; font-weight: bold;">{{ $jurnal->tgl_selesai ? \Carbon\Carbon::parse($jurnal->tgl_selesai)->translatedFormat('d F Y') : '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- FOOTER BLOCK TANDA TANGAN (4 KOLOM) -->
            @include('cetak.partials.ttd_block')
        </table>
    </div>

    <!-- ================= HALAMAN 2: NOTA DEBET / SLIP JURNAL DIVISI IT ================= -->
    <div class="slip-page">
        <!-- KOP SLIP JURNAL (TANPA BORDER LUAR) -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
            <tr>
                <td style="width: 32%; text-align: left; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ asset('images/logo_bank_sulteng.png') }}" alt="Bank Sulteng" class="logo-img" style="max-width: 150px; height: auto;" onerror="this.outerHTML='<strong style=\'font-size:16px;color:#0284c7;\'>Bank Sulteng</strong>'">
                </td>
                <td style="width: 36%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <div style="font-weight: bold; font-size: 13.5px; letter-spacing: 0.5px;">NOTA DEBET</div>
                </td>
                <td style="width: 32%; text-align: right; vertical-align: middle; border: none; padding: 0;">
                    <div style="font-weight: bold; font-size: 10px; line-height: 1.35; text-align: center; display: inline-block;">
                        SLIP JURNAL<br>DIVISI IT
                    </div>
                </td>
            </tr>
        </table>

        <!-- TABEL UTAMA SLIP DENGAN GRID BORDER PRESISI SESUAI PDF -->
        <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #000; font-size: 10.5px;">
            <!-- HEADER KOLOM -->
            <thead>
                <tr>
                    <th style="width: 44%; border: 1.5px solid #000; padding: 4px; text-align: center; font-weight: bold; letter-spacing: 3px;">D E B E T</th>
                    <th colspan="5" style="width: 12%; border: 1.5px solid #000; padding: 4px; text-align: center; font-weight: bold; font-size: 10px; white-space: nowrap;">Jumlah dalam Rp.</th>
                    <th style="width: 44%; border: 1.5px solid #000; padding: 4px; text-align: center; font-weight: bold; letter-spacing: 3px;">K R E D I T</th>
                </tr>
            </thead>
            <tbody>
                <!-- BARIS 1: NAMA AKUN -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: none; padding: 2px 6px; text-align: center; font-weight: bold;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama Akun Penampungan">PENAMP. SELISIH PELIMPAHAN LINK</span>
                    </td>
                    <td style="border: 1px solid #000; width: 2.4%;"></td>
                    <td style="border: 1px solid #000; width: 2.4%;"></td>
                    <td style="border: 1px solid #000; width: 2.4%;"></td>
                    <td style="border: 1px solid #000; width: 2.4%;"></td>
                    <td style="border: 1px solid #000; width: 2.4%;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: none; padding: 2px 6px; text-align: center; font-weight: bold; text-transform: uppercase;">{{ $jurnal->nama_nasabah }}</td>
                </tr>

                <!-- BARIS 2: NOMOR REKENING / AKUN -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: none; border-bottom: 1px solid #000; padding: 2px 6px 4px 6px; text-align: center; font-weight: bold;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nomor Rekening Penampungan">000.00.2310707.002.360</span>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: none; border-bottom: 1px solid #000; padding: 2px 6px 4px 6px; text-align: center; font-weight: bold;">{{ $jurnal->no_rekening }}</td>
                </tr>

                <!-- BARIS 3: HEADER REV. TRANSAKSI -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Rev. Transaksi LINK</td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Rev. Transaksi LINK</td>
                </tr>

                <!-- BARIS 4: NO KARTU ATM -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Kartu ATM</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_kartu }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Kartu ATM</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_kartu }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 5: NO RESI ATM -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Resi ATM</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_resi }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Resi ATM</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_resi }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 6: NO TIKET -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Tiket</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_tiket }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">No Tiket</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ $jurnal->no_tiket }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 7: TGL TRANSAKSI -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">Tgl Transaksi</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">Tgl Transaksi</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 8: JUMLAH TRANSAKSI & NOMINAL KREDIT -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0;">Jumlah Transaksi</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center; font-weight: bold;">:</td>
                                <td style="border: none; padding: 0;"></td>
                            </tr>
                        </table>
                    </td>
                    <!-- KOLOM LEDGER DENGAN RP & NOMINAL -->
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px; text-align: center; font-weight: bold;">Rp</td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td colspan="2" style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px 4px; text-align: right; font-weight: bold; white-space: nowrap;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nominal Transaksi">{{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}</span>
                    </td>
                    <!-- KREDIT NOMINAL -->
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 40%; border: none; padding: 0;"></td>
                                <td style="width: 12%; border: none; padding: 0; font-weight: bold; text-align: left;">Rp</td>
                                <td style="width: 48%; border: none; padding: 0; font-weight: bold; text-align: right;">
                                    <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Total Kredit">{{ number_format($jurnal->nominal_transaksi + ($jurnal->biaya_admin ?? $jurnal->masterTransaksi->biaya_admin ?? 0), 0, ',', '.') }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 9: FEE SECTION (NAMA AKUN) -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: none; padding: 2px 6px; text-align: center; font-weight: bold;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama Akun Fee">PENDAPATAN ADM LINK TELKOM</span>
                    </td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: none; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 10: FEE SECTION (NOMOR AKUN) -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: none; border-bottom: 1px solid #000; padding: 2px 6px 4px 6px; text-align: center; font-weight: bold;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nomor Rekening Fee">000.00.4020112.002.360</span>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: none; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 11: REV. FEE ADM -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px; font-weight: bold;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Keterangan Fee">REV. Fee Adm Link</span>
                    </td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 12: JUMLAH FEE & NOMINAL FEE -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 32%; border: none; padding: 0; font-weight: bold;">Jumlah Fee</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center; font-weight: bold;">:</td>
                                <td style="border: none; padding: 0;"></td>
                            </tr>
                        </table>
                    </td>
                    <!-- KOLOM LEDGER DENGAN RP & NOMINAL FEE -->
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px; text-align: center; font-weight: bold;">Rp</td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td colspan="2" style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px 4px; text-align: right; font-weight: bold; white-space: nowrap;">
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nominal Fee">{{ ($jurnal->biaya_admin ?? $jurnal->masterTransaksi->biaya_admin ?? 0) > 0 ? number_format($jurnal->biaya_admin ?? $jurnal->masterTransaksi->biaya_admin, 0, ',', '.') : '-' }}</span>
                    </td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">&nbsp;</td>
                </tr>

                <!-- BARIS 13: EXTRA SPACER ROW DENGAN LEDGER GRID -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 14: FOOTER PEMBUKUAN & OTORISASI (PRESISI 1:1 SESUAI PDF) -->
                <tr>
                    <!-- BLOK KIRI: PEMBUKUAN & DIBUKUKAN (COLSPAN 6) -->
                    <td colspan="6" style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 0; vertical-align: top; height: 125px;">
                        <table style="width: 100%; height: 100%; border-collapse: collapse; border: none; font-size: 10.5px;">
                            <tr style="height: 22px;">
                                <td colspan="2" style="width: 50%; text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">Pembukuan</td>
                                <td colspan="2" style="width: 50%; text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding: 2px 0;">Dibukukan</td>
                            </tr>
                            <tr style="height: 22px;">
                                <td style="width: 25%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">Fiat</td>
                                <td style="width: 25%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">Kontr</td>
                                <td style="width: 25%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">Oleh</td>
                                <td style="width: 25%; text-align: center; border-bottom: 1px solid #000; padding: 2px 0;">Tgl</td>
                            </tr>
                            <tr>
                                <td style="border-right: 1px solid #000; height: 81px;"></td>
                                <td style="border-right: 1px solid #000;"></td>
                                <td style="border-right: 1px solid #000;"></td>
                                <td></td>
                            </tr>
                        </table>
                    </td>

                    <!-- BLOK KANAN: DIBUAT/DITUGASKAN OLEH & PARAF TANGGAL (COLSPAN 1) -->
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 0; vertical-align: top; height: 125px;">
                        <table style="width: 100%; height: 100%; border-collapse: collapse; border: none; font-size: 10.5px;">
                            <tr style="height: 25px;">
                                <td style="width: 55%; text-align: center; font-weight: bold; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 4px;">Dibuat/Ditugaskan Oleh</td>
                                <td style="width: 45%; text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding: 2px 4px;">Paraf & Tanggal</td>
                            </tr>
                            <tr style="height: 25px;">
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">pemimpin Divisi</td>
                                <td style="border-bottom: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px 2px;" class="slip-ttd-slot-4" data-max-h="20px">
                                    @if(!empty($ttdConfig['slots'][4]) && empty($ttdConfig['slots'][4]['is_kosong']) && !empty($ttdConfig['slots'][4]['ttd_image']))
                                        <img src="{{ $ttdConfig['slots'][4]['ttd_image'] }}" alt="TTD {{ $ttdConfig['slots'][4]['nama'] }}" style="max-height: 20px; max-width: 95%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                                    @endif
                                </td>
                            </tr>
                            <tr style="height: 25px;">
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Pemimpin Bagian</td>
                                <td style="border-bottom: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px 2px;" class="slip-ttd-slot-3" data-max-h="20px">
                                    @if(!empty($ttdConfig['slots'][3]) && empty($ttdConfig['slots'][3]['is_kosong']) && !empty($ttdConfig['slots'][3]['ttd_image']))
                                        <img src="{{ $ttdConfig['slots'][3]['ttd_image'] }}" alt="TTD {{ $ttdConfig['slots'][3]['nama'] }}" style="max-height: 20px; max-width: 95%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                                    @endif
                                </td>
                            </tr>
                            <tr style="height: 25px;">
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Pemimpin Unit</td>
                                <td style="border-bottom: 1px solid #000; text-align: center; vertical-align: middle; padding: 1px 2px;" class="slip-ttd-slot-2" data-max-h="20px">
                                    @if(!empty($ttdConfig['slots'][2]) && empty($ttdConfig['slots'][2]['is_kosong']) && !empty($ttdConfig['slots'][2]['ttd_image']))
                                        <img src="{{ $ttdConfig['slots'][2]['ttd_image'] }}" alt="TTD {{ $ttdConfig['slots'][2]['nama'] }}" style="max-height: 20px; max-width: 95%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                                    @endif
                                </td>
                            </tr>
                            <tr style="height: 25px;">
                                <td style="border-right: 1px solid #000; padding: 2px 6px; vertical-align: middle;">Staf</td>
                                <td style="text-align: center; vertical-align: middle; padding: 1px 2px;" class="slip-ttd-slot-1" data-max-h="20px">
                                    @if(!empty($ttdConfig['slots'][1]) && empty($ttdConfig['slots'][1]['is_kosong']) && !empty($ttdConfig['slots'][1]['ttd_image']))
                                        <img src="{{ $ttdConfig['slots'][1]['ttd_image'] }}" alt="TTD {{ $ttdConfig['slots'][1]['nama'] }}" style="max-height: 20px; max-width: 95%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
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