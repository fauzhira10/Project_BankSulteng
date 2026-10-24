<?php

namespace Database\Seeders;

use App\Models\MasterPejabatTtd;
use Illuminate\Database\Seeder;

class MasterPejabatTtdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SVG signature paths (elegan, resolusi tinggi, latar transparan, tinta hitam/biru gelap)
        $ttdMujadid = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 120" width="280" height="120">
                <path d="M 25 75 Q 40 25 60 45 T 80 85 Q 95 30 115 60 Q 130 90 145 65 Q 160 40 185 70 T 215 55 Q 240 70 260 50 M 40 95 C 100 85 180 88 250 82" 
                      stroke="#0f172a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M 55 45 Q 65 30 75 40" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            </svg>'
        );

        $ttdAyu = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 120" width="280" height="120">
                <path d="M 30 85 C 35 35 60 20 75 55 C 90 90 100 85 110 50 C 120 20 135 60 145 75 C 160 95 180 30 200 65 Q 220 100 245 45 M 20 90 Q 130 105 255 75" 
                      stroke="#0f172a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M 70 50 Q 110 52 140 48" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            </svg>'
        );

        $ttdWachyuni = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 120" width="280" height="120">
                <path d="M 20 40 Q 35 95 55 90 Q 75 35 95 85 Q 115 40 135 85 Q 150 45 170 75 T 205 60 Q 230 40 255 70 M 35 100 C 90 90 180 92 250 85" 
                      stroke="#0f172a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M 170 70 Q 190 35 210 55" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round" fill="none"/>
            </svg>'
        );

        $ttdDiana = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 120" width="280" height="120">
                <path d="M 35 35 Q 25 90 45 95 Q 85 95 90 35 Q 95 85 120 70 Q 140 50 160 80 Q 180 30 200 75 T 240 60 Q 255 45 260 70 M 30 95 C 100 85 190 88 255 78" 
                      stroke="#0f172a" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <circle cx="165" cy="40" r="3" fill="#0f172a"/>
                <path d="M 195 50 L 215 50" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round"/>
            </svg>'
        );

        $ttdAhmad = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 120" width="280" height="120">
                <path d="M 30 75 Q 45 20 65 50 Q 80 80 100 40 Q 120 90 140 60 T 180 70 Q 210 35 240 65 M 35 90 Q 120 100 250 80" 
                      stroke="#0f172a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>'
        );

        $pejabats = [
            // Slot 1: Staf
            [
                'slot' => 1,
                'nama' => 'MUJADID',
                'jabatan' => 'Staf Layanan Keluhan dan Monitoring Transaksi Kartu',
                'nip' => 'BS-990124',
                'ttd_image' => $ttdMujadid,
                'is_aktif' => true,
                'is_default' => true,
                'urutan' => 1,
            ],
            // Slot 2: Pemimpin Unit
            [
                'slot' => 2,
                'nama' => 'AYU FEBRIANTI',
                'jabatan' => 'Pemimpin Unit Layanan Keluhan dan Monitoring Transaksi Kartu',
                'nip' => 'BS-880412',
                'ttd_image' => $ttdAyu,
                'is_aktif' => true,
                'is_default' => true,
                'urutan' => 1,
            ],
            [
                'slot' => 2,
                'nama' => 'BUDI SANTOSO, S.Kom',
                'jabatan' => 'Plt. Pemimpin Unit Layanan Keluhan',
                'nip' => 'BS-890520',
                'ttd_image' => $ttdAhmad,
                'is_aktif' => true,
                'is_default' => false,
                'urutan' => 2,
            ],
            // Slot 3: PINBAG E-Channel
            [
                'slot' => 3,
                'nama' => 'WACHYUNI MADARAYU',
                'jabatan' => 'PINBAG E- CHANNEL',
                'nip' => 'BS-770319',
                'ttd_image' => $ttdWachyuni,
                'is_aktif' => true,
                'is_default' => true,
                'urutan' => 1,
            ],
            // Slot 4: Pemimpin Divisi IT
            [
                'slot' => 4,
                'nama' => 'DIANA, ST',
                'jabatan' => 'Pemimpin Divisi IT',
                'nip' => 'BS-650811',
                'ttd_image' => $ttdDiana,
                'is_aktif' => true,
                'is_default' => true,
                'urutan' => 1,
            ],
            [
                'slot' => 4,
                'nama' => 'H. AHMAD, SE',
                'jabatan' => 'Plt. Pemimpin Divisi IT',
                'nip' => 'BS-640209',
                'ttd_image' => $ttdAhmad,
                'is_aktif' => true,
                'is_default' => false,
                'urutan' => 2,
            ],
        ];

        foreach ($pejabats as $data) {
            MasterPejabatTtd::updateOrCreate(
                ['slot' => $data['slot'], 'nama' => $data['nama']],
                $data
            );
        }
    }
}
