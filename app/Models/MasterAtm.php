<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAtm extends Model
{
    protected $guarded = ['id'];

    /**
     * Data Master 149 Mesin ATM PT Bank Sulteng Lengkap Sesuai Dokumen Resmi
     */
    public static function getAllAtms(): array
    {
        return [
            // CABANG PALU BARAT (008)
            ['no' => 1, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'CRM.PALUBARAT', 'lokasi' => 'KANTOR PALU BARAT', 'id_luno' => '180'],
            ['no' => 2, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'WCR.PALBAR', 'lokasi' => 'SWISS BELL', 'id_luno' => '209'],
            ['no' => 3, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'GRG.PALBAR3', 'lokasi' => 'SMART KITCHEN', 'id_luno' => '444'],
            ['no' => 4, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'GRG.PALBAR4', 'lokasi' => 'MUSEUM', 'id_luno' => '451'],
            ['no' => 5, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'GRG.PALBAR5', 'lokasi' => 'KANTOR PALU BARAT', 'id_luno' => '460'],
            ['no' => 6, 'kode_cabang' => '008', 'cabang' => 'CABANG PALU BARAT', 'profil' => 'GRG.PALBAR6', 'lokasi' => 'KANTOR PALU BARAT', 'id_luno' => '461'],

            // KCP TOLAI (105)
            ['no' => 7, 'kode_cabang' => '105', 'cabang' => 'KCP TOLAI', 'profil' => 'GRG.TOLAI1', 'lokasi' => 'KANTOR TOLAI', 'id_luno' => '474'],

            // KCP BATUI (404)
            ['no' => 8, 'kode_cabang' => '404', 'cabang' => 'KCP BATUI', 'profil' => 'DBL.KASBATUI', 'lokasi' => 'KANTOR KAS BATUI', 'id_luno' => '102'],
            ['no' => 9, 'kode_cabang' => '404', 'cabang' => 'KCP BATUI', 'profil' => 'WCR.KASBATUI2', 'lokasi' => 'POLSEK KINTOM', 'id_luno' => '427'],

            // KCP TOILI (405)
            ['no' => 10, 'kode_cabang' => '405', 'cabang' => 'KCP TOILI', 'profil' => 'WCR.TOILI', 'lokasi' => 'KCP TOILI', 'id_luno' => '103'],
            ['no' => 11, 'kode_cabang' => '405', 'cabang' => 'KCP TOILI', 'profil' => 'WCR.TOILI2', 'lokasi' => 'GALERI ATM PASAR TOILI', 'id_luno' => '165'],

            // KCP MAMOSALATO (411)
            ['no' => 12, 'kode_cabang' => '411', 'cabang' => 'KCP MAMOSALATO', 'profil' => 'WCR.MAMOSALATO', 'lokasi' => 'GALERY ATM MAMOSALATO', 'id_luno' => '160'],

            // KCP TAWAELI (801)
            ['no' => 13, 'kode_cabang' => '801', 'cabang' => 'KCP TAWAELI', 'profil' => 'GRG.TAWAELI1', 'lokasi' => 'KCP TAWAELI', 'id_luno' => '472'],
            ['no' => 14, 'kode_cabang' => '801', 'cabang' => 'KCP TAWAELI', 'profil' => 'GRG.TAWAELI2', 'lokasi' => 'KANTOR CAMAT TOAYA', 'id_luno' => '473'],

            // KCP TOMATA (412)
            ['no' => 15, 'kode_cabang' => '412', 'cabang' => 'KCP TOMATA', 'profil' => 'WCR.TOMATA', 'lokasi' => 'KCP TOMATA', 'id_luno' => '208'],

            // KCP BATURUBE (413)
            ['no' => 16, 'kode_cabang' => '413', 'cabang' => 'KCP BATURUBE', 'profil' => 'NCR.BATURUBE', 'lokasi' => 'KANTOR BATURUBE', 'id_luno' => '135'],

            // KCP TINOMBO (106)
            ['no' => 17, 'kode_cabang' => '106', 'cabang' => 'KCP TINOMBO', 'profil' => 'GRG.TINOMBO1', 'lokasi' => 'KCP TINOMBO', 'id_luno' => '470'],

            // KCP BAHODOPI (502)
            ['no' => 18, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'WCR.MBHDP', 'lokasi' => 'ATM MOBIL BAHODOPI', 'id_luno' => '152'],
            ['no' => 19, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'CRM.BAHODOPI', 'lokasi' => 'KANTOR KCP BAHODOPI', 'id_luno' => '190'],
            ['no' => 20, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'WCR.BHDP2', 'lokasi' => 'BUNGKU PESISIR', 'id_luno' => '218'],
            ['no' => 21, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'GRG.BHDP1', 'lokasi' => 'KCP BAHODOPI1', 'id_luno' => '496'],
            ['no' => 22, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'GRG.BHDP4', 'lokasi' => 'GALERI QARIM LABOTA', 'id_luno' => '497'],
            ['no' => 23, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'GRG.BHDP5', 'lokasi' => 'RUSUNAWA SMI', 'id_luno' => '498'],
            ['no' => 24, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'GRG.BHDP7', 'lokasi' => 'KCP BAHODOPI7', 'id_luno' => '499'],
            ['no' => 25, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'GRG.BHDP6', 'lokasi' => 'SPBU BAHODOPI', 'id_luno' => '500'],
            ['no' => 26, 'kode_cabang' => '502', 'cabang' => 'KCP BAHODOPI', 'profil' => 'RBR.BHDP3', 'lokasi' => 'KCP BAHODOPI3', 'id_luno' => '501'],

            // KCP PENDOLO (304)
            ['no' => 27, 'kode_cabang' => '304', 'cabang' => 'KCP PENDOLO', 'profil' => 'GRG.PENDOLO1', 'lokasi' => 'KCP PENDOLO', 'id_luno' => '464'],

            // CABANG JAKARTA (009)
            ['no' => 28, 'kode_cabang' => '009', 'cabang' => 'CABANG JAKARTA', 'profil' => 'GRG.JAKARTA', 'lokasi' => 'KANTOR CABANG JAKARTA', 'id_luno' => '437'],

            // KCP NAPU (305)
            ['no' => 29, 'kode_cabang' => '305', 'cabang' => 'KCP NAPU', 'profil' => 'DBL.NAPU', 'lokasi' => 'KANTOR KAS NAPU', 'id_luno' => '219'],

            // KCP KULAWI (701)
            ['no' => 30, 'kode_cabang' => '701', 'cabang' => 'KCP KULAWI', 'profil' => 'GRG.KULAWI1', 'lokasi' => 'KCP KULAWI', 'id_luno' => '462'],

            // KCP TINOMBALA (107)
            ['no' => 31, 'kode_cabang' => '107', 'cabang' => 'KCP TINOMBALA', 'profil' => 'GRG.TINOMBALA1', 'lokasi' => 'KCP TINOMBALA', 'id_luno' => '466'],

            // KCP KOTARAYA (108)
            ['no' => 32, 'kode_cabang' => '108', 'cabang' => 'KCP KOTARAYA', 'profil' => 'DBL.KOTARAYA', 'lokasi' => 'CAPEM KOTARAYA', 'id_luno' => '235'],

            // KCP TAMBARANA (306)
            ['no' => 33, 'kode_cabang' => '306', 'cabang' => 'KCP TAMBARANA', 'profil' => 'DBL.TAMBARANA1', 'lokasi' => 'CAPEM TAMBARANA', 'id_luno' => '234'],

            // KCP MASAMA (406)
            ['no' => 34, 'kode_cabang' => '406', 'cabang' => 'KCP MASAMA', 'profil' => 'DBL.MASAMA', 'lokasi' => 'KCP MASAMA', 'id_luno' => '236'],

            // KCP BUNTA (407)
            ['no' => 35, 'kode_cabang' => '407', 'cabang' => 'KCP BUNTA', 'profil' => 'DBL.BUNTA01', 'lokasi' => 'KCP BUNTA', 'id_luno' => '237'],

            // CABANG UTAMA / PALU (001)
            ['no' => 36, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'DBL.GBNR', 'lokasi' => 'KANTOR GUBERNUR SULTENG', 'id_luno' => '2'],
            ['no' => 37, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'DBL.SAMS', 'lokasi' => 'SAMSAT PALU', 'id_luno' => '18'],
            ['no' => 38, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'DBL.BKD', 'lokasi' => 'KANTOR BKD PALU', 'id_luno' => '72'],
            ['no' => 39, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'DBL.WALIKOTA', 'lokasi' => 'KANTOR WALIKOTA', 'id_luno' => '76'],
            ['no' => 40, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'CRM.KCU1', 'lokasi' => 'BAPENDA PALU', 'id_luno' => '176'],
            ['no' => 41, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'WCR.KCU1', 'lokasi' => 'RSUD UNDATA', 'id_luno' => '200'],
            ['no' => 42, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'CRM.KCU3', 'lokasi' => 'KCU PALU', 'id_luno' => '240'],
            ['no' => 43, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'CRM.KCU4', 'lokasi' => 'KCU PALU', 'id_luno' => '241'],
            ['no' => 44, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'CRM.KCU5', 'lokasi' => 'KCU PALU', 'id_luno' => '242'],
            ['no' => 45, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'CRM.KCU6', 'lokasi' => 'KCU PALU', 'id_luno' => '243'],
            ['no' => 46, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU1', 'lokasi' => 'KANTOR CABANG UTAMA PALU', 'id_luno' => '435'],
            ['no' => 47, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU2', 'lokasi' => 'PERIJINAN PALU', 'id_luno' => '435'],
            ['no' => 48, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU7', 'lokasi' => 'ALFAMIDI DESTIK', 'id_luno' => '441'],
            ['no' => 49, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU8', 'lokasi' => 'DINKES PALU', 'id_luno' => '446'],
            ['no' => 50, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU9', 'lokasi' => 'ALFAMIDI TOUWA', 'id_luno' => '447'],
            ['no' => 51, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU10', 'lokasi' => 'GRAND HERO', 'id_luno' => '448'],
            ['no' => 52, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU11', 'lokasi' => 'MRAYA', 'id_luno' => '449'],
            ['no' => 53, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU12', 'lokasi' => 'ALFAMIDI VETERAN', 'id_luno' => '450'],
            ['no' => 54, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU13', 'lokasi' => 'BAPPEDA', 'id_luno' => '454'],
            ['no' => 55, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU14', 'lokasi' => 'DIKJAR', 'id_luno' => '455'],
            ['no' => 56, 'kode_cabang' => '001', 'cabang' => 'CABANG UTAMA', 'profil' => 'GRG.KCU15', 'lokasi' => 'DISPERINDAKOP', 'id_luno' => '456'],

            // CABANG TOLI TOLI (002)
            ['no' => 57, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'CRM.TOLIS2', 'lokasi' => 'ALFAMIDI SANDANA', 'id_luno' => '433'],
            ['no' => 58, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'YHU.TOL6', 'lokasi' => 'KANTOR CABANG TOLIS', 'id_luno' => '224'],
            ['no' => 59, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'DBL.TOL2', 'lokasi' => 'KANTOR CABANG TOLIS', 'id_luno' => '23'],
            ['no' => 60, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'WCR.TOL5', 'lokasi' => 'ALFAMIDI DESA NOPI', 'id_luno' => '164'],
            ['no' => 61, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'CRM.TOLIS1', 'lokasi' => 'KANTOR CABANG TOLIS', 'id_luno' => '195'],
            ['no' => 62, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'DBL.TOL8', 'lokasi' => 'KANTOR CABANG TOLIS', 'id_luno' => '434'],
            ['no' => 63, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'GRG.TOL3', 'lokasi' => 'KANTOR KEUANGAN TOLIS', 'id_luno' => '480'],
            ['no' => 64, 'kode_cabang' => '002', 'cabang' => 'CABANG TOLI TOLI', 'profil' => 'GRG.TOL4', 'lokasi' => 'KANTOR CABANG TOLIS', 'id_luno' => '481'],

            // CABANG POSO (003)
            ['no' => 65, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'CRM.POSO', 'lokasi' => 'KANTOR CABANG POSO', 'id_luno' => '183'],
            ['no' => 66, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'WCR.POSO8', 'lokasi' => 'DENZIPUR POSO', 'id_luno' => '214'],
            ['no' => 67, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO3', 'lokasi' => 'KANTOR CABANG POSO', 'id_luno' => '452'],
            ['no' => 68, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO6', 'lokasi' => 'KANTOR BUPATI POSO', 'id_luno' => '453'],
            ['no' => 69, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO2', 'lokasi' => 'KASIGUNCU', 'id_luno' => '457'],
            ['no' => 70, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO10', 'lokasi' => 'KANTOR BUPATI POSO', 'id_luno' => '458'],
            ['no' => 71, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO9', 'lokasi' => 'RSUD POSO', 'id_luno' => '459'],
            ['no' => 72, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO4', 'lokasi' => 'KANTOR CABANG POSO', 'id_luno' => '467'],
            ['no' => 73, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO5', 'lokasi' => 'KANTOR CABANG POSO', 'id_luno' => '468'],
            ['no' => 74, 'kode_cabang' => '003', 'cabang' => 'CABANG POSO', 'profil' => 'GRG.POSO7', 'lokasi' => 'KANTOR CABANG POSO', 'id_luno' => '469'],

            // CABANG LUWUK (004)
            ['no' => 75, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'DBL.LUWUK3', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '50'],
            ['no' => 76, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'DBL.LUWUK', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '3'],
            ['no' => 77, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'DBL.LWK', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '15'],
            ['no' => 78, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'DBL.LUWUK5', 'lokasi' => 'BUPATI LUWUK', 'id_luno' => '52'],
            ['no' => 79, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'CRM.LUWUK', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '185'],
            ['no' => 80, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'YHU.LUWUK10', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '229'],
            ['no' => 81, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'YHU.LUWUK11', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '230'],
            ['no' => 82, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'CRM.LUWUK12', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '430'],
            ['no' => 83, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'CRM.LUWUK13', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '431'],
            ['no' => 84, 'kode_cabang' => '004', 'cabang' => 'CABANG LUWUK', 'profil' => 'CRM.LUWUK14', 'lokasi' => 'KANTOR CABANG LUWUK', 'id_luno' => '432'],

            // CABANG BUNGKU (005)
            ['no' => 85, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'CRM.BUNGKU', 'lokasi' => 'KANTOR CABANG BUNGKU', 'id_luno' => '188'],
            ['no' => 86, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'CRM.BUNGKU4', 'lokasi' => 'KANTOR CABANG BUNGKU', 'id_luno' => '244'],
            ['no' => 87, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'CRM.BUNGKU5', 'lokasi' => 'KANTOR CABANG BUNGKU', 'id_luno' => '245'],
            ['no' => 88, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'CRM.BUNGKU6', 'lokasi' => 'KANTOR CABANG BUNGKU', 'id_luno' => '246'],
            ['no' => 89, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'GRG.BUNGKU2', 'lokasi' => 'WITAPONDA BUNGKU', 'id_luno' => '486'],
            ['no' => 90, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'GRG.BUNGKU3', 'lokasi' => 'KANTOR LAMA', 'id_luno' => '487'],
            ['no' => 91, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'GRG.BUNGKU1', 'lokasi' => 'KANTOR CABANG BUNGKU', 'id_luno' => '488'],
            ['no' => 92, 'kode_cabang' => '005', 'cabang' => 'CABANG BUNGKU', 'profil' => 'GRG.BUNGKU7', 'lokasi' => 'KANTOR BUPATI BUNGKU', 'id_luno' => '489'],

            // CABANG SALAKAN (006)
            ['no' => 93, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'DBL.SLKN', 'lokasi' => 'KANTOR KEUANGAN SALAKAN', 'id_luno' => '36'],
            ['no' => 94, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'NCR.SLKN', 'lokasi' => 'PERTOKOAN SALAKAN', 'id_luno' => '84'],
            ['no' => 95, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'WCR.SLKN', 'lokasi' => 'RSUD SALAKAN', 'id_luno' => '157'],
            ['no' => 96, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'CRM.SALAKAN', 'lokasi' => 'KANTOR CABANG SALAKAN', 'id_luno' => '187'],
            ['no' => 97, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'WCR.SLKN2', 'lokasi' => 'KANTOR CABANG SALAKAN', 'id_luno' => '210'],
            ['no' => 98, 'kode_cabang' => '006', 'cabang' => 'CABANG SALAKAN', 'profil' => 'YHU.SLKN', 'lokasi' => 'KANTOR CABANG SALAKAN', 'id_luno' => '233'],

            // CABANG DONGGALA (101)
            ['no' => 99,  'kode_cabang' => '101', 'cabang' => 'CABANG DONGGALA', 'profil' => 'CRM.DONGGALA', 'lokasi' => 'KC DONGGALA', 'id_luno' => '181'],
            ['no' => 100, 'kode_cabang' => '101', 'cabang' => 'CABANG DONGGALA', 'profil' => 'YHU.DGL2', 'lokasi' => 'KC DONGGALA', 'id_luno' => '228'],
            ['no' => 101, 'kode_cabang' => '101', 'cabang' => 'CABANG DONGGALA', 'profil' => 'GRG.DGL3', 'lokasi' => 'SPBU DONGGALA', 'id_luno' => '443'],

            // CABANG PARIGI (102)
            ['no' => 102, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'DBL.PRGI', 'lokasi' => 'ALFAMIDI TOBOLI PARIGI', 'id_luno' => '6'],
            ['no' => 103, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'WCR.KCPARIGI', 'lokasi' => 'KANTOR CABANG PARIGI', 'id_luno' => '107'],
            ['no' => 104, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'WCR.PRGI', 'lokasi' => 'ALFAMIDI PARIGI', 'id_luno' => '161'],
            ['no' => 105, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'CRM.PARIGI', 'lokasi' => 'KANTOR CABANG PARIGI', 'id_luno' => '182'],
            ['no' => 106, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'GRG.PRG1', 'lokasi' => 'RSUD ANUNTALOKO', 'id_luno' => '475'],
            ['no' => 107, 'kode_cabang' => '102', 'cabang' => 'CABANG PARIGI', 'profil' => 'GRG.PRG2', 'lokasi' => 'KANTOR BUPATI PARIMO', 'id_luno' => '476'],

            // CABANG BUOL (201)
            ['no' => 108, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'WCR.BUOL', 'lokasi' => 'INDOMARET BUNOBOGU', 'id_luno' => '158'],
            ['no' => 109, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'CRM.BUOL', 'lokasi' => 'KANTOR CABANG BUOL', 'id_luno' => '192'],
            ['no' => 110, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'WCR.BUOL2', 'lokasi' => 'SPBU BUOL', 'id_luno' => '212'],
            ['no' => 111, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'YHU.BUOL7', 'lokasi' => 'KANTOR CABANG BUOL', 'id_luno' => '231'],
            ['no' => 112, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'GRG.BUOL4', 'lokasi' => 'INDOMARET BUSAK', 'id_luno' => '483'],
            ['no' => 113, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'GRG.BUOL5', 'lokasi' => 'KANTOR KEUANGAN BUOL', 'id_luno' => '484'],
            ['no' => 114, 'kode_cabang' => '201', 'cabang' => 'CABANG BUOL', 'profil' => 'GRG.BUOL3', 'lokasi' => 'RSUD BUOL', 'id_luno' => '485'],

            // KCP PALELEH (211)
            ['no' => 115, 'kode_cabang' => '211', 'cabang' => 'KCP PALELEH', 'profil' => 'GRG.PALELEH', 'lokasi' => 'KCP PALELEH', 'id_luno' => '490'],

            // CABANG AMPANA (301)
            ['no' => 116, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'DBL.AMP', 'lokasi' => 'INDOMARAET AMPANA', 'id_luno' => '21'],
            ['no' => 117, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'DBL.AMP3', 'lokasi' => 'MARAJA MART AMPANA', 'id_luno' => '58'],
            ['no' => 118, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'DBL.TOJO', 'lokasi' => 'KANTOR CAMAT TOJO', 'id_luno' => '101'],
            ['no' => 119, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'DBL.AMP5', 'lokasi' => 'KANTOR BUPATI AMPANA', 'id_luno' => '167'],
            ['no' => 120, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'DBL.AMP2', 'lokasi' => 'KANTOR CABANG AMPANA', 'id_luno' => '57'],
            ['no' => 121, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'WCR.AMP1', 'lokasi' => 'KANTOR CABANG AMPANA', 'id_luno' => '166'],
            ['no' => 122, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'CRM.AMPANA', 'lokasi' => 'KANTOR CABANG AMPANA', 'id_luno' => '184'],
            ['no' => 123, 'kode_cabang' => '301', 'cabang' => 'CABANG AMPANA', 'profil' => 'YHU.AMP4', 'lokasi' => 'KANTOR CABANG AMPANA', 'id_luno' => '227'],

            // CABANG KOLONODALE (401)
            ['no' => 124, 'kode_cabang' => '401', 'cabang' => 'CABANG KOLONODALE', 'profil' => 'WCR.MORO2', 'lokasi' => 'KC KOLONODALE', 'id_luno' => '155'],
            ['no' => 125, 'kode_cabang' => '401', 'cabang' => 'CABANG KOLONODALE', 'profil' => 'CRM.KOLONODALE', 'lokasi' => 'KC KOLONODALE', 'id_luno' => '189'],
            ['no' => 126, 'kode_cabang' => '401', 'cabang' => 'CABANG KOLONODALE', 'profil' => 'YHU.KODAL4', 'lokasi' => 'KANTOR CABANG KODAL', 'id_luno' => '225'],
            ['no' => 127, 'kode_cabang' => '401', 'cabang' => 'CABANG KOLONODALE', 'profil' => 'YHU.KODAL5', 'lokasi' => 'KANTOR CABANG KODAL', 'id_luno' => '226'],
            ['no' => 128, 'kode_cabang' => '401', 'cabang' => 'CABANG KOLONODALE', 'profil' => 'GRG.KODAL3', 'lokasi' => 'SPBU KOLONODALE', 'id_luno' => '479'],

            // CABANG BANGGAI LAUT (402)
            ['no' => 129, 'kode_cabang' => '402', 'cabang' => 'CABANG BANGGAI LAUT', 'profil' => 'DBL.BALUT3', 'lokasi' => 'BALAIKOTA BALUT', 'id_luno' => '203'],
            ['no' => 130, 'kode_cabang' => '402', 'cabang' => 'CABANG BANGGAI LAUT', 'profil' => 'NCR.BALUT', 'lokasi' => 'RSUD BANGGAI LAUT', 'id_luno' => '85'],
            ['no' => 131, 'kode_cabang' => '402', 'cabang' => 'CABANG BANGGAI LAUT', 'profil' => 'CRM.BALUT', 'lokasi' => 'KANTOR CABANG BANGGAI LAUT', 'id_luno' => '186'],
            ['no' => 132, 'kode_cabang' => '402', 'cabang' => 'CABANG BANGGAI LAUT', 'profil' => 'DBL.BALUT2', 'lokasi' => 'KC BANGGAI LAUT', 'id_luno' => '201'],
            ['no' => 133, 'kode_cabang' => '402', 'cabang' => 'CABANG BANGGAI LAUT', 'profil' => 'DBL.BALUT4', 'lokasi' => 'KC BANGGAI LAUT', 'id_luno' => '238'],

            // CABANG SIGI (007)
            ['no' => 134, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'WCR.SIGI', 'lokasi' => 'PUSKESMAS KALEKE', 'id_luno' => '159'],
            ['no' => 135, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'CRM.SIGI1', 'lokasi' => 'KANTOR CABANG SIGI', 'id_luno' => '179'],
            ['no' => 136, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'GRG.SIGI3', 'lokasi' => 'KANTOR CABANG SIGI', 'id_luno' => '442'],
            ['no' => 137, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'GRG.SIGI2', 'lokasi' => 'KANTOR BUPATI SIGI', 'id_luno' => '493'],
            ['no' => 138, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'GRG.SIGI4', 'lokasi' => 'KELAPA GADING SIGI', 'id_luno' => '495'],
            ['no' => 149, 'kode_cabang' => '007', 'cabang' => 'CABANG SIGI', 'profil' => 'DBL.PALOLO', 'lokasi' => 'KCP PALOLO', 'id_luno' => '463'],

            // KCP BAHOMOTEFE (501)
            ['no' => 139, 'kode_cabang' => '501', 'cabang' => 'KCP BAHOMOTEFE', 'profil' => 'GRG.BHMTF1', 'lokasi' => 'KCP BAHOMOTEFE', 'id_luno' => '491'],
            ['no' => 140, 'kode_cabang' => '501', 'cabang' => 'KCP BAHOMOTEFE', 'profil' => 'GRG.BHMTF2', 'lokasi' => 'KCP BAHOMOTEFE', 'id_luno' => '492'],

            // KCP BETELEME (403)
            ['no' => 141, 'kode_cabang' => '403', 'cabang' => 'KCP BETELEME', 'profil' => 'GRG.BETELEME1', 'lokasi' => 'KC BETELEME', 'id_luno' => '478'],

            // KCP LAMBUNU (103)
            ['no' => 142, 'kode_cabang' => '103', 'cabang' => 'KCP LAMBUNU', 'profil' => 'GRG.LAMBUNU1', 'lokasi' => 'KCP LAMBUNU', 'id_luno' => '471'],

            // KCP SONI (202)
            ['no' => 143, 'kode_cabang' => '202', 'cabang' => 'KCP SONI', 'profil' => 'WCR.SONI', 'lokasi' => 'KCP SONI', 'id_luno' => '211'],

            // KCP WAKAI (302)
            ['no' => 144, 'kode_cabang' => '302', 'cabang' => 'KCP WAKAI', 'profil' => 'DBL.WAKAI2', 'lokasi' => 'KCP WAKAI', 'id_luno' => '198'],

            // KCP TENTENA (303)
            ['no' => 145, 'kode_cabang' => '303', 'cabang' => 'KCP TENTENA', 'profil' => 'YHU.TTNA2', 'lokasi' => 'KCP TENTENA', 'id_luno' => '232'],
            ['no' => 146, 'kode_cabang' => '303', 'cabang' => 'KCP TENTENA', 'profil' => 'GRG.TTNA1', 'lokasi' => 'KCP TENTENA', 'id_luno' => '465'],

            // KCP LABEAN (104)
            ['no' => 147, 'kode_cabang' => '104', 'cabang' => 'KCP LABEAN', 'profil' => 'WCR.SIOYONG', 'lokasi' => 'ALFAMIDI SIOYONG', 'id_luno' => '159'],
            ['no' => 148, 'kode_cabang' => '104', 'cabang' => 'KCP LABEAN', 'profil' => 'GRG.LABEAN1', 'lokasi' => 'KCP LABEAN', 'id_luno' => '477'],
        ];
    }

    /**
     * Mengambil daftar ATM yang diformat untuk dropdown per cabang
     */
    public static function getAtmsGroupedByCabang(): array
    {
        $all = self::getAllAtms();
        $grouped = [];

        foreach ($all as $atm) {
            $formattedValue = "{$atm['id_luno']} - {$atm['profil']}";
            $displayLabel   = "{$atm['id_luno']} - {$atm['profil']} ({$atm['lokasi']})";

            $grouped[$atm['kode_cabang']][] = [
                'value'        => $formattedValue,
                'label'        => $displayLabel,
                'id_luno'      => $atm['id_luno'],
                'profil'       => $atm['profil'],
                'lokasi'       => $atm['lokasi'],
                'kode_cabang'  => $atm['kode_cabang'],
                'cabang'       => $atm['cabang']
            ];
        }

        return $grouped;
    }

    /**
     * Mencari detail info mesin ATM berdasarkan nama terminal / profil / id_luno
     */
    public static function findAtmInfo(?string $terminal): ?array
    {
        if (empty($terminal)) {
            return null;
        }

        $term = strtoupper(trim($terminal));
        if ($term === '-' || str_contains($term, 'BANK LAIN') || str_contains($term, 'MOBILE BANKING') || str_contains($term, 'SMS BANKING') || str_contains($term, 'INTERNET BANKING')) {
            return null;
        }

        $all = self::getAllAtms();

        // 1. Exact formatted match "180 - CRM.PALUBARAT"
        foreach ($all as $atm) {
            $formattedValue = strtoupper("{$atm['id_luno']} - {$atm['profil']}");
            if ($term === $formattedValue) {
                return $atm;
            }
        }

        // 2. Exact Profil match "CRM.PALUBARAT"
        foreach ($all as $atm) {
            $profil = strtoupper($atm['profil']);
            if ($term === $profil) {
                return $atm;
            }
        }

        // 3. String contains Profil
        foreach ($all as $atm) {
            $profil = strtoupper($atm['profil']);
            if (!empty($profil) && str_contains($term, $profil)) {
                return $atm;
            }
        }

        // 4. Exact ID LUNO match
        if (preg_match('/^\d+$/', $term)) {
            foreach ($all as $atm) {
                if ((string)$atm['id_luno'] === $term) {
                    return $atm;
                }
            }
        }

        return null;
    }
}
