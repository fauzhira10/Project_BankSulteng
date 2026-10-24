{{-- Rincian pengaduan (hanya baca). Pemakaian: @include('partials.pengaduan_detail', ['pengaduan' => $pengaduan]) --}}
@php
    $p = $pengaduan;
    $atmInfo = \App\Models\MasterAtm::findAtmInfo($p->terminal_transaksi);
    $lampiranPerJenis = $p->lampirans->groupBy('jenis');
    $jenisLampiran = config('pengaduan.jenis_lampiran', []);
@endphp

<div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-6 space-y-7">

        {{-- 1. Data Pelapor --}}
        <div>
            <h3 class="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                1. Data Pelapor (Customer Service)
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3.5">
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nama Pelapor</span><span class="text-sm font-semibold text-slate-800">{{ $p->nama_pelapor }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Asal Cabang</span><span class="text-sm font-semibold text-slate-800">{{ $p->labelCabang() }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Tanggal Kirim</span><span class="text-sm font-semibold text-slate-800">{{ $p->created_at?->translatedFormat('d F Y, H:i') }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Kategori</span><span class="text-sm font-semibold text-slate-800">{{ $p->kategori ?: '-' }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Sub Kategori</span><span class="text-sm font-semibold text-slate-800">{{ $p->sub_kategori ?: '-' }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Sub Kategori 2</span><span class="text-sm font-semibold text-slate-800">{{ $p->sub_kategori_2 ?: '-' }}</span></div>
            </div>
        </div>

        {{-- 2. Data Nasabah --}}
        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><circle cx="8" cy="11" r="2.5"></circle><path d="M14 10h5M14 14h5M4 18c1-2 3-2.5 4-2.5s3 .5 4 2.5"></path></svg>
                2. Data Nasabah
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3.5">
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nama Nasabah</span><span class="text-sm font-bold text-navy">{{ $p->nama_nasabah }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor HP</span><span class="text-sm font-semibold text-slate-800">{{ $p->no_hp }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor KTP (NIK)</span><span class="text-sm font-semibold text-slate-800 font-mono">{{ $p->no_ktp }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor Rekening</span><span class="text-sm font-semibold text-slate-800 font-mono">{{ $p->no_rekening }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor Kartu ATM</span><span class="text-sm font-semibold text-slate-800 font-mono">{{ $p->no_kartu ?: '-' }}</span></div>
            </div>
        </div>

        {{-- 3. Data Transaksi --}}
        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                3. Data Transaksi Bermasalah
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3.5">
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Jenis Transaksi</span><span class="text-sm font-semibold text-slate-800">{{ $p->transaksi->jenis_transaksi ?? '-' }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Prinsipal / Channel</span><span><span class="badge badge-channel">{{ $p->channel }}</span></span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nomor Resi / Trace</span><span class="text-sm font-bold text-brand-blue font-mono">{{ $p->no_resi }}</span></div>
                <div class="flex flex-col">
                    <span class="text-xs text-slate-500 mb-0.5">Terminal Transaksi</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $atmInfo['profil'] ?? ($p->terminal_transaksi ?: '-') }}</span>
                    @if(!empty($atmInfo['id_luno']))
                        <span class="text-[0.71875rem] font-mono text-slate-500 mt-0.5">ID Mesin: {{ $atmInfo['id_luno'] }} &bull; {{ $atmInfo['lokasi'] ?? '' }}</span>
                    @endif
                </div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Nominal Transaksi</span><span class="text-[0.9375rem] font-bold text-emerald-600">{{ $p->nominalRupiah() }}</span></div>
                <div class="flex flex-col"><span class="text-xs text-slate-500 mb-0.5">Tanggal Transaksi</span><span class="text-sm font-semibold text-slate-800">{{ $p->tgl_transaksi?->translatedFormat('d F Y') }}</span></div>
            </div>
        </div>

        {{-- 4. Lampiran --}}
        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                4. Lampiran Dokumen
                <span class="text-[0.71875rem] font-semibold text-slate-500 normal-case tracking-normal">({{ $p->lampirans->count() }} berkas, format asli dari cabang)</span>
            </h3>
            @if($p->lampirans->isEmpty())
                <div class="text-sm text-slate-500 italic bg-slate-50 border border-dashed border-slate-200 rounded-xl p-4">Tidak ada lampiran.</div>
            @else
                <div class="space-y-4">
                    @foreach($jenisLampiran as $jenis => $cfg)
                        @php $berkas = $lampiranPerJenis->get($jenis, collect()); @endphp
                        @continue($berkas->isEmpty())
                        <div>
                            <div class="text-[0.75rem] font-bold text-slate-700 mb-1.5">{{ $cfg['label'] ?? $jenis }} <span class="text-slate-500 font-semibold">({{ $berkas->count() }} berkas)</span></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                @foreach($berkas as $l)
                                    <a href="{{ route('pengaduan.lampiran.show', [$p, $l]) }}" target="_blank" class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-sky-50 hover:border-brand-blue/40 transition-colors group">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 font-extrabold text-[0.71875rem] tracking-wide {{ $l->adalahGambar() ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-600' }}">{{ $l->labelFormat() }}</div>
                                        <div class="min-w-0 grow">
                                            <div class="text-[0.78125rem] font-bold text-navy group-hover:text-brand-blue truncate" title="{{ $l->nama_asli }}">{{ $l->nama_asli }}</div>
                                            <div class="text-[0.71875rem] text-slate-500 truncate">
                                                {{ $l->adalahGambar() ? 'Gambar' : 'Dokumen PDF' }} &bull; {{ $l->ukuranTerbaca() }} &bull; diunggah {{ $l->created_at?->translatedFormat('d M Y') }}
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-500 group-hover:text-brand-blue shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 5. Kronologi --}}
        <div class="border-t border-slate-100 pt-6">
            <h3 class="text-xs font-bold text-brand-blue uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                5. Kronologi Detail Keluhan
            </h3>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $p->kronologi }}</div>
        </div>
    </div>
</div>
