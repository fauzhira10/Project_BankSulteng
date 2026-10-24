@extends('layouts.app')

@section('title', 'Detail Pengaduan ' . $pengaduan->nomor_tiket)
@section('page_title', 'Detail Pengaduan Nasabah')
@section('page_subtitle', 'Nomor ' . $pengaduan->nomor_tiket . ' — status penanganan dari Admin Pusat')

@section('topbar_action')
    <a href="{{ route('cs.pengaduan.index') }}" class="inline-flex items-center gap-2 h-[2.375rem] px-3.5 rounded-lg border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold transition-colors">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali ke Daftar</span>
    </a>
@endsection

@section('content')
@php $st = $pengaduan->status; $jurnal = $pengaduan->jurnal; @endphp
<div class="max-w-[68.75rem] mx-auto">

    {{-- Kartu status --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs mb-5 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3 bg-slate-50">
            <div>
                <div class="text-[0.71875rem] uppercase tracking-wider text-slate-500 font-bold">Nomor Tiket</div>
                <div class="text-lg font-extrabold text-navy font-mono">{{ $pengaduan->nomor_tiket }}</div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                @include('partials.pengaduan_status_badge', ['status' => $st])
                @if($pengaduan->bisaDiedit())
                    <a href="{{ route('cs.pengaduan.edit', $pengaduan) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition-colors">✏️ Edit</a>
                    <button type="button" onclick="bukaModalHapus()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors cursor-pointer">🗑️ Hapus</button>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 text-[0.71875rem] font-semibold" title="Data sudah diproses pusat dan tidak dapat diubah">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Terkunci (hanya baca)
                    </span>
                @endif
            </div>
        </div>
        <div class="p-6">
            <p class="text-[0.8125rem] text-slate-600 mb-5 leading-relaxed">{{ $st->deskripsi() }}</p>
            @include('partials.pengaduan_timeline', ['pengaduan' => $pengaduan])
        </div>
    </div>

    {{-- Informasi dari pusat --}}
    @if($pengaduan->catatan_pusat || $jurnal)
        <div class="rounded-2xl border shadow-xs mb-5 overflow-hidden {{ $st === \App\Enums\PengaduanStatus::Ditolak ? 'border-rose-200 bg-rose-50/40' : 'border-sky-200 bg-sky-50/40' }}">
            <div class="px-6 py-4 border-b {{ $st === \App\Enums\PengaduanStatus::Ditolak ? 'border-rose-200' : 'border-sky-200' }} flex items-center gap-2.5">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{{ $st === \App\Enums\PengaduanStatus::Ditolak ? 'text-rose-600' : 'text-sky-700' }}">
                    <path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><path d="M9 9h1M9 13h1M9 17h1M14 9h1M14 13h1M14 17h1"></path>
                </svg>
                <span class="text-[0.9375rem] font-bold text-navy">Informasi dari Admin Pusat</span>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                @if($pengaduan->catatan_pusat)
                    <div class="md:col-span-2 flex flex-col">
                        <span class="text-xs text-slate-500 mb-1">Catatan Pusat</span>
                        <div class="bg-white border border-slate-200 rounded-xl p-3.5 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">{{ $pengaduan->catatan_pusat }}</div>
                    </div>
                @endif
                @if($jurnal)
                    <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor Tiket Jurnal</span><span class="text-sm font-bold text-navy font-mono">{{ $jurnal->no_tiket ?: '-' }}</span></div>
                    <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Status Jurnal Pusat</span><span class="text-sm font-bold text-slate-800">{{ $jurnal->status ?: '-' }}</span></div>
                    <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Tanggal Diterima Pusat</span><span class="text-sm font-semibold text-slate-800">{{ $jurnal->tgl_terima ? \Carbon\Carbon::parse($jurnal->tgl_terima)->translatedFormat('d F Y') : '-' }}</span></div>
                    <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Tanggal Selesai Penanganan</span><span class="text-sm font-semibold text-slate-800">{{ $jurnal->tgl_selesai ? \Carbon\Carbon::parse($jurnal->tgl_selesai)->translatedFormat('d F Y') : 'Belum selesai' }}</span></div>
                    @if($jurnal->permasalahan && $jurnal->permasalahan !== '-')
                        <div class="md:col-span-2 flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Keterangan Permasalahan (Pusat)</span><span class="text-sm font-semibold text-slate-800">{{ $jurnal->permasalahan }}</span></div>
                    @endif
                    @php $logAda = !empty($jurnal->keterangan_log) && trim($jurnal->keterangan_log) !== '-' && trim($jurnal->keterangan_log) !== ''; @endphp
                    <div class="md:col-span-2 flex flex-col">
                        <span class="text-xs text-slate-500 mb-1">Hasil Pemeriksaan Log Transaksi (Pusat)</span>
                        @if($logAda)
                            <div class="bg-white border border-slate-200 rounded-xl p-3.5 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap">{{ $jurnal->keterangan_log }}</div>
                        @else
                            <div class="text-sm text-slate-500 italic">Pusat belum mengisi hasil pemeriksaan log.</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Rincian pengaduan --}}
    @include('partials.pengaduan_detail', ['pengaduan' => $pengaduan])
</div>

@if($pengaduan->bisaDiedit())
<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal-backdrop fixed inset-0 bg-slate-900/65 z-50 backdrop-blur-xs hidden items-center justify-center p-4" id="modalHapus">
    <div class="bg-white rounded-2xl w-full max-w-[27.5rem] shadow-2xl overflow-hidden animate-modal-in">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
            <h3 class="text-lg font-bold text-navy mb-2">Hapus Pengaduan?</h3>
            <p class="text-[0.84375rem] text-slate-600 leading-relaxed">
                Pengaduan <strong class="font-mono">{{ $pengaduan->nomor_tiket }}</strong> atas nama <strong>{{ $pengaduan->nama_nasabah }}</strong> beserta seluruh lampirannya akan dihapus permanen.
            </p>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-center gap-3">
            <button type="button" class="px-5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-[0.84375rem] cursor-pointer min-w-[6.25rem]" onclick="tutupModalHapus()">Batal</button>
            <form action="{{ route('cs.pengaduan.destroy', $pengaduan) }}" method="POST" class="m-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[0.84375rem] shadow-md cursor-pointer min-w-[6.875rem]">Ya, Hapus</button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function bukaModalHapus() { const m = document.getElementById('modalHapus'); if (m) m.classList.add('show'); }
    function tutupModalHapus() { const m = document.getElementById('modalHapus'); if (m) m.classList.remove('show'); }
    window.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModalHapus(); });
    const modalHapusEl = document.getElementById('modalHapus');
    if (modalHapusEl) modalHapusEl.addEventListener('click', function (e) { if (e.target === this) tutupModalHapus(); });
</script>
@endpush
