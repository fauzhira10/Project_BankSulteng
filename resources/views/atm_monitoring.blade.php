@extends('layouts.app')

@section('title', 'Monitoring Mesin ATM Bermasalah - PT Bank Sulteng')

@section('content')
<!-- Hero Header -->
<div class="bg-gradient-to-r from-navy-dark via-blue-900 to-sky-700 rounded-2xl p-6 text-white mb-6 shadow-md flex justify-between items-center flex-wrap gap-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white flex items-center gap-2.5 m-0 mb-1.5">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="6" y1="18" x2="6.01" y2="18"></line>
            </svg>
            <span>Monitoring Keluhan Mesin ATM</span>
        </h1>
        <p class="text-xs sm:text-sm text-white/85 m-0">Identifikasi dan evaluasi unit mesin ATM / terminal yang paling sering mengalami kendala transaksi perbankan.</p>
    </div>
    <div class="flex items-center gap-2.5 flex-wrap">
        <span class="bg-white/15 border border-white/30 backdrop-blur-xs px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold text-amber-200">
            Periode: {{ $selectedMonth ? $monthNames[$selectedMonth] . ' ' : '' }}{{ $selectedYear }}
        </span>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-xs">
    <div class="flex justify-between items-center pb-3 mb-4 border-b border-slate-100 flex-wrap gap-2">
        <div class="flex items-center gap-2 text-xs font-bold text-navy uppercase tracking-wider">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <span>Parameter Filter Data Mesin ATM</span>
        </div>
        <div class="text-xs text-slate-500">
            Pilih tahun, bulan, cabang, atau cari kode mesin spesifik
        </div>
    </div>

    <form action="{{ route('atm.index') }}" method="GET" id="atmFilterForm">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1fr_1.2fr_1.5fr_1.5fr_auto] gap-4 items-end">
            <!-- Tahun -->
            <div class="flex flex-col gap-1.5">
                <label for="filterTahun" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                    </svg>
                    <span>Tahun</span>
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
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- Bulan -->
            <div class="flex flex-col gap-1.5">
                <label for="filterBulan" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Bulan</span>
                </label>
                <div class="relative flex items-center">
                    <select name="bulan" id="filterBulan" onchange="this.form.submit()" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        <option value="">-- Semua Bulan --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ $monthNames[$m] }}
                            </option>
                        @endfor
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- Cabang -->
            <div class="flex flex-col gap-1.5">
                <label for="filterCabang" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"></path>
                        <path d="M5 21V7l8-4v18"></path>
                    </svg>
                    <span>Kantor Cabang</span>
                </label>
                <div class="relative flex items-center">
                    <select name="master_cabang_id" id="filterCabang" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        <option value="" data-kode="">-- Seluruh Kantor Cabang --</option>
                        @foreach($cabangs as $c)
                            <option value="{{ $c->id }}" data-kode="{{ $c->kode_cabang ?? '' }}" {{ $selectedCabang == $c->id ? 'selected' : '' }}>
                                {{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>
            </div>

            <!-- Dropdown Filter Terminal / Mesin ATM -->
            <div class="flex flex-col gap-1.5">
                <label for="filterTerminal" class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    </svg>
                    <span>Terminal / Mesin ATM</span>
                </label>
                <div class="relative flex items-center">
                    <select name="q" id="filterTerminal" class="w-full h-[2.625rem] px-3.5 pr-8 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl appearance-none cursor-pointer focus:bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none transition-all">
                        <option value="">-- Seluruh Terminal / Mesin --</option>
                    </select>
                    <div class="absolute right-3 pointer-events-none text-slate-500">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
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
                    <span>Cari</span>
                </button>
                <a href="{{ route('atm.index', ['tahun' => $selectedYear]) }}" class="h-[2.625rem] px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 font-semibold text-xs border border-slate-200 inline-flex items-center gap-1.5 transition-colors" title="Reset Filter">
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

<!-- Table Card -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden mb-8">
    <div class="px-6 py-4.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center flex-wrap gap-3">
        <h2 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
            </svg>
            <span>Daftar Mesin ATM & Jumlah Keluhan</span>
        </h2>
        
        <!-- Pilihan Tampilkan Baris (10, 50, 100) -->
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span>Tampilkan:</span>
                <select onchange="changePerPage(this.value)" class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-semibold bg-white text-slate-800 cursor-pointer outline-none focus:border-brand-blue">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 data</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 data</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 data</option>
                </select>
            </div>
            <div class="text-xs text-slate-500">
                Total: <strong class="text-navy font-bold">{{ number_format($totalMesin, 0, ',', '.') }}</strong> unit mesin
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-xs text-left">
            <thead>
                <tr>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200 w-[3.75rem] text-center">No</th>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200">Kode / Nama Terminal ATM</th>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200">Kantor Cabang Pengelola</th>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200 text-center">Jumlah Keluhan</th>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200 text-right">Total Nominal (Rp)</th>
                    <th class="px-4.5 py-3.5 bg-slate-50 text-slate-600 font-bold text-xs uppercase tracking-wider border-b-2 border-slate-200">Jenis Gangguan Terbanyak</th>
                </tr>
            </thead>
            <tbody>
                @forelse($atms as $atm)
                    <tr class="hover:bg-slate-50/75 transition-colors">
                        <!-- No Urut Biasa -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100 text-center font-semibold text-slate-500">
                            {{ ($atms->currentPage() - 1) * $atms->perPage() + $loop->iteration }}
                        </td>

                        <!-- Kode Terminal -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100">
                            <div class="font-bold text-navy text-[0.9375rem] sm:text-[0.96875rem]">{{ $atm['terminal'] }}</div>
                            @if(!empty($atm['atm_info']['id_luno']))
                                <div class="text-[0.84375rem] text-slate-600 mt-1.5 font-semibold flex items-center gap-2">
                                    <span class="text-slate-500 font-medium text-[0.8125rem]">Kode Mesin:</span>
                                    <span class="font-extrabold font-mono text-navy text-[0.90625rem] px-2.5 py-0.5 rounded-lg bg-sky-50 border border-sky-300 tracking-wide shadow-2xs">{{ $atm['atm_info']['id_luno'] }}</span>
                                </div>
                            @elseif(str_contains(strtoupper($atm['terminal']), 'BANK LAIN'))
                                <div class="text-[0.75rem] text-slate-500 mt-1 font-semibold">Kanal Transaksi Off-Us (ATM Bank Lain)</div>
                            @elseif(str_contains(strtoupper($atm['terminal']), 'MOBILE BANKING') || str_contains(strtoupper($atm['terminal']), 'SMS BANKING'))
                                <div class="text-[0.75rem] text-slate-500 mt-1 font-semibold">Kanal Layanan Perbankan Digital</div>
                            @endif
                        </td>

                        <!-- Kantor Cabang (Redesigned) -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100 min-w-[17.5rem]">
                            @if(!empty($atm['is_multi_cabang']))
                                @php
                                    $branchList = array_values($atm['cabang_list'] ?? []);
                                    $topBranches = array_slice($branchList, 0, 3);
                                    $remainingCount = count($branchList) - count($topBranches);
                                    $maxBranchCount = !empty($branchList) ? max(array_column($branchList, 'count')) : 1;
                                    $rowIndex = ($atms->currentPage() - 1) * $atms->perPage() + $loop->iteration;
                                @endphp
                                <div class="space-y-2 py-0.5">
                                    <!-- Badge Ringkas Multi-Cabang -->
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 font-bold text-[0.71875rem]">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600">
                                                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                                <path d="M2 17l10 5 10-5"></path>
                                                <path d="M2 12l10 5 10-5"></path>
                                            </svg>
                                            <span>Multi-Cabang ({{ count($branchList) }} Cabang)</span>
                                        </div>
                                        <span class="text-[0.71875rem] text-slate-500 font-medium">Top 3 Pelapor:</span>
                                    </div>

                                    <!-- Mini Horizontal Distribution List (Top 3) -->
                                    <div class="space-y-1.5 bg-slate-50/90 p-2 rounded-xl border border-slate-100">
                                        @foreach($topBranches as $tbIdx => $tb)
                                            @php
                                                $barWidth = round(($tb['count'] / max($maxBranchCount, 1)) * 100);
                                                $hasCode = !empty($tb['kode']) && strtoupper(trim($tb['nama'])) !== 'CALL CENTER';
                                            @endphp
                                            <div class="flex items-center gap-2 text-[0.71875rem]">
                                                <div class="flex items-center gap-1.5 min-w-0 max-w-[10.625rem]" title="{{ $hasCode ? '[' . $tb['kode'] . '] ' . $tb['nama'] : $tb['nama'] }}">
                                                    @if($hasCode)
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-200/90 text-slate-700 font-mono font-bold text-[0.71875rem] shrink-0">
                                                            {{ $tb['kode'] }}
                                                        </span>
                                                    @endif
                                                    <span class="font-semibold text-slate-700 truncate text-[0.71875rem]">
                                                        {{ $tb['nama'] }}
                                                    </span>
                                                </div>
                                                <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $tbIdx == 0 ? 'bg-amber-500' : 'bg-brand-blue' }}" style="width: {{ max($barWidth, 8) }}%;"></div>
                                                </div>
                                                <span class="font-bold text-slate-800 text-[0.71875rem] shrink-0 min-w-[1.375rem] text-right">
                                                    {{ $tb['count'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Progressive Disclosure Action -->
                                    <div class="pt-0.5">
                                        <button type="button" onclick='openMultiBranchDetail(@json($atm['terminal']), @json($branchList), {{ $atm['total_keluhan'] }})' class="inline-flex items-center gap-1 text-[0.71875rem] font-bold text-brand-blue hover:text-navy hover:underline cursor-pointer group">
                                            @if($remainingCount > 0)
                                                <span>+{{ $remainingCount }} cabang lainnya (lihat semua)</span>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-y-0.5 transition-transform">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            @else
                                                <span>Lihat rincian cabang</span>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform">
                                                    <polyline points="9 18 15 12 9 6"></polyline>
                                                </svg>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="font-semibold text-slate-700">{{ $atm['cabang_nama'] }}</div>
                                @if(!empty($atm['cabang_kode']) && strtoupper(trim($atm['cabang_nama'])) !== 'CALL CENTER')
                                    <div class="text-[0.71875rem] text-slate-500 mt-0.5">Kode Cabang: {{ $atm['cabang_kode'] }}</div>
                                @endif
                            @endif
                        </td>

                        <!-- Jumlah Keluhan -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-sky-100 text-sky-700 font-extrabold text-xs">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <span>{{ $atm['total_keluhan'] }} Keluhan</span>
                            </span>
                        </td>

                        <!-- Total Nominal -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100 text-right font-bold text-navy tabular-nums">
                            Rp {{ number_format($atm['total_nominal'], 0, ',', '.') }}
                        </td>

                        <!-- Jenis Gangguan -->
                        <td class="px-4.5 py-3.5 border-b border-slate-100">
                            <span class="text-slate-600 text-xs">
                                {{ $atm['dominant_issues'] ?: '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 px-5 text-slate-500">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2.5 opacity-50">
                                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            </svg>
                            <div class="font-semibold text-sm text-slate-700">Tidak Ada Data Keluhan Mesin ATM</div>
                            <div class="text-xs text-slate-500 mt-1">Tidak ditemukan mesin ATM yang sesuai dengan filter pencarian yang Anda tentukan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination Bar -->
    @if($atms->total() > 0)
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center flex-wrap gap-3 text-xs text-slate-500">
            <div>
                Halaman <strong>{{ $atms->currentPage() }}</strong> dari <strong>{{ $atms->lastPage() }}</strong>
                <span class="text-slate-300 mx-1">•</span>
                Menampilkan <strong>{{ $atms->firstItem() ?? 0 }}</strong> sampai <strong>{{ $atms->lastItem() ?? 0 }}</strong> dari <strong>{{ number_format($atms->total(), 0, ',', '.') }}</strong> total mesin ATM
            </div>

            @if($atms->hasPages())
                <nav class="flex items-center gap-1 flex-wrap" aria-label="Navigasi Halaman Data ATM">
                    @php
                        $current = $atms->currentPage();
                        $last = $atms->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                        if ($end - $start < 4) {
                            if ($start == 1) $end = min($last, $start + 4);
                            else if ($end == $last) $start = max(1, $end - 4);
                        }
                    @endphp

                    {{-- Tombol Pertama & Sebelumnya --}}
                    @if ($atms->onFirstPage())
                        <span class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" title="Halaman Pertama">«</span>
                        <span class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" title="Halaman Sebelumnya">‹ Sebelumnya</span>
                    @else
                        <a href="{{ $atms->url(1) }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors" title="Halaman Pertama">«</a>
                        <a href="{{ $atms->previousPageUrl() }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors" title="Halaman Sebelumnya">‹ Sebelumnya</a>
                    @endif

                    {{-- Halaman 1 & Ellipsis jika jauh --}}
                    @if($start > 1)
                        <a href="{{ $atms->url(1) }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors">1</a>
                        @if($start > 2)
                            <span class="inline-flex items-center justify-center min-w-[1.75rem] h-[2.125rem] text-slate-500 font-bold">...</span>
                        @endif
                    @endif

                    {{-- Nomor Halaman Numerik --}}
                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i == $current)
                            <span class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-bold border border-navy bg-navy text-white shadow-xs">{{ $i }}</span>
                        @else
                            <a href="{{ $atms->url($i) }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors">{{ $i }}</a>
                        @endif
                    @endfor

                    {{-- Halaman Terakhir & Ellipsis jika jauh --}}
                    @if($end < $last)
                        @if($end < $last - 1)
                            <span class="inline-flex items-center justify-center min-w-[1.75rem] h-[2.125rem] text-slate-500 font-bold">...</span>
                        @endif
                        <a href="{{ $atms->url($last) }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors">{{ $last }}</a>
                    @endif

                    {{-- Tombol Selanjutnya & Terakhir --}}
                    @if ($atms->hasMorePages())
                        <a href="{{ $atms->nextPageUrl() }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors" title="Halaman Selanjutnya">Selanjutnya ›</a>
                        <a href="{{ $atms->url($last) }}" class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-sky-50 hover:border-sky-300 hover:text-brand-blue transition-colors" title="Halaman Terakhir (Loncat)">»</a>
                    @else
                        <span class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" title="Halaman Selanjutnya">Selanjutnya ›</span>
                        <span class="inline-flex items-center justify-center min-w-[2.125rem] h-[2.125rem] px-2.5 rounded-lg text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-300 cursor-not-allowed" title="Halaman Terakhir">»</span>
                    @endif
                </nav>
            @endif
        </div>
    @endif
</div>

<!-- Modal Distribusi Keluhan Multi-Cabang (Progressive Disclosure) -->
<div id="multiBranchModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-xl max-h-[85vh] flex flex-col overflow-hidden transform transition-all duration-200 scale-95 opacity-0" id="multiBranchModalDialog">
        <!-- Modal Header -->
        <div class="px-6 py-4.5 bg-gradient-to-r from-slate-900 via-slate-800 to-navy text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-400/30 flex items-center justify-center text-sky-300">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-base text-white">Distribusi Keluhan Multi-Cabang</h3>
                        <span id="mbBadgeCount" class="px-2 py-0.5 rounded-full bg-amber-400/20 border border-amber-400/40 text-amber-300 font-bold text-xs">
                            0 Cabang
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Rincian transaksi keluhan untuk <span id="mbTerminalName" class="font-semibold text-white"></span>
                    </p>
                </div>
            </div>
            <button onclick="closeMultiBranchModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <!-- Toolbar Pencarian & Filter -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 space-y-2.5">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </div>
                    <input type="text" id="mbSearchInput" oninput="renderMultiBranchList()" placeholder="Cari nama atau kode cabang..." class="w-full h-9 pl-9 pr-3 text-xs font-medium bg-white border border-slate-300 rounded-xl focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/15 focus:outline-none transition-all placeholder:text-slate-500">
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <select id="mbSortSelect" onchange="renderMultiBranchList()" class="h-9 px-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl focus:border-brand-blue focus:outline-none cursor-pointer">
                        <option value="count-desc">Keluhan Tertinggi</option>
                        <option value="count-asc">Keluhan Terendah</option>
                        <option value="name-asc">Nama Cabang (A-Z)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between text-[0.71875rem] text-slate-500">
                <span id="mbShowingText">Menampilkan 0 cabang pelapor</span>
                <span id="mbTotalText">Total: 0 keluhan</span>
            </div>
        </div>

        <!-- Scrollable List of Branches -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2" id="mbListContainer">
            <!-- Rendered by JS -->
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <div class="text-[0.71875rem] text-slate-500 flex items-center gap-1.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <span>Mini bar proporsional terhadap cabang pelapor tertinggi.</span>
            </div>
            <button onclick="closeMultiBranchModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function changePerPage(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', val);
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }

    // Modal Multi-Cabang Progressive Disclosure
    let activeBranches = [];
    let activeTotalComplaints = 0;

    function openMultiBranchDetail(terminalName, branchList, totalComplaints) {
        activeBranches = branchList || [];
        activeTotalComplaints = totalComplaints || 0;

        document.getElementById('mbTerminalName').textContent = terminalName;
        document.getElementById('mbBadgeCount').textContent = `${activeBranches.length} Cabang`;
        document.getElementById('mbTotalText').innerHTML = `Total: <strong>${activeTotalComplaints}</strong> keluhan`;
        document.getElementById('mbSearchInput').value = '';
        document.getElementById('mbSortSelect').value = 'count-desc';

        renderMultiBranchList();

        const modal = document.getElementById('multiBranchModal');
        const dialog = document.getElementById('multiBranchModalDialog');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            dialog.classList.remove('scale-95', 'opacity-0');
            dialog.classList.add('scale-100', 'opacity-100');
            document.getElementById('mbSearchInput').focus();
        }, 10);
    }

    function closeMultiBranchModal() {
        const modal = document.getElementById('multiBranchModal');
        const dialog = document.getElementById('multiBranchModalDialog');
        dialog.classList.remove('scale-100', 'opacity-100');
        dialog.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    }

    function renderMultiBranchList() {
        const search = (document.getElementById('mbSearchInput').value || '').toLowerCase().trim();
        const sortBy = document.getElementById('mbSortSelect').value;
        const container = document.getElementById('mbListContainer');

        let filtered = activeBranches.filter(b => {
            const name = (b.nama || '').toLowerCase();
            const code = (b.kode || '').toLowerCase();
            return name.includes(search) || code.includes(search);
        });

        if (sortBy === 'count-desc') filtered.sort((a, b) => b.count - a.count);
        else if (sortBy === 'count-asc') filtered.sort((a, b) => a.count - b.count);
        else if (sortBy === 'name-asc') filtered.sort((a, b) => (a.nama || '').localeCompare(b.nama || ''));

        const maxCount = Math.max(...activeBranches.map(b => b.count), 1);
        document.getElementById('mbShowingText').innerHTML = `Menampilkan <strong>${filtered.length}</strong> dari <strong>${activeBranches.length}</strong> cabang pelapor`;

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="py-10 text-center text-slate-500">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2 opacity-50"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <div class="text-xs font-semibold text-slate-600">Tidak ada kantor cabang yang cocok</div>
                    <div class="text-[0.71875rem] text-slate-500 mt-0.5">Coba kata kunci pencarian yang lain</div>
                </div>
            `;
            return;
        }

        let html = '';
        filtered.forEach((branch, idx) => {
            const barWidth = Math.round((branch.count / maxCount) * 100);
            const share = activeTotalComplaints > 0 ? ((branch.count / activeTotalComplaints) * 100).toFixed(1) : 0;
            const rankClass = idx === 0 
                ? 'bg-amber-100 text-amber-800 border border-amber-200' 
                : (idx === 1 ? 'bg-slate-200 text-slate-700' : (idx === 2 ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500'));
            const barGradient = idx === 0 ? 'bg-amber-500' : (idx < 3 ? 'bg-sky-500' : 'bg-slate-400');

            html += `
                <div class="p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 hover:border-slate-200 transition-all flex items-center gap-3">
                    <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-[0.71875rem] shrink-0 ${rankClass}">
                        ${idx + 1}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="font-bold text-xs text-slate-800 truncate">${branch.nama}</span>
                                ${branch.kode && branch.nama.toUpperCase().trim() !== 'CALL CENTER' ? `<span class="text-[0.71875rem] px-2 py-0.5 rounded-md bg-slate-200/85 text-slate-700 font-mono font-bold">Kode: ${branch.kode}</span>` : ''}
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="font-extrabold text-xs text-navy px-2.5 py-0.5 rounded-md bg-sky-50 border border-sky-100">
                                    ${branch.count} <span class="font-normal text-[0.71875rem] text-slate-500">kasus</span>
                                </span>
                            </div>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full ${barGradient}" style="width: ${Math.max(barWidth, 6)}%;"></div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Cascading Dropdown Filter Terminal / Mesin ATM
    const atmsGrouped = @json($atmsGrouped);
    const initialSelectedTerminal = @json($searchKeyword);
    const filterCabang = document.getElementById('filterCabang');
    const filterTerminal = document.getElementById('filterTerminal');

    function isAtmMatch(atm, target) {
        if (!target) return false;
        const t = target.toLowerCase().trim();
        const val = (atm.value || '').toLowerCase().trim();
        const prof = (atm.profil || '').toLowerCase().trim();
        const id = (String(atm.id_luno || '')).toLowerCase().trim();
        return t === val || t === prof || t === id || val.includes(t) || t.includes(prof);
    }

    function populateFilterTerminal(kodeCabang, targetValue = '') {
        if (!filterTerminal) return;
        filterTerminal.innerHTML = '';

        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- Seluruh Terminal / Mesin --';
        filterTerminal.appendChild(defaultOpt);

        if (kodeCabang) {
            const atms = atmsGrouped[kodeCabang] || [];
            if (atms.length > 0) {
                const optGroup = document.createElement('optgroup');
                const namaCabang = atms[0].cabang || '';
                optGroup.label = (namaCabang && namaCabang.toUpperCase() !== 'CALL CENTER') ? `${kodeCabang} - ${namaCabang}` : (namaCabang || `Cabang ${kodeCabang}`);
                atms.forEach(atm => {
                    const opt = document.createElement('option');
                    opt.value = atm.value;
                    opt.textContent = atm.label;
                    if (isAtmMatch(atm, targetValue)) {
                        opt.selected = true;
                    }
                    optGroup.appendChild(opt);
                });
                filterTerminal.appendChild(optGroup);
            }
        } else {
            Object.keys(atmsGrouped).forEach(kode => {
                const atms = atmsGrouped[kode];
                if (atms && atms.length > 0) {
                    const optGroup = document.createElement('optgroup');
                    const namaCabang = atms[0].cabang || '';
                    optGroup.label = (namaCabang && namaCabang.toUpperCase() !== 'CALL CENTER') ? `${kode} - ${namaCabang}` : (namaCabang || `Cabang ${kode}`);
                    atms.forEach(atm => {
                        const opt = document.createElement('option');
                        opt.value = atm.value;
                        opt.textContent = atm.label;
                        if (isAtmMatch(atm, targetValue)) {
                            opt.selected = true;
                        }
                        optGroup.appendChild(opt);
                    });
                    filterTerminal.appendChild(optGroup);
                }
            });

            const generalGroup = document.createElement('optgroup');
            generalGroup.label = 'Channel Non-ATM';
            ['BANK LAIN', 'ATM BANK LAIN', 'MOBILE BANKING', 'SMS BANKING', 'EDC', 'EDC BANK LAIN'].forEach(ch => {
                const opt = document.createElement('option');
                opt.value = ch;
                opt.textContent = ch;
                if (targetValue && targetValue.toUpperCase().trim() === ch) {
                    opt.selected = true;
                }
                generalGroup.appendChild(opt);
            });
            filterTerminal.appendChild(generalGroup);
        }

        if (targetValue && filterTerminal.selectedIndex <= 0) {
            const customOpt = document.createElement('option');
            customOpt.value = targetValue;
            customOpt.textContent = targetValue;
            customOpt.selected = true;
            filterTerminal.appendChild(customOpt);
        }
    }

    if (filterCabang) {
        filterCabang.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            const kode = selectedOpt ? selectedOpt.getAttribute('data-kode') : '';
            populateFilterTerminal(kode);
        });
    }

    window.addEventListener('DOMContentLoaded', function() {
        if (filterCabang) {
            const selectedOpt = filterCabang.options[filterCabang.selectedIndex];
            const kode = selectedOpt ? selectedOpt.getAttribute('data-kode') : '';
            populateFilterTerminal(kode, initialSelectedTerminal);
        }
    });
</script>
@endpush

