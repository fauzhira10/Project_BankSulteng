@extends('layouts.app')

@section('title', 'Dashboard CS')
@section('page_title', 'Dashboard Customer Service')
@section('page_subtitle', 'Pantau status pengaduan nasabah ' . ($user->cabang->nama_cabang ?? 'cabang Anda') . ' yang dikirim ke Admin Pusat')

@section('topbar_action')
    <a href="{{ route('cs.pengaduan.create') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg bg-gradient-to-r from-brand-blue to-navy text-white hover:opacity-95 text-xs font-semibold shadow-sm transition-opacity">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Input Pengaduan Baru</span>
    </a>
@endsection

@section('content')
<div class="max-w-[75rem] mx-auto">

    {{-- Kartu sambutan --}}
    <div class="bg-gradient-to-r from-navy to-brand-blue text-white rounded-2xl p-6 mb-6 shadow-lg shadow-navy/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[0.71875rem] uppercase tracking-wider text-blue-200 font-bold mb-1">Customer Service &bull; {{ $user->cabang ? ($user->cabang->kode_cabang ? $user->cabang->kode_cabang . ' - ' : '') . $user->cabang->nama_cabang : 'Cabang belum ditentukan' }}</div>
            <h2 class="text-xl font-extrabold">Selamat datang, {{ $user->name }}</h2>
            <p class="text-[0.8125rem] text-blue-100 mt-1.5 max-w-2xl leading-relaxed">
                Kirim pengaduan nasabah lengkap dengan lampiran dokumen. Admin Pusat (Divisi IT) akan memverifikasi, memasukkannya ke Jurnal Keluhan, dan status penanganannya dapat Anda pantau di sini.
            </p>
        </div>
        <div class="bg-white/10 border border-white/20 rounded-xl px-5 py-3.5 text-center shrink-0">
            <div class="text-[0.71875rem] uppercase tracking-wider text-blue-200 font-bold">Pengaduan Bulan Ini</div>
            <div class="text-3xl font-extrabold mt-0.5">{{ number_format($bulanIni, 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Statistik --}}
    @include('partials.pengaduan_stats', ['stats' => $stats, 'urlDasar' => route('cs.pengaduan.index'), 'nilaiSemua' => ''])

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Pengaduan terbaru --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="text-[0.9375rem] font-bold text-navy">Pengaduan Terbaru</div>
                        @if(!$terbaru->isEmpty())
                            <span class="px-2 py-0.5 rounded-full text-[0.6875rem] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $terbaru->count() }} Data
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('cs.pengaduan.index') }}" class="text-xs font-semibold text-brand-blue hover:underline inline-flex items-center gap-1">
                        Lihat semua &rarr;
                    </a>
                </div>
                @if($terbaru->isEmpty())
                    <div class="p-10 text-center text-slate-500">
                        <div class="text-4xl mb-2">📭</div>
                        <p class="text-sm font-semibold">Belum ada pengaduan dari cabang Anda.</p>
                        <a href="{{ route('cs.pengaduan.create') }}" class="inline-flex mt-3 items-center gap-2 px-4 py-2 rounded-lg bg-brand-blue text-white text-xs font-bold hover:opacity-90">+ Buat pengaduan pertama</a>
                    </div>
                @else
                    <div class="p-4 sm:p-5">
                        <div class="overflow-x-auto rounded-xl border border-slate-200/90 shadow-2xs bg-white">
                            <table class="w-full text-left border-collapse text-[0.8125rem]" style="min-width: 600px;">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 text-[0.75rem] font-bold">
                                        <th class="px-4 py-3 text-left whitespace-nowrap">Nomor / Tanggal</th>
                                        <th class="px-4 py-3 text-left whitespace-nowrap">Nasabah</th>
                                        <th class="px-4 py-3 text-left whitespace-nowrap">Transaksi</th>
                                        <th class="px-4 py-3 text-left whitespace-nowrap">Nominal</th>
                                        <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
                                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($terbaru as $p)
                                        <tr class="hover:bg-slate-50/60 transition-colors">
                                            <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                                                <div class="font-extrabold text-navy font-mono text-[0.875rem] tracking-wide">{{ $p->nomor_tiket }}</div>
                                                <div class="text-[0.71875rem] text-slate-500 mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                                    <span>{{ $p->created_at->translatedFormat('d M Y, H:i') }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5 align-middle">
                                                <div class="font-semibold text-slate-800">{{ $p->nama_nasabah }}</div>
                                                <div class="text-[0.71875rem] text-slate-500 font-mono mt-0.5">Rek: {{ \App\Support\Penyamaran::nomor($p->no_rekening) }}</div>
                                            </td>
                                            <td class="px-4 py-3.5 align-middle">
                                                <div class="text-[0.78125rem] font-medium text-slate-700 leading-snug">{{ $p->transaksi->jenis_transaksi ?? '-' }}</div>
                                                <span class="badge badge-channel mt-1">{{ $p->channel }}</span>
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap font-bold text-emerald-600 align-middle">
                                                {{ $p->nominalRupiah() }}
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap align-middle">
                                                @include('partials.pengaduan_status_badge', ['status' => $p->status])
                                            </td>
                                            <td class="px-4 py-3.5 text-center whitespace-nowrap align-middle">
                                                <a href="{{ route('cs.pengaduan.show', $p) }}" class="btn btn-secondary btn-sm" style="padding: 5px 12px; font-size: 0.75rem; border-radius: 8px;">Detail</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Perlu perhatian --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <div class="text-[0.9375rem] font-bold text-navy">Pembaruan dari Pusat</div>
                <div class="text-[0.71875rem] text-slate-500 mt-0.5">Pengaduan yang selesai / ditolak dalam 7 hari terakhir</div>
            </div>
            <div class="p-4 space-y-3">
                @forelse($perluPerhatian as $p)
                    <a href="{{ route('cs.pengaduan.show', $p) }}" class="block p-3 rounded-xl border border-slate-200 hover:border-brand-blue/40 hover:bg-sky-50/50 transition-colors">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[0.8125rem] font-extrabold text-navy tracking-wide truncate">{{ $p->nomor_tiket }}</span>
                            @include('partials.pengaduan_status_badge', ['status' => $p->status, 'singkat' => true])
                        </div>
                        <div class="text-[0.78125rem] font-semibold text-slate-700 mt-1 truncate">{{ $p->nama_nasabah }}</div>
                        <div class="text-[0.71875rem] text-slate-500 mt-0.5">Diperbarui {{ $p->updated_at->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="text-center text-slate-500 text-sm py-6">
                        <div class="text-3xl mb-1.5">✅</div>
                        Belum ada pembaruan status dari pusat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Panduan alur --}}
    <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <div class="text-[0.8125rem] font-bold text-navy mb-3">Alur Penanganan Pengaduan</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-[0.75rem] text-slate-600">
            @foreach(\App\Enums\PengaduanStatus::cases() as $i => $st)
                <div class="flex items-start gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="badge {{ $st->badgeClass() }} shrink-0" style="padding: 3px 8px;">{{ $i + 1 }}</span>
                    <div>
                        <div class="font-bold text-slate-800">{{ $st->label() }}</div>
                        <div class="mt-0.5 leading-snug">{{ $st->deskripsi() }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
