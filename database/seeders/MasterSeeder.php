<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // Data Master 41 Kantor Cabang & KCP Bank Sulteng
        $cabangs = [
            ['kode_cabang' => '000', 'nama_cabang' => 'BANK LAIN'],
            ['kode_cabang' => '001', 'nama_cabang' => 'CABANG UTAMA'],
            ['kode_cabang' => '002', 'nama_cabang' => 'CABANG TOLI TOLI'],
            ['kode_cabang' => '003', 'nama_cabang' => 'CABANG POSO'],
            ['kode_cabang' => '004', 'nama_cabang' => 'CABANG LUWUK'],
            ['kode_cabang' => '005', 'nama_cabang' => 'CABANG BUNGKU'],
            ['kode_cabang' => '006', 'nama_cabang' => 'CABANG SALAKAN'],
            ['kode_cabang' => '007', 'nama_cabang' => 'CABANG SIGI'],
            ['kode_cabang' => '008', 'nama_cabang' => 'CABANG PALU BARAT'],
            ['kode_cabang' => '009', 'nama_cabang' => 'CABANG JAKARTA'],
            ['kode_cabang' => '101', 'nama_cabang' => 'CABANG DONGGALA'],
            ['kode_cabang' => '102', 'nama_cabang' => 'CABANG PARIGI'],
            ['kode_cabang' => '103', 'nama_cabang' => 'KCP LAMBUNU'],
            ['kode_cabang' => '104', 'nama_cabang' => 'KCP LABEAN'],
            ['kode_cabang' => '105', 'nama_cabang' => 'KCP TOLAI'],
            ['kode_cabang' => '106', 'nama_cabang' => 'KCP TINOMBO'],
            ['kode_cabang' => '107', 'nama_cabang' => 'KCP TINOMBALA'],
            ['kode_cabang' => '201', 'nama_cabang' => 'CABANG BUOL'],
            ['kode_cabang' => '202', 'nama_cabang' => 'KCP SONI'],
            ['kode_cabang' => '211', 'nama_cabang' => 'KCP PALELEH'],
            ['kode_cabang' => '301', 'nama_cabang' => 'CABANG AMPANA'],
            ['kode_cabang' => '302', 'nama_cabang' => 'KCP WAKAI'],
            ['kode_cabang' => '303', 'nama_cabang' => 'KCP TENTENA'],
            ['kode_cabang' => '304', 'nama_cabang' => 'KCP PENDOLO'],
            ['kode_cabang' => '305', 'nama_cabang' => 'KCP NAPU'],
            ['kode_cabang' => '401', 'nama_cabang' => 'CABANG KOLONODALE'],
            ['kode_cabang' => '402', 'nama_cabang' => 'CABANG BANGGAI LAUT'],
            ['kode_cabang' => '403', 'nama_cabang' => 'KCP BETELEME'],
            ['kode_cabang' => '404', 'nama_cabang' => 'KCP BATUI'],
            ['kode_cabang' => '405', 'nama_cabang' => 'KCP TOILI'],
            ['kode_cabang' => '411', 'nama_cabang' => 'KCP MAMOSALATO'],
            ['kode_cabang' => '412', 'nama_cabang' => 'KCP TOMATA'],
            ['kode_cabang' => '413', 'nama_cabang' => 'KCP BATURUBE'],
            ['kode_cabang' => '501', 'nama_cabang' => 'KCP BAHOMOTEFE'],
            ['kode_cabang' => '502', 'nama_cabang' => 'KCP BAHODOPI'],
            ['kode_cabang' => '701', 'nama_cabang' => 'KCP KULAWI'],
            ['kode_cabang' => '801', 'nama_cabang' => 'KCP TAWAELI'],
            ['kode_cabang' => '406', 'nama_cabang' => 'KCP MASAMA'],
            ['kode_cabang' => '306', 'nama_cabang' => 'KCP TAMBARANA'],
            ['kode_cabang' => '407', 'nama_cabang' => 'KCP BUNTA'],
            ['kode_cabang' => '108', 'nama_cabang' => 'KCP KOTARAYA'],
        ];

        foreach ($cabangs as $cabang) {
            DB::table('master_cabangs')->updateOrInsert(
                ['kode_cabang' => $cabang['kode_cabang']],
                ['nama_cabang' => $cabang['nama_cabang'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Data Master 33 Jenis Transaksi lengkap
        $transaksis = [
            ['jenis_transaksi' => 'ATM_TARIK TUNAI ATM BANK SULTENG', 'biaya_admin' => 0, 'channel' => 'ATM LOKAL'],
            ['jenis_transaksi' => 'ATM_TARIK TUNAI DI BANK LAIN', 'biaya_admin' => 0, 'channel' => 'ATM BERSAMA'],
            ['jenis_transaksi' => 'CRM_SETOR TUNAI', 'biaya_admin' => 0, 'channel' => 'ATM LOKAL'],
            ['jenis_transaksi' => 'ATM_TRANSFER MESIN BANK SULTENG', 'biaya_admin' => 0, 'channel' => 'ATM LOKAL'],
            ['jenis_transaksi' => 'ATM_TRANSFER MESIN BANK LAIN', 'biaya_admin' => 0, 'channel' => 'ATM BERSAMA'],
            ['jenis_transaksi' => 'ATM_TELKOM', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_PULSA TSEL', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_PULSA XL', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_PLN PREPAID', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_DANA', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_GOPAY', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_PEMBAYARAN HALO', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'ATM_BPJS', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'SMS BANKING (TRANSFER INTERNAL)', 'biaya_admin' => 0, 'channel' => 'SMS BANKING'],
            ['jenis_transaksi' => 'SMS BANKING (TRANSFER KE BANK LAIN)', 'biaya_admin' => 0, 'channel' => 'SMS BANKING'],
            ['jenis_transaksi' => 'SMS BANKING (PULSA TSEL)', 'biaya_admin' => 0, 'channel' => 'SMS BANKING'],
            ['jenis_transaksi' => 'SMS BANKING (PLN PREPAID)', 'biaya_admin' => 0, 'channel' => 'SMS BANKING'],
            ['jenis_transaksi' => 'MBANKING_TRANSFER', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_TELKOM', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_PULSA TSEL', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_PULSA XL', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_PLN PREPAID', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_DANA', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_PEMBAYARAN', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_PEMBAYARAN HALO', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'MBANKING_BPJS', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'EDC', 'biaya_admin' => 0, 'channel' => 'DEBIT'],
            ['jenis_transaksi' => 'EDC BANK LAIN', 'biaya_admin' => 0, 'channel' => 'EDC BANK LAIN'],
            ['jenis_transaksi' => 'QRIS', 'biaya_admin' => 0, 'channel' => 'MOBILE BANKING'],
            ['jenis_transaksi' => 'LAKU PANDAI', 'biaya_admin' => 0, 'channel' => 'LAKU PANDAI'],
            ['jenis_transaksi' => 'LAINNYA_PERMINTAAN CCTV', 'biaya_admin' => 0, 'channel' => 'CCTV'],
            ['jenis_transaksi' => 'PEMBAYARAN', 'biaya_admin' => 0, 'channel' => 'FINNET'],
            ['jenis_transaksi' => 'PEMBELIAN', 'biaya_admin' => 0, 'channel' => 'FINNET'],
        ];

        foreach ($transaksis as $t) {
            DB::table('master_transaksis')->updateOrInsert(
                ['jenis_transaksi' => $t['jenis_transaksi']],
                ['biaya_admin' => $t['biaya_admin'], 'channel' => $t['channel'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}