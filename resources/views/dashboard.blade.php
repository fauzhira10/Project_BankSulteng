@extends('layouts.app')

@section('title', 'Dashboard Utama - E-Jurnal Keluhan PT Bank Sulteng')
@section('page_title', 'Dashboard Utama')
@section('page_subtitle', 'Ringkasan performa penyelesaian keluhan, tren, cabang, & channel transaksi')

@section('topbar_action')
    <a href="{{ route('jurnal.create') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-4 rounded-xl bg-gradient-to-r from-brand-blue to-navy text-white text-xs font-bold shadow-sm shadow-brand-blue/20 hover:brightness-110 transition-all">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Input Jurnal Baru</span>
    </a>
@endsection

@section('content')
{{-- ================= HERO BANNER ================= --}}
<div class="bg-gradient-to-r from-navy-dark via-navy to-brand-blue rounded-2xl p-6 text-white mb-5 shadow-md flex justify-between items-center flex-wrap gap-4 relative overflow-hidden">
    <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-xs text-xs font-semibold text-amber-300 mb-2 border border-white/20">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Sistem E-Jurnal Keluhan Nasabah Aktif</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-white flex items-center gap-2.5 m-0 mb-1 leading-tight">
            Selamat Datang, {{ Auth::user()->name ?? 'Petugas Bank Sulteng' }}!
        </h1>
        <p class="text-xs sm:text-sm text-slate-200 m-0 max-w-2xl">
            Pantau ringkasan penyelesaian keluhan nasabah, performa terminal mesin ATM, serta status transaksi perbankan secara real-time.
        </p>
    </div>

    <div class="relative z-10 flex items-center gap-3">
        <div class="text-right hidden md:block">
            <div class="text-[0.71875rem] uppercase tracking-wider text-blue-200 font-semibold">Total Kasus Aktif</div>
            <div class="text-2xl font-black text-amber-300 leading-tight">{{ number_format($totalKasus, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute right-40 -top-10 w-32 h-32 bg-sky-400/10 rounded-full blur-xl pointer-events-none"></div>
</div>


{{-- ================= 5 KPI CARDS DENGAN SPARKLINE ================= --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    {{-- Card 1: Total Kasus + Sparkline + Delta --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:-translate-y-0.5 hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-1">
            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            @if($previousPeriodStats['delta_kasus'] !== null)
                @php $d = $previousPeriodStats['delta_kasus']; @endphp
                <span class="text-[0.71875rem] font-extrabold px-2 py-0.5 rounded-full {{ $d > 0 ? 'bg-rose-100 text-rose-700' : ($d < 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                    {{ $d > 0 ? '▲' : ($d < 0 ? '▼' : '') }} {{ number_format(abs($d), 1) }}%
                </span>
            @endif
        </div>
        <h4 class="text-[0.71875rem] text-slate-500 font-bold uppercase tracking-wider m-0 mb-0.5">Total Kasus</h4>
        <div class="text-2xl font-black text-navy leading-tight">{{ number_format($totalKasus, 0, ',', '.') }}</div>
        <div id="sparkKasus" class="mt-1 h-10"></div>
    </div>

    {{-- Card 2: Total Nominal + Sparkline + Delta --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:-translate-y-0.5 hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-1">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            @if($previousPeriodStats['delta_nominal'] !== null)
                @php $d = $previousPeriodStats['delta_nominal']; @endphp
                <span class="text-[0.71875rem] font-extrabold px-2 py-0.5 rounded-full {{ $d > 0 ? 'bg-rose-100 text-rose-700' : ($d < 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                    {{ $d > 0 ? '▲' : ($d < 0 ? '▼' : '') }} {{ number_format(abs($d), 1) }}%
                </span>
            @endif
        </div>
        <h4 class="text-[0.71875rem] text-slate-500 font-bold uppercase tracking-wider m-0 mb-0.5">Total Nominal</h4>
        <div class="text-lg font-black text-navy leading-tight" title="Rp {{ number_format($totalNominal, 0, ',', '.') }}">
            @if($totalNominal >= 1000000000)
                Rp {{ number_format($totalNominal / 1000000000, 2, ',', '.') }} M
            @elseif($totalNominal >= 1000000)
                Rp {{ number_format($totalNominal / 1000000, 1, ',', '.') }} Jt
            @else
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            @endif
        </div>
        <div id="sparkNominal" class="mt-1 h-10"></div>
    </div>

    {{-- Kartu 3: Rata-rata lama penanganan, dihitung dari Tanggal Terima sampai Tanggal Selesai --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:-translate-y-0.5 hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-1">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
        </div>
        <h4 class="text-[0.71875rem] text-slate-500 font-bold uppercase tracking-wider m-0 mb-0.5">Rata-rata Selesai</h4>
        <div class="text-2xl font-black text-navy leading-tight">{{ number_format($avgResolutionDays, 1, ',', '.') }} <span class="text-sm font-bold text-slate-500">hari</span></div>
        <div class="text-[0.71875rem] text-slate-500 mt-2">{{ $resolvedCount }} keluhan selesai</div>
    </div>

    {{-- Kartu 4: Tingkat Penyelesaian — bagian keluhan yang sudah tuntas (Done + Success)
         dibanding seluruh keluhan pada periode terpilih. Status kustom yang diketik manual
         tidak dihitung tuntas, sama seperti kartu ringkasan di halaman Data Keluhan. --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:-translate-y-0.5 hover:shadow-md transition-all"
         title="Keluhan berstatus Done atau Success dibagi seluruh keluhan pada periode ini, lalu dikali 100.">
        <div class="flex items-start justify-between mb-1">
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            @if($previousPeriodStats['delta_resolution'] !== null)
                @php $d = $previousPeriodStats['delta_resolution']; @endphp
                <span class="text-[0.71875rem] font-extrabold px-2 py-0.5 rounded-full {{ $d < 0 ? 'bg-rose-100 text-rose-700' : ($d > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                    {{ $d > 0 ? '▲' : ($d < 0 ? '▼' : '') }} {{ number_format(abs($d), 1) }} pp
                </span>
            @endif
        </div>
        <h4 class="text-[0.71875rem] text-slate-500 font-bold uppercase tracking-wider m-0 mb-0.5">Tingkat Penyelesaian</h4>
        <div class="text-2xl font-black text-navy leading-tight">{{ number_format($resolutionRate, 1, ',', '.') }}<span class="text-sm font-bold text-slate-500">%</span></div>
        <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-purple-500 to-emerald-500 rounded-full" style="width: {{ min($resolutionRate, 100) }}%"></div>
        </div>
        <div class="text-[0.71875rem] text-slate-500 mt-1.5">{{ number_format($resolvedCount, 0, ',', '.') }} dari {{ number_format($totalKasus, 0, ',', '.') }} keluhan tuntas</div>
    </div>

    {{-- Card 5: Cabang Aktif --}}
    <div class="bg-gradient-to-br from-navy to-brand-blue border border-navy rounded-2xl p-4 shadow-md text-white col-span-2 lg:col-span-1 hover:-translate-y-0.5 hover:shadow-lg transition-all">
        <div class="flex items-start justify-between mb-1">
            <div class="w-10 h-10 rounded-xl bg-white/20 text-amber-300 flex items-center justify-center shrink-0 backdrop-blur-xs">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l7-4 7 4v14"></path>
                    <path d="M9 21v-8h6v8"></path>
                </svg>
            </div>
        </div>
        <h4 class="text-[0.71875rem] text-blue-100 font-bold uppercase tracking-wider m-0 mb-0.5">Cabang Aktif</h4>
        <div class="text-2xl font-black text-white leading-tight">{{ number_format($totalCabang, 0, ',', '.') }} <span class="text-sm font-bold text-blue-200">unit</span></div>
        <div class="text-[0.71875rem] text-blue-100 mt-2">Kantor cabang & KCP terdaftar</div>
    </div>
</div>

{{-- ================= ROW 2: MONTHLY TREND + STATUS DONUT ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    {{-- Monthly Trend (12 bulan) --}}
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Tren Keluhan 12 Bulan Terakhir</span>
                </h3>
                <p class="text-[0.71875rem] text-slate-500 mt-0.5">Bar biru: jumlah kasus &bull; Garis kuning: total nominal (juta Rp)</p>
            </div>
        </div>
        <div id="monthlyTrendChart" style="min-height: 320px;"></div>
    </div>

    {{-- Status Donut --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                </svg>
                <span>Distribusi Status</span>
            </h3>
        </div>
        <div id="statusDonutChart" style="min-height: 320px;"></div>
    </div>
</div>

{{-- ================= ROW 3: CHANNEL BREAKDOWN ================= --}}
<div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs mb-6">
    <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100 flex-wrap gap-2">
        <div>
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="20" x2="12" y2="10"></line>
                    <line x1="18" y1="20" x2="18" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="16"></line>
                </svg>
                <span>Breakdown Keluhan per Channel Transaksi</span>
            </h3>
            <p class="text-[0.71875rem] text-slate-500 mt-0.5">Identifikasi channel dengan kontribusi keluhan tertinggi</p>
        </div>
        <div class="text-[0.71875rem] text-slate-500">Total: <strong class="text-navy">{{ $channelBreakdown->count() }}</strong> channel aktif</div>
    </div>
    @if($channelBreakdown->isEmpty())
        <div class="text-center py-8 text-xs text-slate-500">Belum ada data keluhan pada periode ini.</div>
    @else
        <div id="channelBreakdownChart" style="min-height: 300px;"></div>
    @endif
</div>

{{-- ================= ROW 4: TOP CABANG + TOP ATM ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    {{-- Top 10 Cabang Bermasalah --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l7-4 7 4v14"></path>
                </svg>
                <span>Top 10 Cabang Bermasalah</span>
            </h3>
            <a href="{{ route('jurnal.index') }}" class="text-[0.71875rem] font-bold text-brand-blue hover:underline">Detail &rsaquo;</a>
        </div>
        <div class="flex flex-col gap-1.5">
            @php $maxCabang = $branchRanking->max('total_kasus') ?: 1; @endphp
            @forelse($branchRanking as $idx => $cab)
                @php $pct = ($cab->total_kasus / $maxCabang) * 100; @endphp
                <a href="{{ route('jurnal.index') }}?master_cabang_id={{ $cab->id }}" class="group block p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <span class="w-5 h-5 rounded-md bg-navy text-white text-[0.71875rem] font-bold flex items-center justify-center shrink-0">{{ $idx + 1 }}</span>
                            <span class="font-mono text-[0.71875rem] text-slate-500 shrink-0">{{ $cab->kode_cabang ?: '---' }}</span>
                            <span class="text-xs font-bold text-slate-800 truncate group-hover:text-brand-blue">{{ $cab->nama_cabang }}</span>
                        </div>
                        <span class="text-[0.71875rem] font-extrabold text-navy shrink-0">{{ $cab->total_kasus }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-sky-400 to-brand-blue rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-[0.71875rem] text-slate-500 font-semibold tabular-nums shrink-0">Rp {{ number_format($cab->total_nominal / 1000000, 1, ',', '.') }} Jt</span>
                    </div>
                </a>
            @empty
                <div class="text-center py-8 text-xs text-slate-500">Belum ada data keluhan per cabang.</div>
            @endforelse
        </div>
    </div>

    {{-- Top 10 Mesin ATM Bermasalah --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                </svg>
                <span>Top 10 Mesin ATM Bermasalah</span>
            </h3>
            <a href="{{ route('atm.index') }}" class="text-[0.71875rem] font-bold text-brand-blue hover:underline">Detail &rsaquo;</a>
        </div>
        <div class="flex flex-col gap-1.5">
            @php $maxAtm = $topAtms->max('total_keluhan') ?: 1; @endphp
            @forelse($topAtms as $idx => $atm)
                @php
                    $pct = ($atm->total_keluhan / $maxAtm) * 100;
                    $info = \App\Models\MasterAtm::findAtmInfo($atm->terminal_transaksi);
                @endphp
                <a href="{{ route('atm.index') }}?q={{ urlencode($atm->terminal_transaksi) }}" class="group block p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all">
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <span class="w-5 h-5 rounded-md bg-amber-500 text-white text-[0.71875rem] font-bold flex items-center justify-center shrink-0">{{ $idx + 1 }}</span>
                            <div class="overflow-hidden">
                                <div class="text-xs font-bold text-slate-800 truncate group-hover:text-amber-700">{{ $atm->terminal_transaksi }}</div>
                                <div class="text-[0.71875rem] text-slate-500 truncate">{{ $info['lokasi'] ?? '-' }} &bull; {{ $info['cabang'] ?? '-' }}</div>
                            </div>
                        </div>
                        <span class="text-[0.71875rem] font-extrabold text-amber-700 shrink-0">{{ $atm->total_keluhan }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-[0.71875rem] text-slate-500 font-semibold tabular-nums shrink-0">Rp {{ number_format($atm->total_nominal / 1000000, 1, ',', '.') }} Jt</span>
                    </div>
                </a>
            @empty
                <div class="text-center py-8 text-xs text-slate-500">Belum ada data keluhan mesin ATM.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- ================= ROW 5: RECENT + BACKLOG ================= --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
    {{-- Recent Complaints --}}
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100 flex-wrap gap-2">
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span>Laporan Keluhan Terbaru</span>
            </h3>
            <a href="{{ route('jurnal.index') }}" class="text-[0.71875rem] font-bold text-brand-blue hover:underline">Semua Data &rsaquo;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs text-left">
                <thead>
                    <tr>
                        <th class="pb-2.5 text-slate-500 font-bold uppercase tracking-wider text-[0.71875rem]">Nasabah</th>
                        <th class="pb-2.5 text-slate-500 font-bold uppercase tracking-wider text-[0.71875rem]">Jenis Transaksi</th>
                        <th class="pb-2.5 text-slate-500 font-bold uppercase tracking-wider text-[0.71875rem] text-right">Nominal</th>
                        <th class="pb-2.5 text-slate-500 font-bold uppercase tracking-wider text-[0.71875rem] text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentJurnals as $jurnal)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 pr-3">
                                <div class="font-bold text-navy text-xs">{{ $jurnal->nama_nasabah }}</div>
                                <div class="text-[0.71875rem] text-slate-500">Resi: {{ $jurnal->no_resi }} &bull; {{ $jurnal->masterCabang->nama_cabang ?? '-' }}</div>
                            </td>
                            <td class="py-3 pr-3">
                                <div class="font-semibold text-slate-700">{{ $jurnal->masterTransaksi->jenis_transaksi ?? '-' }}</div>
                                <div class="text-[0.71875rem] text-slate-500">{{ $jurnal->terminal_transaksi ?: 'Non-ATM' }}</div>
                            </td>
                            <td class="py-3 pr-3 text-right font-bold text-slate-800 tabular-nums">
                                Rp {{ number_format($jurnal->nominal_transaksi, 0, ',', '.') }}
                            </td>
                            <td class="py-3 text-center">
                                @php $st = strtolower($jurnal->status); @endphp
                                @if($st == 'success')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold bg-emerald-100 text-emerald-700">Success</span>
                                @elseif($st == 'menunggu')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold bg-amber-100 text-amber-700">Menunggu</span>
                                @elseif($st == 'done')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold bg-sky-100 text-sky-700">Done</span>
                                @elseif($st == 'rejected')
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold bg-rose-100 text-rose-700">Rejected</span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold bg-slate-100 text-slate-700">{{ $jurnal->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-xs text-slate-500">
                                Belum ada data keluhan nasabah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Backlog Perlu Tindak Lanjut --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3.5 mb-3.5 border-b border-slate-100">
            <h3 class="text-sm font-bold text-navy flex items-center gap-2 m-0">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Backlog &gt; 7 Hari</span>
            </h3>
            @if($pendingOverdue->isNotEmpty())
                <span class="text-[0.71875rem] font-extrabold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">{{ $pendingOverdue->count() }} URGENT</span>
            @endif
        </div>
        <div class="flex flex-col gap-2">
            @forelse($pendingOverdue as $backlog)
                <a href="{{ route('jurnal.index') }}?q={{ urlencode($backlog->no_resi) }}" class="group block p-2.5 rounded-xl bg-rose-50/50 border border-rose-100 hover:bg-rose-50 hover:border-rose-200 transition-all">
                    <div class="flex items-center justify-between mb-1">
                        <div class="text-xs font-bold text-slate-800 truncate group-hover:text-rose-700">{{ $backlog->nama_nasabah }}</div>
                        <span class="text-[0.71875rem] font-extrabold text-rose-700 shrink-0 ml-2">{{ $backlog->days_pending }} hari</span>
                    </div>
                    <div class="text-[0.71875rem] text-slate-500 truncate">{{ $backlog->masterCabang->nama_cabang ?? '-' }} &bull; Rp {{ number_format($backlog->nominal_transaksi, 0, ',', '.') }}</div>
                    <div class="text-[0.71875rem] text-slate-500 mt-0.5">Terima: {{ \Carbon\Carbon::parse($backlog->tgl_terima)->locale('id')->translatedFormat('d M Y') }}</div>
                </a>
            @empty
                <div class="text-center py-8">
                    <div class="w-12 h-12 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="text-xs font-bold text-slate-700">Semua kasus tertangani!</div>
                    <div class="text-[0.71875rem] text-slate-500 mt-0.5">Tidak ada backlog &gt; 7 hari.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ================= QUICK ACCESS FOOTER ================= --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
    <a href="{{ route('jurnal.create') }}" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex items-center gap-3 group">
        <div class="w-11 h-11 rounded-xl bg-sky-100 group-hover:bg-brand-blue text-sky-600 group-hover:text-white flex items-center justify-center shrink-0 transition-all">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </div>
        <div>
            <div class="text-xs font-extrabold text-navy">Input Keluhan Baru</div>
            <div class="text-[0.71875rem] text-slate-500">Catat keluhan nasabah baru</div>
        </div>
    </a>
    <a href="{{ route('laporan.index') }}" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex items-center gap-3 group">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 group-hover:bg-emerald-600 text-emerald-600 group-hover:text-white flex items-center justify-center shrink-0 transition-all">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
        </div>
        <div>
            <div class="text-xs font-extrabold text-navy">Laporan Rekap Bulanan</div>
            <div class="text-[0.71875rem] text-slate-500">Matriks 12 bulan &amp; export Excel</div>
        </div>
    </a>
    <a href="{{ route('atm.index') }}" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex items-center gap-3 group">
        <div class="w-11 h-11 rounded-xl bg-amber-100 group-hover:bg-amber-600 text-amber-600 group-hover:text-white flex items-center justify-center shrink-0 transition-all">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
            </svg>
        </div>
        <div>
            <div class="text-xs font-extrabold text-navy">Monitoring ATM</div>
            <div class="text-[0.71875rem] text-slate-500">Peringkat &amp; drill-down mesin</div>
        </div>
    </a>
</div>
@endsection

@push('styles')
<style>
    /* Prevent horizontal overflow of ApexCharts tooltip */
    .apexcharts-tooltip { z-index: 100 !important; }
</style>
@endpush

@push('scripts')
<script>
    // === DATA (dari controller Laravel) ===
    const CHART_DATA = {
        sparkKasus:   @json($sparklineKasus),
        sparkNominal: @json($sparklineNominal),
        sparkLabels:  @json($sparklineLabels),
        monthlyTrend: @json($monthlyTrend),
        statusStats:  @json($statusStats),
        channels:     @json($channelBreakdown),
    };

    // Helper formatter Rupiah
    function fmtRupiah(v) {
        if (!v && v !== 0) return 'Rp 0';
        if (v >= 1000000000) return 'Rp ' + (v/1000000000).toFixed(2).replace('.', ',') + ' M';
        if (v >= 1000000)    return 'Rp ' + (v/1000000).toFixed(1).replace('.', ',') + ' Jt';
        if (v >= 1000)       return 'Rp ' + (v/1000).toFixed(0) + ' Rb';
        return 'Rp ' + v.toLocaleString('id-ID');
    }

    // Init all charts after ApexCharts loaded + DOM ready
    function initCharts() {
        if (typeof ApexCharts === 'undefined') { setTimeout(initCharts, 100); return; }

        // ===== SPARKLINE 1: Total Kasus =====
        new ApexCharts(document.querySelector('#sparkKasus'), {
            chart: { type: 'area', height: 40, sparkline: { enabled: true }, animations: { enabled: true, speed: 500 } },
            series: [{ name: 'Kasus', data: CHART_DATA.sparkKasus }],
            stroke: { curve: 'smooth', width: 2 },
            fill:   { opacity: 0.3, type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
            colors: ['#0284c7'],
            tooltip: {
                fixed: { enabled: false }, x: { show: false },
                y: { title: { formatter: () => '' }, formatter: (v, opts) => v + ' kasus (' + CHART_DATA.sparkLabels[opts.dataPointIndex] + ')' },
                marker: { show: false }
            }
        }).render();

        // ===== SPARKLINE 2: Total Nominal =====
        new ApexCharts(document.querySelector('#sparkNominal'), {
            chart: { type: 'area', height: 40, sparkline: { enabled: true }, animations: { enabled: true, speed: 500 } },
            series: [{ name: 'Nominal', data: CHART_DATA.sparkNominal }],
            stroke: { curve: 'smooth', width: 2 },
            fill:   { opacity: 0.3, type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
            colors: ['#10b981'],
            tooltip: {
                fixed: { enabled: false }, x: { show: false },
                y: { title: { formatter: () => '' }, formatter: (v, opts) => fmtRupiah(v) + ' (' + CHART_DATA.sparkLabels[opts.dataPointIndex] + ')' },
                marker: { show: false }
            }
        }).render();

        // ===== MONTHLY TREND (bar + line dual axis) =====
        const trendLabels  = CHART_DATA.monthlyTrend.map(m => m.label);
        const trendCounts  = CHART_DATA.monthlyTrend.map(m => m.count);
        const trendNominal = CHART_DATA.monthlyTrend.map(m => Number((m.nominal / 1000000).toFixed(2))); // dalam juta Rp

        new ApexCharts(document.querySelector('#monthlyTrendChart'), {
            chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [
                { name: 'Jumlah Kasus', type: 'column', data: trendCounts },
                { name: 'Total Nominal', type: 'line', data: trendNominal },
            ],
            stroke: { width: [0, 3], curve: 'smooth' },
            colors: ['#0066cc', '#f59e0b'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            markers: { size: [0, 5], strokeWidth: 2, hover: { size: 7 } },
            dataLabels: { enabled: false },
            xaxis: { categories: trendLabels, labels: { style: { fontSize: '10.5px', colors: '#64748b' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: [
                { title: { text: 'Kasus', style: { fontSize: '11px', color: '#0066cc' } }, labels: { style: { fontSize: '10.5px', colors: '#64748b' } } },
                { opposite: true, title: { text: 'Nominal (Juta Rp)', style: { fontSize: '11px', color: '#f59e0b' } }, labels: { style: { fontSize: '10.5px', colors: '#64748b' }, formatter: v => v.toFixed(1) } },
            ],
            grid: { borderColor: '#e2e8f0', strokeDashArray: 3 },
            legend: { fontSize: '11px', markers: { width: 10, height: 10, radius: 3 }, itemMargin: { horizontal: 12 } },
            tooltip: {
                shared: true, intersect: false,
                y: [
                    { formatter: v => v + ' kasus' },
                    { formatter: v => 'Rp ' + v.toLocaleString('id-ID') + ' Juta' },
                ]
            }
        }).render();

        // ===== STATUS DONUT =====
        const st = CHART_DATA.statusStats;
        const statusSeries = [st.menunggu, st.success, st.done, st.rejected];
        const statusTotal  = statusSeries.reduce((a, b) => a + b, 0);
        new ApexCharts(document.querySelector('#statusDonutChart'), {
            chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
            series: statusSeries,
            labels: ['Menunggu', 'Success', 'Done', 'Rejected'],
            colors: ['#f59e0b', '#10b981', '#0284c7', '#e11d48'],
            stroke: { width: 2, colors: ['#fff'] },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name:  { fontSize: '11px', color: '#64748b', fontWeight: 700, offsetY: 20 },
                            value: { fontSize: '28px', color: '#0b2f54', fontWeight: 800, offsetY: -14, formatter: v => v },
                            total: {
                                show: true, label: 'Total Kasus', color: '#64748b',
                                fontSize: '11px', fontWeight: 700,
                                formatter: () => statusTotal.toLocaleString('id-ID')
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 }, formatter: (val) => val.toFixed(0) + '%' },
            legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 3 }, itemMargin: { horizontal: 8, vertical: 4 } },
            tooltip: { y: { formatter: v => v + ' kasus' } },
            noData: { text: 'Belum ada data status.', style: { fontSize: '12px', color: '#94a3b8' } }
        }).render();

        // ===== CHANNEL BREAKDOWN (horizontal bar) =====
        const chEl = document.querySelector('#channelBreakdownChart');
        if (chEl && CHART_DATA.channels.length) {
            const channelLabels  = CHART_DATA.channels.map(c => c.channel);
            const channelCounts  = CHART_DATA.channels.map(c => c.count);
            const channelNominal = CHART_DATA.channels.map(c => c.nominal);
            const palette = ['#0066cc', '#f59e0b', '#10b981', '#8b5cf6', '#e11d48', '#0891b2', '#ea580c', '#059669', '#7c3aed', '#db2777', '#0284c7', '#65a30d'];
            new ApexCharts(chEl, {
                chart: { type: 'bar', height: Math.max(300, channelLabels.length * 42), toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Jumlah Kasus', data: channelCounts }],
                plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 6, dataLabels: { position: 'top' } } },
                colors: palette.slice(0, channelLabels.length),
                dataLabels: { enabled: true, textAnchor: 'start', offsetX: 4, style: { fontSize: '11px', fontWeight: 700, colors: ['#0b2f54'] }, formatter: (val, opts) => val + ' kasus  •  ' + fmtRupiah(channelNominal[opts.dataPointIndex]) },
                xaxis: { categories: channelLabels, labels: { style: { fontSize: '10.5px', colors: '#64748b' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { style: { fontSize: '11px', fontWeight: 600, colors: '#0b2f54' } } },
                grid: { borderColor: '#e2e8f0', strokeDashArray: 3, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
                legend: { show: false },
                tooltip: {
                    y: { formatter: (v, opts) => v + ' kasus (Rp ' + channelNominal[opts.dataPointIndex].toLocaleString('id-ID') + ')' },
                }
            }).render();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
</script>
@endpush
