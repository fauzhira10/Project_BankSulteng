@extends('layouts.app')

@section('title', 'Blockchain Audit')
@section('page_title', 'Blockchain Jejak Audit')
@section('page_subtitle', 'Block explorer — setiap catatan audit disegel ke dalam blok berantai dan dijangkarkan ke Ethereum')

@section('content')
@php
    $utuh = $pemeriksaan['utuh'];
    $adaBlok = $pemeriksaan['jumlah_blok'] > 0;
@endphp
<div class="max-w-[80rem] mx-auto flex flex-col gap-5">

    {{-- Status & Aksi --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col lg:flex-row lg:items-center gap-4">
        <div class="flex items-center gap-4 grow">
            @if(! $adaBlok)
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div>
                    <div class="text-[0.9375rem] font-extrabold text-navy">Blockchain belum dimulai</div>
                    <div class="text-[0.8125rem] text-slate-500">Tekan <strong>Tambang Blok</strong> untuk membuat blok genesis dan menyegel {{ number_format($pemeriksaan['transaksi_tertunda'], 0, ',', '.') }} catatan audit yang sudah ada.</div>
                </div>
            @elseif($utuh)
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                </div>
                <div>
                    <div class="text-[0.9375rem] font-extrabold text-emerald-700">Rantai blok UTUH</div>
                    <div class="text-[0.8125rem] text-slate-500">Seluruh {{ $pemeriksaan['jumlah_blok'] }} blok diperiksa ulang barusan: tautan hash, proof of work, dan merkle root semuanya cocok.</div>
                </div>
            @else
                <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <div>
                    <div class="text-[0.9375rem] font-extrabold text-rose-700">Rantai blok RUSAK — {{ $pemeriksaan['blok_rusak'] }} blok bermasalah</div>
                    <div class="text-[0.8125rem] text-slate-500">Data jejak audit telah diubah di luar aplikasi. Rinciannya ada di bawah.</div>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-2 shrink-0">
            <a href="{{ route('admin.blockchain.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[0.8125rem] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                Periksa Ulang
            </a>
            <form method="POST" action="{{ route('admin.blockchain.tambang') }}" class="m-0 form-proses">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[0.8125rem] font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-colors cursor-pointer disabled:opacity-60" data-teks-proses="Menambang…">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    <span>Tambang Blok</span>
                    @if($pemeriksaan['transaksi_tertunda'] > 0)
                        <span class="bg-white/25 px-1.5 rounded-md text-[0.71875rem]">{{ $pemeriksaan['transaksi_tertunda'] }}</span>
                    @endif
                </button>
            </form>
            <form method="POST" action="{{ route('admin.blockchain.jangkarkan') }}" class="m-0 form-proses">
                @csrf
                <button type="submit" @disabled(! $jangkar['aktif']) class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-[0.8125rem] font-bold text-white bg-navy hover:bg-navy-light shadow-sm transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed" data-teks-proses="Mengirim ke Ethereum…" title="{{ $jangkar['aktif'] ? 'Kirim hash blok yang belum terjangkar ke kontrak Ethereum' : 'Penjangkaran Ethereum belum diaktifkan di .env' }}">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="3"></circle><line x1="12" y1="22" x2="12" y2="8"></line><path d="M5 12H2a10 10 0 0 0 20 0h-3"></path></svg>
                    <span>Jangkarkan ke Ethereum</span>
                    @if($jangkar['aktif'] && $jangkar['belum'] > 0)
                        <span class="bg-white/25 px-1.5 rounded-md text-[0.71875rem]">{{ $jangkar['belum'] }}</span>
                    @endif
                </button>
            </form>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[0.71875rem] font-bold uppercase tracking-wider text-slate-500">Jumlah Blok</div>
            <div class="text-[1.75rem] font-extrabold text-navy leading-tight mt-1">{{ number_format($pemeriksaan['jumlah_blok'], 0, ',', '.') }}</div>
            <div class="text-[0.75rem] text-slate-500">termasuk genesis · maks {{ $ukuranBlok }} catatan/blok</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[0.71875rem] font-bold uppercase tracking-wider text-slate-500">Catatan Tersegel</div>
            <div class="text-[1.75rem] font-extrabold text-emerald-600 leading-tight mt-1">{{ number_format($pemeriksaan['transaksi_tercatat'], 0, ',', '.') }}</div>
            <div class="text-[0.75rem] text-slate-500">catatan audit di dalam blok</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[0.71875rem] font-bold uppercase tracking-wider text-slate-500">Menunggu Ditambang</div>
            <div class="text-[1.75rem] font-extrabold {{ $pemeriksaan['transaksi_tertunda'] > 0 ? 'text-amber-500' : 'text-slate-400' }} leading-tight mt-1">{{ number_format($pemeriksaan['transaksi_tertunda'], 0, ',', '.') }}</div>
            <div class="text-[0.75rem] text-slate-500">otomatis tiap 10 menit · kesulitan {{ $tingkatKesulitan }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
            <div class="text-[0.71875rem] font-bold uppercase tracking-wider text-slate-500">Jangkar Ethereum</div>
            @if($jangkar['aktif'])
                <div class="text-[1.75rem] font-extrabold text-brand-blue leading-tight mt-1">{{ number_format($jangkar['terkirim'], 0, ',', '.') }}</div>
                <div class="text-[0.75rem] text-slate-500">
                    blok tercatat · {{ $jangkar['belum'] }} belum
                    @if($jangkar['konflik'] > 0)
                        · <span class="text-rose-600 font-bold">{{ $jangkar['konflik'] }} konflik</span>
                    @endif
                </div>
            @else
                <div class="text-[1.125rem] font-extrabold text-slate-400 leading-tight mt-2">Nonaktif</div>
                <div class="text-[0.75rem] text-slate-500">isi ETH_* di .env (lihat dokumentasi 8.11)</div>
            @endif
        </div>
    </div>

    @if($jangkar['alamat_kontrak'])
    <div class="bg-sky-50 border border-sky-200 rounded-xl px-4 py-3 text-[0.8125rem] text-sky-900 flex flex-wrap items-center gap-x-2 gap-y-1">
        <strong>Kontrak JangkarAudit:</strong>
        <code class="font-mono text-[0.75rem] break-all">{{ $jangkar['alamat_kontrak'] }}</code>
        @if($jangkar['url_kontrak'])
            <a href="{{ $jangkar['url_kontrak'] }}" target="_blank" rel="noopener noreferrer" class="font-bold text-brand-blue hover:underline">Lihat di Etherscan ↗</a>
        @endif
    </div>
    @endif

    {{-- Temuan --}}
    @if(! $utuh)
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5">
        <h3 class="text-[0.9375rem] font-extrabold text-rose-800 mb-3">Temuan Pemeriksaan</h3>
        <div class="flex flex-col gap-2">
            @foreach($pemeriksaan['blok'] as $hasil)
                @continue($hasil['valid'])
                <div class="bg-white rounded-xl border border-rose-200 p-3">
                    <a href="{{ route('admin.blockchain.show', $hasil['nomor']) }}" class="font-extrabold text-rose-700 hover:underline">Blok #{{ $hasil['nomor'] }}</a>
                    <ul class="list-disc pl-5 mt-1 text-[0.8125rem] text-rose-900">
                        @foreach($hasil['masalah'] as $m)
                            <li>{{ $m }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Gambar Rantai --}}
    @if($rantai->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-[0.9375rem] font-extrabold text-navy">Rantai Blok Terbaru</h3>
            <span class="text-[0.75rem] text-slate-500">Setiap blok menyimpan hash blok sebelumnya</span>
        </div>
        <div class="flex items-stretch gap-0 overflow-x-auto pb-2">
            @foreach($rantai as $b)
                @php $valid = $pemeriksaan['blok'][$b->id]['valid'] ?? false; @endphp
                @if(! $loop->first)
                    <div class="flex items-center px-1 shrink-0 {{ $valid ? 'text-slate-400' : 'text-rose-500' }}">
                        <svg class="w-8 h-6" viewBox="0 0 32 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="12" x2="28" y2="12"></line><polyline points="22 6 28 12 22 18"></polyline></svg>
                    </div>
                @endif
                <a href="{{ route('admin.blockchain.show', $b->nomor) }}" class="shrink-0 w-[13.5rem] rounded-xl border-2 p-3 transition-all hover:-translate-y-0.5 hover:shadow-md {{ $valid ? 'border-emerald-300 bg-emerald-50/50' : 'border-rose-400 bg-rose-50' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-extrabold text-navy text-[0.9375rem]">{{ $b->isGenesis() ? 'Genesis' : 'Blok #'.$b->nomor }}</span>
                        <span class="text-[0.71875rem] font-bold px-1.5 py-0.5 rounded {{ $valid ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">{{ $valid ? 'VALID' : 'RUSAK' }}</span>
                    </div>
                    <dl class="text-[0.71875rem] leading-relaxed">
                        <dt class="text-slate-500">Hash sebelumnya</dt>
                        <dd class="font-mono text-slate-700 truncate">{{ $b->hashPendek($b->hash_sebelumnya) }}</dd>
                        <dt class="text-slate-500 mt-1">Hash blok</dt>
                        <dd class="font-mono font-bold text-navy truncate">{{ $b->hashPendek() }}</dd>
                        <dt class="text-slate-500 mt-1">Isi · Nonce</dt>
                        <dd class="text-slate-700">{{ $b->jumlah_transaksi }} catatan · {{ number_format($b->nonce, 0, ',', '.') }}</dd>
                    </dl>
                    <div class="mt-2">
                        @include('admin.blockchain.partials.lencana_jangkar', ['blok' => $b])
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tabel Blok --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-[0.9375rem] font-extrabold text-navy">Daftar Blok</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-[0.8125rem]">
                <thead class="bg-slate-50 text-slate-600 text-left text-[0.75rem] uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 font-bold">Blok</th>
                        <th class="px-4 py-3 font-bold">Hash Blok</th>
                        <th class="px-4 py-3 font-bold">Merkle Root</th>
                        <th class="px-4 py-3 font-bold text-right">Catatan</th>
                        <th class="px-4 py-3 font-bold text-right">Nonce</th>
                        <th class="px-4 py-3 font-bold">Ditambang</th>
                        <th class="px-4 py-3 font-bold">Integritas</th>
                        <th class="px-4 py-3 font-bold">Ethereum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($daftarBlok as $b)
                        @php $valid = $pemeriksaan['blok'][$b->id]['valid'] ?? false; @endphp
                        <tr class="hover:bg-slate-50 {{ $valid ? '' : 'bg-rose-50/60' }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.blockchain.show', $b->nomor) }}" class="font-extrabold text-brand-blue hover:underline">#{{ $b->nomor }}</a>
                                @if($b->isGenesis())<span class="ml-1 text-[0.71875rem] text-slate-500">genesis</span>@endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[0.75rem] text-navy" title="{{ $b->hash_blok }}">{{ $b->hashPendek() }}</td>
                            <td class="px-4 py-3 font-mono text-[0.75rem] text-slate-600" title="{{ $b->merkle_root }}">{{ $b->hashPendek($b->merkle_root) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ $b->jumlah_transaksi }}</td>
                            <td class="px-4 py-3 text-right font-mono text-[0.75rem]">{{ number_format($b->nonce, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $b->ditambang_pada?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3">
                                @if($valid)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-emerald-100 text-emerald-700">✓ Valid</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[0.71875rem] font-bold bg-rose-100 text-rose-700">✗ Rusak</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">@include('admin.blockchain.partials.lencana_jangkar', ['blok' => $b])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Belum ada blok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($daftarBlok->hasPages())
            <div class="px-5 py-3 border-t border-slate-200">{{ $daftarBlok->links() }}</div>
        @endif
    </div>

    {{-- Penjelasan --}}
    <details class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 group">
        <summary class="cursor-pointer font-extrabold text-navy text-[0.9375rem] list-none flex items-center gap-2">
            <svg class="w-4 h-4 transition-transform group-open:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            Bagaimana blockchain ini bekerja?
        </summary>
        <ol class="mt-4 grid md:grid-cols-2 gap-3 text-[0.8125rem] text-slate-700">
            <li class="bg-slate-50 rounded-xl p-3.5"><strong class="text-navy">1. Transaksi.</strong> Setiap kejadian penting — jurnal dibuat, diubah, dihapus, keputusan pengaduan, pembukaan KTP, login gagal — dicatat sebagai satu catatan jejak audit.</li>
            <li class="bg-slate-50 rounded-xl p-3.5"><strong class="text-navy">2. Merkle root.</strong> Hash tiap catatan dipasangkan dan di-hash bertingkat sampai tersisa satu hash. Mengubah satu catatan saja mengubah akar pohonnya.</li>
            <li class="bg-slate-50 rounded-xl p-3.5"><strong class="text-navy">3. Rantai &amp; proof of work.</strong> Kepala blok (nomor, hash blok sebelumnya, merkle root, waktu) di-hash bersama nonce yang dicari sampai hash-nya diawali {{ $tingkatKesulitan }} angka nol. Memalsukan satu blok berarti menambang ulang semua blok sesudahnya.</li>
            <li class="bg-slate-50 rounded-xl p-3.5"><strong class="text-navy">4. Jangkar Ethereum.</strong> Hash tiap blok dikirim ke kontrak JangkarAudit di Ethereum Sepolia, yang tidak dapat diubah siapa pun. Pemalsuan yang menambang ulang seluruh rantai di server tetap ketahuan karena hash-nya berbeda dari yang tercatat di sana. Hanya hash yang dikirim — tidak ada data nasabah.</li>
        </ol>
    </details>
</div>
@endsection

@push('scripts')
<script>
    // Penjangkaran menunggu konfirmasi jaringan Ethereum (bisa belasan detik per blok).
    document.querySelectorAll('.form-proses').forEach(function (form) {
        form.addEventListener('submit', function () {
            var tombol = form.querySelector('button[type=submit]');
            if (!tombol) return;
            tombol.disabled = true;
            var teks = tombol.querySelector('span');
            if (teks && tombol.dataset.teksProses) teks.textContent = tombol.dataset.teksProses;
        });
    });
</script>
@endpush
