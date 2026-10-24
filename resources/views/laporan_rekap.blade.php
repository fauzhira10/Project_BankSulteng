@extends('layouts.app')

@section('title', 'Rekap Laporan Keluhan Nasabah - PT Bank Sulteng')

@section('content')
<!-- Hero Header -->
<div class="bg-gradient-to-r from-navy-dark via-blue-900 to-sky-700 rounded-2xl p-6 text-white mb-6 shadow-md flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2.5 m-0 mb-1.5">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Laporan Penyelesaian Keluhan Nasabah</span>
        </h1>
        <p class="text-xs sm:text-sm text-white/85 m-0">Rekapitulasi statistik penyelesaian pengaduan keluhan nasabah per bulan & tahunan PT Bank Sulteng.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
        <span class="bg-white/15 border border-white/30 backdrop-blur-xs px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold text-amber-200">
            Tahun Periode: {{ $selectedYear }}
        </span>
        <a href="{{ route('laporan.export_excel', ['tahun' => $selectedYear, 'master_cabang_id' => $selectedCabang, 'status' => $selectedStatus]) }}" 
           class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-xl bg-white hover:bg-slate-100 text-navy font-bold text-xs shadow-xs transition-colors"
           title="Unduh file Excel (.xlsx) dengan tata letak matriks 12 bulan identik">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Export Excel (.xlsx)</span>
        </a>
    </div>
</div>

