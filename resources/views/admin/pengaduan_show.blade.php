@extends('layouts.app')

@section('title', 'Verifikasi Pengaduan ' . $pengaduan->nomor_tiket)
@section('page_title', 'Verifikasi Pengaduan Cabang')
@section('page_subtitle', 'Nomor ' . $pengaduan->nomor_tiket . ' dari ' . $pengaduan->labelCabang())

@section('topbar_action')
    <a href="{{ route('admin.pengaduan.index', ['status' => $pengaduan->status->value]) }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali ke Daftar</span>
    </a>
@endsection

@section('content')
@php
    $st = $pengaduan->status;
    $jurnal = $pengaduan->jurnal;
    $bolehTerima = auth()->user()->can('terima', $pengaduan);
    $bolehTolak = auth()->user()->can('tolak', $pengaduan);
    $bolehJurnal = auth()->user()->can('jurnalkan', $pengaduan);
@endphp
<div class="max-w-[78.125rem] mx-auto">

    {{-- Kartu status + linimasa --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50">
            <div>
                <div class="text-[0.71875rem] uppercase tracking-wider text-slate-500 font-bold">Nomor Tiket</div>
                <div class="text-lg font-extrabold text-navy font-mono">{{ $pengaduan->nomor_tiket }}</div>
            </div>
            @include('partials.pengaduan_status_badge', ['status' => $st])
        </div>
        <div class="p-6">
            @include('partials.pengaduan_timeline', ['pengaduan' => $pengaduan])
        </div>
    </div>

    {{-- Peringatan bila keluhan dengan Nama Nasabah + No. Resi yang sama sudah pernah
         ditangani, ditempatkan sebelum panel tindak lanjut agar terbaca lebih dulu. --}}
    @if(!empty($duplikat))
        <div class="mb-5">
            @include('partials.panel_duplikat', ['duplikat' => $duplikat])
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-5 items-start">
        {{-- Rincian --}}
        <div>
            @include('partials.pengaduan_detail', ['pengaduan' => $pengaduan])
        </div>

        {{-- Panel tindak lanjut --}}
        <div class="xl:sticky xl:top-[5.375rem] space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-navy text-white">
                    <div class="text-[0.875rem] font-bold">Tindak Lanjut Pusat</div>
                    <div class="text-[0.71875rem] text-blue-200 mt-0.5">Status saat ini: {{ $st->label() }}</div>
                </div>
                <div class="p-5 space-y-3">
                    @if($bolehJurnal)
                        <a href="{{ route('jurnal.create', ['pengaduan' => $pengaduan->id]) }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-r from-brand-blue to-navy text-white text-[0.8125rem] font-bold shadow-md hover:opacity-95 transition-opacity">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            {{ $st === \App\Enums\PengaduanStatus::Terkirim ? 'Terima & Input ke Jurnal' : 'Input ke Jurnal Keluhan' }}
                        </a>
                        <p class="text-[0.71875rem] text-slate-500 text-center -mt-1">Form jurnal akan terisi otomatis dari data pengaduan ini.</p>
                    @endif

                    @if($bolehTerima)
                        <form action="{{ route('admin.pengaduan.terima', $pengaduan) }}" method="POST"
                              data-konfirmasi="Terima Pengaduan Ini?"
                              data-ikon="✅"
                              data-warna="emerald"
                              data-aksi="Ya, Terima"
                              data-pesan="Pengaduan {{ $pengaduan->nomor_tiket }} atas nama {{ $pengaduan->nama_nasabah }} akan ditandai sebagai diterima pusat. Setelah ini data terkunci dan CS cabang tidak dapat mengubahnya lagi.">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[0.8125rem] font-bold transition-colors cursor-pointer">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Terima Pengaduan (Tanpa Jurnal Dulu)
                            </button>
                        </form>
                    @endif

                    @if($bolehTolak)
                        <button type="button" onclick="bukaModalTolak()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 text-[0.8125rem] font-bold transition-colors cursor-pointer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            Tolak Pengaduan
                        </button>
                    @endif

                    @if($jurnal)
                        <div class="rounded-xl border border-violet-200 bg-violet-50/60 p-3.5 text-[0.78125rem]">
                            <div class="font-bold text-violet-800 mb-1.5">Tertaut ke Jurnal Keluhan #{{ $jurnal->id }}</div>
                            <div class="text-slate-600">Tiket: <strong class="font-mono">{{ $jurnal->no_tiket ?: '-' }}</strong></div>
                            <div class="text-slate-600">Status jurnal: <strong>{{ $jurnal->status ?: '-' }}</strong></div>
                            <div class="flex gap-2 mt-3">
                                <a href="{{ route('jurnal.preview', $jurnal->id) }}" class="flex-1 text-center px-3 py-2 rounded-lg bg-white border border-violet-300 text-violet-800 font-bold text-[0.75rem] hover:bg-violet-100">Lihat Jurnal</a>
                                <a href="{{ route('jurnal.edit', $jurnal->id) }}" class="flex-1 text-center px-3 py-2 rounded-lg bg-violet-700 text-white font-bold text-[0.75rem] hover:bg-violet-800">Edit Jurnal</a>
                            </div>
                            <p class="text-[0.71875rem] text-slate-500 mt-2.5 leading-snug">Ubah status jurnal menjadi <strong>Done/Success</strong> agar CS melihat "Selesai", atau <strong>Rejected</strong> untuk "Ditolak".</p>
                        </div>
                    @endif

                    @if(!$bolehJurnal && !$bolehTerima && !$bolehTolak && !$jurnal)
                        <div class="text-[0.78125rem] text-slate-500 italic text-center py-2">Tidak ada tindakan yang tersedia untuk status ini.</div>
                    @endif
                </div>
            </div>

            {{-- Informasi verifikasi --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 text-[0.78125rem] space-y-2.5">
                <div class="font-bold text-navy text-[0.8125rem] mb-1">Informasi Verifikasi</div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">Dikirim oleh</span><span class="font-semibold text-right">{{ $pengaduan->user->name ?? $pengaduan->nama_pelapor }}<br><span class="text-[0.71875rem] text-slate-500">&#64;{{ $pengaduan->user->username ?? '-' }}</span></span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">Diterima oleh</span><span class="font-semibold text-right">{{ $pengaduan->penerima->name ?? '-' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">Waktu diterima</span><span class="font-semibold text-right">{{ $pengaduan->diterima_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-slate-500">Terakhir diperbarui</span><span class="font-semibold text-right">{{ $pengaduan->updated_at?->translatedFormat('d M Y, H:i') }}</span></div>
                @if($pengaduan->catatan_pusat)
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-slate-500 mb-1">Catatan Pusat (terlihat oleh CS)</div>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $pengaduan->catatan_pusat }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($bolehTolak)
<!-- MODAL TOLAK -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/65 z-50 backdrop-blur-xs hidden items-center justify-center p-4" id="modalTolak">
    <div class="bg-white rounded-2xl w-full max-w-[32.5rem] shadow-2xl overflow-hidden animate-modal-in">
        <form action="{{ route('admin.pengaduan.tolak', $pengaduan) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl shrink-0">⛔</div>
                    <div>
                        <h3 class="text-lg font-bold text-navy">Tolak Pengaduan</h3>
                        <p class="text-[0.78125rem] text-slate-500">{{ $pengaduan->nomor_tiket }} &bull; {{ $pengaduan->nama_nasabah }}</p>
                    </div>
                </div>
                <label class="font-semibold text-[0.8125rem] text-slate-700 flex items-center gap-1 mb-1.5">Alasan Penolakan <span class="text-rose-600 font-bold">*</span></label>
                <textarea name="catatan_pusat" required minlength="5" rows="5" placeholder="Jelaskan kekurangan / alasan penolakan agar CS cabang dapat menindaklanjuti (mis. foto KTP tidak terbaca, nomor resi tidak sesuai, dsb.)" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white text-slate-800 placeholder-slate-400 focus:border-rose-500 focus:ring-3 focus:ring-rose-500/15 focus:outline-none resize-y">{{ old('catatan_pusat') }}</textarea>
                <p class="text-[0.71875rem] text-slate-500 mt-2">Catatan ini akan ditampilkan kepada CS cabang pada halaman detail pengaduan.</p>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                <button type="button" class="px-5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-[0.84375rem] cursor-pointer" onclick="tutupModalTolak()">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[0.84375rem] shadow-md cursor-pointer">Tolak & Kirim Catatan</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function bukaModalTolak() { const m = document.getElementById('modalTolak'); if (m) { m.classList.add('show'); const t = m.querySelector('textarea'); if (t) setTimeout(() => t.focus(), 50); } }
    function tutupModalTolak() { const m = document.getElementById('modalTolak'); if (m) m.classList.remove('show'); }
    window.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModalTolak(); });
    const modalTolakEl = document.getElementById('modalTolak');
    if (modalTolakEl) modalTolakEl.addEventListener('click', function (e) { if (e.target === this) tutupModalTolak(); });
    @if($errors->has('catatan_pusat')) bukaModalTolak(); @endif
</script>
@endpush
