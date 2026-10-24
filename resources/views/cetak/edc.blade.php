@php
    $ttdConfig = $jurnal->getTtdConfig();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Jurnal EDC (Nota Debet) - {{ $jurnal->no_tiket }}</title>
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

        /* Container Slip Cetak A4 */
        .form-wrapper {
            background: #ffffff;
            width: 210mm;
            margin: 0 auto 35px auto;
            padding: 18mm 20mm 12mm 20mm;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        /* Toolbar Aksi Layar Monitor */
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

    @include('cetak.partials.ttd_toolbar_and_modal', ['judulForm' => 'Cetak Slip Jurnal EDC (Nota Debet)'])

    <!-- ================= SLIP JURNAL / NOTA DEBET DIVISI IT (EDC) ================= -->
    <div class="form-wrapper">
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

        <!-- TABEL UTAMA SLIP DENGAN GRID BORDER PRESISI SESUAI FORMAT CETAK -->
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
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama Akun Penampungan">KEWAJIBAN PURCHASE & VOID MESIN EDC ATMB</span>
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
                        <span class="editable-text" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nomor Rekening Penampungan">000.00.2310713.001.360</span>
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
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Rev. Transaksi EDC</td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">Rev. Transaksi EDC</td>
                </tr>

                <!-- BARIS 4: NO KARTU ATM -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 38%; border: none; padding: 0;">No Kartu ATM</td>
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
                                <td style="width: 38%; border: none; padding: 0;">No Kartu ATM</td>
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
                                <td style="width: 38%; border: none; padding: 0;">No Resi ATM</td>
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
                                <td style="width: 38%; border: none; padding: 0;">No Resi ATM</td>
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
                                <td style="width: 38%; border: none; padding: 0;">No Tiket</td>
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
                                <td style="width: 38%; border: none; padding: 0;">No Tiket</td>
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
                                <td style="width: 38%; border: none; padding: 0;">Tgl Transaksi</td>
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
                                <td style="width: 38%; border: none; padding: 0;">Tgl Transaksi</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0; font-weight: bold;">{{ \Carbon\Carbon::parse($jurnal->tgl_transaksi)->translatedFormat('d F Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- BARIS 8: JUMLAH TRANSAKSI & NOMINAL KREDIT -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 38%; border: none; padding: 0;">Jumlah Transaksi</td>
                                <td style="width: 4%; border: none; padding: 0; text-align: center;">:</td>
                                <td style="border: none; padding: 0;"></td>
                            </tr>
                        </table>
                    </td>
                    <!-- KOLOM LEDGER DENGAN RP & NOMINAL -->
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px; text-align: center; font-weight: bold;">Rp</td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000;"></td>
                    <td colspan="2" style="border: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px 4px; text-align: right; font-weight: bold; white-space: nowrap;">{{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}</td>
                    <!-- KREDIT NOMINAL (SEJAJAR DI BAWAH NILAI TGL TRANSAKSI) -->
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1.5px solid #000; padding: 2px 6px;">
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

                <!-- BARIS 9: SPACER ROW DENGAN LEDGER GRID -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1.5px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-top: 1.5px solid #000; height: 16px;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1.5px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 10: SPACER ROW DENGAN LEDGER GRID -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 11: SPACER ROW DENGAN LEDGER GRID -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; height: 16px;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 12: SPACER ROW DENGAN LEDGER GRID (RP -) -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; padding: 2px; text-align: center; font-weight: bold;">Rp</td>
                    <td style="border: 1px solid #000;"></td>
                    <td style="border: 1px solid #000;"></td>
                    <td colspan="2" style="border: 1px solid #000; padding: 2px 4px; text-align: right; font-weight: bold; white-space: nowrap;">{{ number_format($jurnal->biaya_admin ?? $jurnal->masterTransaksi->biaya_admin ?? 0, 0, ',', '.') ?: '-' }}</td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000; height: 16px;">&nbsp;</td>
                </tr>

                <!-- BARIS 13: EXTRA SPACER ROW DENGAN LEDGER GRID -->
                <tr>
                    <td style="border-left: 1.5px solid #000; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;">&nbsp;</td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;"></td>
                    <td style="border-left: 1px solid #000; border-right: 1.5px solid #000; border-top: 1px solid #000; border-bottom: 1.5px solid #000; height: 16px;">&nbsp;</td>
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