<!-- Filter Bar Modern & Rapi -->
<div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-xs hover:shadow-md transition-shadow">
    <div class="flex justify-between items-center pb-3 mb-4 border-b border-slate-100 flex-wrap gap-2">
        <div class="flex items-center gap-2 text-xs font-bold text-navy uppercase tracking-wider">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <span>Parameter Filter Laporan</span>
        </div>
        <div class="text-xs text-slate-500 font-medium">
            Saring data matriks berdasarkan tahun laporan, kantor cabang, dan status penyelesaian keluhan
        </div>
    </div>

    <form action="{{ route('laporan.index') }}" method="GET" id="laporanFilterForm">
        <input type="hidden" name="bulan" id="hiddenBulanInput" value="{{ $selectedMonth }}">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1.6fr_1.3fr_auto] gap-4 items-end">
            <!-- Pilihan Tahun -->
            <div class="flex flex-col gap-1.5">
                <label for="filterTahun" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Tahun Laporan</span>
                </label>
                <div class="relative flex items-center">
                    <select name="tahun" id="filterTahun" onchange="this.form.submit()" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>
                                Tahun {{ $yr }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pilihan Cabang -->
            <div class="flex flex-col gap-1.5">
                <label for="filterCabang" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"></path>
                        <path d="M5 21V7l8-4v18"></path>
                        <path d="M19 21V11l-6-4"></path>
                    </svg>
                    <span>Kantor Cabang</span>
                </label>
                <div class="relative flex items-center">
                    <select name="master_cabang_id" id="filterCabang" onchange="this.form.submit()" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        <option value="">-- Seluruh Kantor Cabang & KCP --</option>
                        @foreach($cabangs as $c)
                            <option value="{{ $c->id }}" {{ $selectedCabang == $c->id ? 'selected' : '' }}>
                                {{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pilihan Status -->
            <div class="flex flex-col gap-1.5">
                <label for="filterStatus" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span>Status Keluhan</span>
                </label>
                <div class="relative flex items-center">
                    <select name="status" id="filterStatus" onchange="this.form.submit()" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        <option value="">-- Semua Status Keluhan --</option>
                        <option value="Done" {{ $selectedStatus === 'Done' ? 'selected' : '' }}>Done (Selesai)</option>
                        <option value="Success" {{ $selectedStatus === 'Success' ? 'selected' : '' }}>Success (Berhasil)</option>
                        <option value="Menunggu" {{ $selectedStatus === 'Menunggu' ? 'selected' : '' }}>Menunggu (Dalam Proses)</option>
                        <option value="Rejected" {{ $selectedStatus === 'Rejected' ? 'selected' : '' }}>Rejected (Ditolak)</option>
                        <option value="-" {{ $selectedStatus === '-' ? 'selected' : '' }}>- (Belum Ditentukan)</option>
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center gap-2.5 h-[2.625rem]">
                <button type="submit" class="h-[2.625rem] px-5 rounded-xl bg-gradient-to-r from-navy to-blue-700 hover:from-navy-dark hover:to-blue-800 text-white font-bold text-xs inline-flex items-center gap-2 shadow-sm shadow-navy/20 transition-all cursor-pointer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Terapkan</span>
                </button>
                <a href="{{ route('laporan.index', ['tahun' => $selectedYear]) }}" class="h-[2.625rem] px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 font-semibold text-xs border border-slate-200 inline-flex items-center gap-1.5 transition-colors" title="Reset Filter Cabang & Status">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path>
                    </svg>
                    <span>Reset</span>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Month Tabs Navigation Bar -->
<div class="bg-white border border-slate-200 rounded-2xl p-3.5 mb-6 shadow-xs">
    <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5 flex justify-between items-center flex-wrap gap-1">
        <span>PILIH BULAN UNTUK DETAIL DRILL-DOWN</span>
        <span class="font-normal text-[0.71875rem] text-slate-500">Klik salah satu bulan untuk melihat rincian & grafik</span>
    </div>
    <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-12 gap-2">
        @for($m = 1; $m <= 12; $m++)
            @php
                $mTotal = $colTotals[$m] ?? 0;
                $isActive = $selectedMonth == $m;
            @endphp
            @if($isActive)
                <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-navy bg-gradient-to-br from-navy to-blue-800 text-white shadow-md shadow-navy/25 hover:-translate-y-0.5 transition-all cursor-pointer select-none" onclick="selectMonthTab({{ $m }})">
                    <span class="text-[0.71875rem] font-bold tracking-wide text-white">{{ strtoupper(substr($monthNames[$m], 0, 3)) }}</span>
                    <span class="text-[0.71875rem] font-semibold mt-0.5 px-2 py-0.5 rounded-full bg-white/25 text-white">{{ $mTotal }}</span>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl border border-slate-200 bg-slate-50 text-navy hover:bg-sky-50 hover:border-sky-300 hover:-translate-y-0.5 transition-all cursor-pointer select-none" onclick="selectMonthTab({{ $m }})">
                    <span class="text-[0.71875rem] font-bold tracking-wide">{{ strtoupper(substr($monthNames[$m], 0, 3)) }}</span>
                    <span class="text-[0.71875rem] font-semibold mt-0.5 px-2 py-0.5 rounded-full bg-slate-200/70 text-slate-700">{{ $mTotal }}</span>
                </div>
            @endif
        @endfor
    </div>
</div>

<!-- Drill-down Card untuk Bulan Terpilih -->
<div class="bg-white border border-sky-200 border-l-4 border-l-navy rounded-2xl p-5 mb-6 shadow-xs grid grid-cols-1 lg:grid-cols-[1.5fr_2.5fr_auto] gap-5 items-center">
    <!-- Stat 1: Total Bulan -->
    <div>
        <h3 class="text-xs text-slate-500 font-bold uppercase tracking-wider m-0 mb-1">Total Klaim {{ $activeMonthName }} {{ $selectedYear }}</h3>
        <div class="text-3xl font-extrabold text-navy leading-none">{{ number_format($activeMonthTotal, 0, ',', '.') }}</div>
        <div class="text-xs text-slate-500 mt-1.5">
            Dari total <strong class="text-navy">{{ number_format($grandTotal, 0, ',', '.') }}</strong> klaim tahun {{ $selectedYear }}
        </div>
    </div>

    <!-- Stat 2: Top Jenis Keluhan Terbanyak -->
    <div>
        <div class="text-xs font-bold text-navy mb-2 uppercase tracking-wider">
            Top Kategori Keluhan Bulan {{ $activeMonthName }}
        </div>
        @if(count($monthCategoryRank) > 0)
            <div class="flex flex-col gap-1.5">
                @foreach(array_slice($monthCategoryRank, 0, 3) as $rk)
                    <div class="flex items-center justify-between text-xs gap-2.5">
                        <span class="font-semibold text-slate-700 truncate max-w-[13.75rem]" title="{{ $rk['label'] }}">{{ $rk['label'] }}</span>
                        <div class="grow h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-600 to-sky-400 rounded-full" style="width: {{ $rk['percentage'] }}%;"></div>
                        </div>
                        <span class="text-[0.71875rem] font-bold text-navy bg-slate-100 px-2 py-0.5 rounded-full shrink-0">{{ $rk['count'] }} klaim</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-xs text-slate-500 italic">
                Tidak ada data keluhan tercatat di bulan {{ $activeMonthName }} {{ $selectedYear }}.
            </div>
        @endif
    </div>

    <!-- Aksi Drilldown ke Halaman Data Keluhan -->
    <div class="flex flex-col gap-2 justify-center">
        @php
            $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
            $lastDay = date('t', strtotime($startDate));
            $endDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $lastDay);
        @endphp
        <a href="{{ route('jurnal.index', ['tgl_dari' => $startDate, 'tgl_sampai' => $endDate, 'master_cabang_id' => $selectedCabang, 'status' => $selectedStatus]) }}" 
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 shadow-xs transition-all whitespace-nowrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <span>Buka Transaksi {{ $activeMonthName }}</span>
        </a>
    </div>
</div>

<!-- Matriks Tabel Laporan 12 Bulan (Identik Format Excel Bank Sulteng) -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden mb-8">
    <div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center flex-wrap gap-3 bg-slate-50">
        <h2 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="9" y1="21" x2="9" y2="9"></line>
            </svg>
            <span>Matriks Rekapitulasi Tahunan (Januari - Desember {{ $selectedYear }})</span>
        </h2>
        <div class="text-xs text-slate-500">
            Total Seluruh Klaim: <strong class="text-navy text-sm font-extrabold">{{ number_format($grandTotal, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-xs text-left">
            <thead>
                <tr>
                    <th rowspan="2" class="p-2.5 border border-slate-300 bg-slate-100 text-navy font-bold text-center w-10">NO</th>
                    <th rowspan="2" class="p-2.5 border border-slate-300 bg-slate-100 text-navy font-bold text-center w-9"></th>
                    <th rowspan="2" class="p-2.5 border border-slate-300 bg-slate-100 text-navy font-bold text-left min-w-[15rem]">JENIS KLAIM</th>
                    @for($m = 1; $m <= 12; $m++)
                        <th class="p-2.5 border border-slate-300 text-navy font-bold text-center min-w-[4.6875rem] {{ $selectedMonth == $m ? 'bg-sky-100 text-brand-blue border-sky-400' : 'bg-slate-100' }}">
                            {{ strtoupper($monthNames[$m]) }}
                        </th>
                    @endfor
                    <th rowspan="2" class="p-2.5 border border-slate-300 bg-slate-200 text-navy font-bold text-center min-w-[5.3125rem]">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($structure as $key => $meta)
                    @php
                        $isHeader = $meta['is_header'] ?? false;
                        $isHeaderSulteng = $isHeader && ($meta['theme'] ?? '') !== 'blue';
                        $isHeaderLain = $isHeader && ($meta['theme'] ?? '') === 'blue';
                        $isSingle = $meta['single'] ?? false;
                        $rowTotal = $rowTotals[$key] ?? 0;
                        
                        $trClass = $isHeaderSulteng 
                            ? 'bg-emerald-100/80 text-emerald-950 font-bold' 
                            : ($isHeaderLain 
                                ? 'bg-sky-100/80 text-sky-950 font-bold' 
                                : ($isSingle 
                                    ? 'bg-slate-50 font-bold text-navy' 
                                    : 'hover:bg-slate-50/75'));
                    @endphp

                    <tr class="{{ $trClass }}">
                        <!-- NO -->
                        <td class="p-2.5 border border-slate-300 text-center font-bold text-navy">
                            {{ $meta['no'] }}
                        </td>

                        <!-- SUB (A, B, C, D...) -->
                        <td class="p-2.5 border border-slate-300 text-center font-bold text-slate-600">
                            {{ $meta['sub'] }}
                        </td>

                        <!-- JENIS KLAIM -->
                        <td class="p-2.5 border border-slate-300 {{ $isHeader || $isSingle ? 'font-bold' : 'pl-6 text-slate-700 font-medium' }}">
                            @if($isHeader || $isSingle)
                                <span class="uppercase font-bold tracking-wide">{{ $meta['label'] }}</span>
                            @else
                                <span>{{ $meta['label'] }}</span>
                            @endif
                        </td>

                        <!-- 12 MONTH VALUES -->
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $val = $matrix[$key][$m] ?? 0;
                                $isActiveCol = $selectedMonth == $m;
                                $sDate = sprintf('%04d-%02d-01', $selectedYear, $m);
                                $eDate = sprintf('%04d-%02d-%02d', $selectedYear, $m, date('t', strtotime($sDate)));
                                $cellBg = $isActiveCol ? 'bg-sky-50/80 border-x-2 border-sky-400' : '';
                            @endphp
                            <td class="p-2.5 border border-slate-300 text-center tabular-nums {{ $cellBg }}">
                                @if($isHeader)
                                    <span class="text-slate-300 font-normal">-</span>
                                @elseif($val > 0)
                                    <a href="{{ route('jurnal.index', ['tgl_dari' => $sDate, 'tgl_sampai' => $eDate, 'master_cabang_id' => $selectedCabang, 'status' => $selectedStatus]) }}" 
                                       class="inline-block px-1.5 py-0.5 rounded font-bold text-navy hover:bg-sky-100 hover:text-brand-blue underline transition-colors" 
                                       title="Lihat {{ $val }} data {{ $meta['label'] }} bulan {{ $monthNames[$m] }}">
                                        {{ $val }}
                                    </a>
                                @else
                                    <span class="text-slate-500 font-normal">-</span>
                                @endif
                            </td>
                        @endfor

                        <!-- ROW TOTAL -->
                        <td class="p-2.5 border border-slate-300 bg-slate-50 font-bold text-center text-navy">
                            @if($isHeader)
                                <span class="text-slate-300 font-normal">-</span>
                            @else
                                {{ $rowTotal > 0 ? $rowTotal : '-' }}
                            @endif
                        </td>
                    </tr>
                @endforeach

                <!-- FOOTER TOTAL KLAIM -->
                <tr class="bg-amber-100 text-amber-950 font-extrabold text-[0.8125rem] border-t-2 border-amber-500">
                    <td colspan="3" class="p-2.5 border border-slate-300 text-center tracking-wider font-extrabold">
                        TOTAL KLAIM
                    </td>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $cTot = $colTotals[$m] ?? 0;
                        @endphp
                        <td class="p-2.5 border border-slate-300 text-center font-extrabold">
                            {{ $cTot > 0 ? $cTot : '-' }}
                        </td>
                    @endfor
                    <td class="p-2.5 border border-slate-300 text-center text-sm font-extrabold">
                        {{ $grandTotal > 0 ? $grandTotal : '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function selectMonthTab(monthNum) {
        document.getElementById('hiddenBulanInput').value = monthNum;
        document.getElementById('laporanFilterForm').submit();
    }
</script>
@endpush

