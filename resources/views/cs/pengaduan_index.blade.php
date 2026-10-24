@extends('layouts.app')

@section('title', 'Data Pengaduan')
@section('page_title', 'Data Pengaduan Nasabah')
@section('page_subtitle', 'Seluruh pengaduan dari ' . ($user->cabang->nama_cabang ?? 'cabang Anda') . ' beserta status penanganannya di pusat')

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

    @include('partials.pengaduan_stats', ['stats' => $stats, 'urlDasar' => route('cs.pengaduan.index'), 'nilaiSemua' => '', 'statusAktif' => request('status', '')])

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 p-4">
        <form method="GET" action="{{ route('cs.pengaduan.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_220px_auto] gap-3 items-end">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Cari Pengaduan</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nomor pengaduan, nama nasabah, rekening, resi, kartu, HP..." class="w-full h-10 px-3.5 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-700">Status</label>
                <select name="status" class="w-full h-10 px-3 border border-slate-300 rounded-xl text-sm bg-white focus:border-brand-blue focus:ring-3 focus:ring-brand-blue/15 focus:outline-none">
                    <option value="">-- Semua Status --</option>
                    @foreach(\App\Enums\PengaduanStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->ikon() }} {{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="h-10 px-4 rounded-xl bg-navy text-white text-xs font-bold hover:opacity-90 transition-opacity cursor-pointer">Terapkan</button>
                <a href="{{ route('cs.pengaduan.index') }}" class="h-10 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-xs font-bold hover:bg-slate-100 inline-flex items-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
            <div class="text-[0.9375rem] font-bold text-navy">Daftar Pengaduan Cabang</div>
            <div class="text-xs text-slate-500">Total <strong class="text-navy">{{ number_format($pengaduans->total(), 0, ',', '.') }}</strong> data</div>
        </div>

        @if($pengaduans->isEmpty())
            <div class="p-12 text-center text-slate-500">
                <div class="text-4xl mb-2">🔎</div>
                <p class="text-sm font-semibold">Tidak ada pengaduan yang cocok.</p>
                <p class="text-xs mt-1">Ubah kata kunci / filter, atau buat pengaduan baru.</p>
            </div>
        @else
            <div class="p-4">
                <div style="overflow-x: auto;">
                    <table class="custom-table" style="width: 100%; min-width: 900px;">
                        <thead>
                            <tr>
                                <th style="width: 44px; text-align:center;">No</th>
                                <th>Nomor Tiket / Tanggal Kirim</th>
                                <th>Data Nasabah</th>
                                <th>Transaksi</th>
                                <th>Nominal</th>
                                <th class="whitespace-nowrap">Status Pusat</th>
                                <th style="text-align:center; width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pengaduans as $p)
                                <tr>
                                    <td style="text-align:center; color:#64748b; font-weight:600;">{{ ($pengaduans->currentPage() - 1) * $pengaduans->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="font-extrabold text-navy font-mono text-[0.875rem] tracking-wide">{{ $p->nomor_tiket }}</div>
                                        <div class="text-[0.71875rem] text-slate-500 mt-0.5">{{ $p->created_at->translatedFormat('d M Y, H:i') }}</div>
                                        <div class="text-[0.71875rem] text-slate-500">Pelapor: {{ $p->nama_pelapor }}</div>
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
                                        @include('partials.pengaduan_status_badge', ['status' => $p->status])
                                        @if($p->jurnal && $p->jurnal->no_tiket && $p->jurnal->no_tiket !== '-')
                                            <div class="text-[0.71875rem] text-slate-500 mt-1">Tiket: {{ $p->jurnal->no_tiket }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:center; white-space:nowrap;">
                                        <a href="{{ route('cs.pengaduan.show', $p) }}" class="btn btn-secondary btn-sm" style="padding: 5px 11px; font-size: 0.75rem;">Detail</a>
                                        @if($p->bisaDiedit())
                                            <a href="{{ route('cs.pengaduan.edit', $p) }}" class="btn btn-sm" style="padding: 5px 11px; font-size: 0.75rem; background:#fef3c7; color:#b45309; border:1px solid #fde68a; margin-left:4px;">Edit</a>
                                        @endif
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
