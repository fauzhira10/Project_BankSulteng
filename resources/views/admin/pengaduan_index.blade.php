@extends('layouts.app')

@section('title', 'Pengaduan Masuk')
@section('page_title', 'Pengaduan Masuk dari CS Cabang')
@section('page_subtitle', 'Verifikasi pengaduan nasabah yang dikirim Customer Service cabang, lalu masukkan ke Jurnal Keluhan')

@section('content')
@php
    $tabs = [['nilai' => 'semua', 'label' => 'Semua', 'cls' => 'badge-strip']];
    foreach (\App\Enums\PengaduanStatus::cases() as $st) {
        $tabs[] = ['nilai' => $st->value, 'label' => $st->label(), 'cls' => $st->badgeClass()];
    }
    $filterLain = request()->only(['q', 'master_cabang_id', 'tgl_dari', 'tgl_sampai']);
@endphp
<div class="max-w-[78.125rem] mx-auto">

    {{-- Tab status --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 p-2 flex items-center gap-1.5 overflow-x-auto">
        @foreach($tabs as $t)
            @php $aktif = $statusAktif === $t['nilai']; @endphp
            <a href="{{ route('admin.pengaduan.index', array_merge($filterLain, ['status' => $t['nilai']])) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-[0.78125rem] font-semibold whitespace-nowrap transition-colors {{ $aktif ? 'bg-navy text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' }}">
                <span>{{ $t['label'] }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[0.71875rem] font-extrabold {{ $aktif ? 'bg-white/20 text-white' : 'badge ' . $t['cls'] }}" style="padding: 1px 7px;">{{ number_format($counts[$t['nilai']] ?? 0, 0, ',', '.') }}</span>
            </a>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 p-4">
        <form method="GET" action="{{ route('admin.pengaduan.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[1.6fr_1.2fr_1fr_1fr_auto] gap-3 items-end">
            <input type="hidden" name="status" value="{{ $statusAktif }}">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Cari</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nomor, nama nasabah, rekening, resi, pelapor..." class="w-full h-10 px-3.5 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Kantor Cabang</label>
                <select name="master_cabang_id" class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    <option value="">-- Semua Cabang --</option>
                    @foreach($cabangs as $c)
                        <option value="{{ $c->id }}" {{ request('master_cabang_id') == $c->id ? 'selected' : '' }}>{{ !empty($c->kode_cabang) && strtoupper(trim($c->nama_cabang)) !== 'CALL CENTER' ? $c->kode_cabang . ' - ' : '' }}{{ $c->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Dikirim Dari</label>
                <input type="date" name="tgl_dari" value="{{ request('tgl_dari') }}" class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Sampai</label>
                <input type="date" name="tgl_sampai" value="{{ request('tgl_sampai') }}" class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="h-10 px-4 rounded-xl bg-navy text-white text-xs font-bold hover:opacity-90 cursor-pointer">Terapkan</button>
                <a href="{{ route('admin.pengaduan.index', ['status' => $statusAktif]) }}" class="h-10 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-xs font-bold hover:bg-slate-100 inline-flex items-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
            <div class="text-[0.9375rem] font-bold text-navy">
                @if($statusAktif === 'semua') Seluruh Pengaduan @else Pengaduan: {{ \App\Enums\PengaduanStatus::from($statusAktif)->label() }} @endif
            </div>
            <div class="text-xs text-slate-500">Total <strong class="text-navy">{{ number_format($pengaduans->total(), 0, ',', '.') }}</strong> data</div>
        </div>

        @if($pengaduans->isEmpty())
            <div class="p-12 text-center text-slate-500">
                <div class="text-4xl mb-2">📭</div>
                <p class="text-sm font-semibold">Tidak ada pengaduan pada kategori ini.</p>
            </div>
        @else
            <div class="p-4">
                <div style="overflow-x: auto;">
                    <table class="custom-table" style="width: 100%; min-width: 1000px;">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align:center;">No</th>
                                <th>Nomor Tiket / Tanggal Kirim</th>
                                <th>Cabang & Pelapor</th>
                                <th>Data Nasabah</th>
                                <th>Transaksi</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th style="text-align:center; width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pengaduans as $p)
                                <tr>
                                    <td style="text-align:center; color:#64748b; font-weight:600;">{{ ($pengaduans->currentPage() - 1) * $pengaduans->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="font-extrabold text-navy font-mono text-[0.875rem] tracking-wide">{{ $p->nomor_tiket }}</div>
                                        <div class="text-[0.71875rem] text-slate-500 mt-0.5">{{ $p->created_at->translatedFormat('d M Y, H:i') }}</div>
                                        <div class="text-[0.71875rem] text-slate-500">{{ $p->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <div class="font-bold text-slate-800">{{ $p->cabang->nama_cabang ?? '-' }}</div>
                                        @if(!empty($p->cabang->kode_cabang))<div class="text-[0.71875rem] text-slate-500">Kode: {{ $p->cabang->kode_cabang }}</div>@endif
                                        <div class="text-[0.71875rem] text-slate-500 mt-0.5">CS: {{ $p->nama_pelapor }}</div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-slate-800">{{ $p->nama_nasabah }}</div>
                                        <div class="text-[0.71875rem] text-slate-500">Rek: <strong title="Nomor lengkap ada pada rincian pengaduan">{{ \App\Support\Penyamaran::nomor($p->no_rekening) }}</strong> &bull; Resi: <strong>{{ $p->no_resi }}</strong></div>
                                        <div class="text-[0.71875rem] text-slate-500">{{ $p->kategoriLengkap() }}</div>
                                    </td>
                                    <td>
                                        <div class="text-[0.78125rem] font-medium text-slate-700">{{ $p->transaksi->jenis_transaksi ?? '-' }}</div>
                                        <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                            <span class="badge badge-channel">{{ $p->channel }}</span>
                                            <span class="text-[0.71875rem] text-slate-500">{{ $p->tgl_transaksi?->translatedFormat('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td style="white-space:nowrap;" class="font-bold text-emerald-600">{{ $p->nominalRupiah() }}</td>
                                    <td>
                                        @include('partials.pengaduan_status_badge', ['status' => $p->status, 'singkat' => true])
                                        @if($p->jurnal)<div class="text-[0.71875rem] text-slate-500 mt-1">Jurnal #{{ $p->jurnal->id }} &bull; {{ $p->jurnal->status }}</div>@endif
                                    </td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="{{ route('admin.pengaduan.show', $p) }}" class="btn btn-sm" style="padding: 6px 12px; font-size: 0.75rem; background:#0b2f54; color:#fff; border:1px solid #0b2f54;">
                                            {{ $p->status === \App\Enums\PengaduanStatus::Terkirim ? 'Verifikasi' : 'Detail' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials.pagination_sederhana', ['paginator' => $pengaduans])
            </div>
        @endif
    </div>
</div>
@endsection
