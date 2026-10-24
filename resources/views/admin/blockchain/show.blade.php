@extends('layouts.app')

@section('title', 'Blok #'.$blok->nomor)
@section('page_title', ($blok->isGenesis() ? 'Blok Genesis' : 'Blok #'.$blok->nomor).' — Blockchain Jejak Audit')
@section('page_subtitle', 'Kepala blok, pohon Merkle, isi catatan audit, dan jangkar Ethereum')

@section('content')
@php
    $valid = $pemeriksaan['valid'];
    $awalanNol = str_repeat('0', $blok->tingkat_kesulitan);
    $warnaStatus = [
        'utuh' => 'bg-emerald-100 text-emerald-700',
        'diubah' => 'bg-rose-500 text-white',
        'dihapus' => 'bg-rose-500 text-white',
        'disisipkan' => 'bg-amber-500 text-white',
    ];
@endphp
<div class="max-w-[80rem] mx-auto flex flex-col gap-5">

    {{-- Navigasi --}}
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('admin.blockchain.index') }}" class="inline-flex items-center gap-1.5 text-[0.8125rem] font-bold text-brand-blue hover:underline">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            Kembali ke Block Explorer
        </a>
        <div class="flex gap-2">
            @if($sebelumnya)
                <a href="{{ route('admin.blockchain.show', $sebelumnya->nomor) }}" class="px-3 py-1.5 rounded-lg text-[0.8125rem] font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">← Blok #{{ $sebelumnya->nomor }}</a>
            @endif
            @if($berikutnya)
                <a href="{{ route('admin.blockchain.show', $berikutnya->nomor) }}" class="px-3 py-1.5 rounded-lg text-[0.8125rem] font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Blok #{{ $berikutnya->nomor }} →</a>
            @endif
        </div>
    </div>

    {{-- Status --}}
    @if($valid)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-extrabold shrink-0">✓</span>
            <div class="text-[0.875rem] text-emerald-900"><strong>Blok valid.</strong> Tautan ke blok sebelumnya, proof of work, dan merkle root dari catatan audit saat ini semuanya cocok.</div>
        </div>
    @else
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
            <div class="flex items-center gap-3 mb-2">
                <span class="w-9 h-9 rounded-lg bg-rose-500 text-white flex items-center justify-center font-extrabold shrink-0">✗</span>
                <strong class="text-[0.875rem] text-rose-900">Blok rusak — isinya tidak lagi sama dengan saat ditambang.</strong>
            </div>
            <ul class="list-disc pl-16 text-[0.8125rem] text-rose-900">
                @foreach($pemeriksaan['masalah'] as $m)
                    <li>{{ $m }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">
        {{-- Kepala Blok --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-[0.9375rem] font-extrabold text-navy">Kepala Blok</h3>
            </div>
            <dl class="divide-y divide-slate-100 text-[0.8125rem]">
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Nomor (tinggi)</dt>
                    <dd class="font-bold text-navy">{{ $blok->nomor }}</dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Hash blok</dt>
                    <dd class="font-mono text-[0.75rem] break-all"><span class="bg-emerald-200 text-emerald-900 font-bold">{{ $awalanNol }}</span><span class="text-navy font-bold">{{ substr($blok->hash_blok, $blok->tingkat_kesulitan) }}</span></dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Hash blok sebelumnya</dt>
                    <dd class="font-mono text-[0.75rem] break-all">
                        @if($sebelumnya)
                            <a href="{{ route('admin.blockchain.show', $sebelumnya->nomor) }}" class="text-brand-blue hover:underline">{{ $blok->hash_sebelumnya }}</a>
                        @else
                            <span class="text-slate-500">{{ $blok->hash_sebelumnya }}</span> <span class="font-sans text-slate-500">(genesis)</span>
                        @endif
                    </dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Merkle root</dt>
                    <dd class="font-mono text-[0.75rem] break-all text-slate-700">{{ $blok->merkle_root }}</dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Isi</dt>
                    <dd>
                        {{ $blok->jumlah_transaksi }} catatan audit
                        @if($blok->audit_id_awal)
                            <span class="text-slate-500">(ID #{{ $blok->audit_id_awal }} – #{{ $blok->audit_id_akhir }})</span>
                        @endif
                    </dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Proof of work</dt>
                    <dd>
                        Kesulitan <strong>{{ $blok->tingkat_kesulitan }}</strong> (hash diawali {{ $blok->tingkat_kesulitan }} angka nol) ·
                        nonce <strong class="font-mono">{{ number_format($blok->nonce, 0, ',', '.') }}</strong> ·
                        {{ number_format($blok->durasi_tambang_ms, 0, ',', '.') }} ms
                    </dd>
                </div>
                <div class="px-5 py-2.5 grid sm:grid-cols-[11rem_1fr] gap-1">
                    <dt class="text-slate-500 font-semibold">Ditambang pada</dt>
                    <dd>{{ $blok->ditambang_pada?->locale('id')->translatedFormat('d F Y, H:i:s') }} WITA</dd>
                </div>
            </dl>
        </div>

        {{-- Jangkar Ethereum --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-[0.9375rem] font-extrabold text-navy">Jangkar Ethereum</h3>
                @include('admin.blockchain.partials.lencana_jangkar', ['blok' => $blok])
            </div>
            <div class="p-5 flex flex-col gap-3 text-[0.8125rem] grow">
                @if($blok->jangkar_status === 'terkirim')
                    <div><span class="text-slate-500">Jaringan:</span> <strong>{{ $blok->jangkar_jaringan }}</strong></div>
                    @if($blok->jangkar_blok_eth)
                        <div><span class="text-slate-500">Blok Ethereum:</span> <strong class="font-mono">{{ number_format($blok->jangkar_blok_eth, 0, ',', '.') }}</strong></div>
                    @endif
                    <div><span class="text-slate-500">Dijangkarkan:</span> {{ $blok->jangkar_pada?->format('d/m/Y H:i:s') }}</div>
                    @if($blok->jangkar_tx)
                        <div class="font-mono text-[0.71875rem] break-all text-slate-600">{{ $blok->jangkar_tx }}</div>
                        <a href="{{ $blok->urlTransaksiEthereum() }}" target="_blank" rel="noopener noreferrer" class="font-bold text-brand-blue hover:underline">Lihat transaksi di Etherscan ↗</a>
                    @endif
                @elseif($blok->jangkar_galat)
                    <div class="text-rose-700 bg-rose-50 rounded-lg p-3">{{ $blok->jangkar_galat }}</div>
                @else
                    <div class="text-slate-500">Hash blok ini belum dikirim ke Ethereum.</div>
                @endif

                @if($jangkarAktif)
                    <a href="{{ route('admin.blockchain.show', ['nomor' => $blok->nomor, 'cek' => 1]) }}" class="mt-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl text-[0.8125rem] font-bold text-white bg-navy hover:bg-navy-light transition-colors">
                        Cocokkan dengan Ethereum
                    </a>
                @endif

                @if($cekJangkar)
                    @switch($cekJangkar['status'])
                        @case('cocok')
                            <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-lg p-3">
                                <strong>✓ Cocok.</strong> Hash blok di server sama persis dengan yang tercatat di Ethereum. Blok ini belum pernah diubah sejak dijangkarkan.
                            </div>
                            @break
                        @case('berbeda')
                            <div class="bg-rose-50 border border-rose-200 text-rose-900 rounded-lg p-3">
                                <strong>✗ Berbeda!</strong> Ethereum mencatat hash
                                <code class="font-mono text-[0.71875rem] break-all">{{ $cekJangkar['hash_onchain'] }}</code>
                                — blok di server telah dipalsukan.
                            </div>
                            @break
                        @case('belum')
                            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-3">Blok ini belum tercatat di kontrak Ethereum.</div>
                            @break
                        @case('nonaktif')
                            <div class="bg-slate-50 border border-slate-200 text-slate-700 rounded-lg p-3">Penjangkaran Ethereum tidak aktif.</div>
                            @break
                        @default
                            <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-3">{{ $cekJangkar['pesan'] ?? 'Gagal menghubungi Ethereum.' }}</div>
                    @endswitch
                @endif
            </div>
        </div>
    </div>

    {{-- Pohon Merkle --}}
    @if($blok->jumlah_transaksi > 0)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="text-[0.9375rem] font-extrabold text-navy">Pohon Merkle</h3>
            <span class="text-[0.75rem] text-slate-500">Induk = SHA-256(kiri + kanan). Daun ganjil dipasangkan dengan dirinya sendiri.</span>
        </div>
        <div class="overflow-x-auto">
            <div class="flex flex-col gap-3 min-w-max">
                @foreach(array_reverse($pohon) as $i => $tingkat)
                    <div class="flex justify-center gap-2">
                        @foreach($tingkat as $hash)
                            <span class="font-mono text-[0.71875rem] px-2 py-1 rounded-md border {{ $i === 0 ? 'bg-navy text-white border-navy font-bold' : ($loop->parent->last ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-700') }}" title="{{ $hash }}">{{ substr($hash, 0, 8) }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="flex justify-center gap-4 mt-3 text-[0.71875rem] text-slate-500">
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-navy align-middle"></span> Merkle root</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-200 align-middle"></span> Daun = hash catatan audit</span>
        </div>
    </div>
    @endif

    {{-- Isi Blok --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-[0.9375rem] font-extrabold text-navy">Isi Blok — Catatan Jejak Audit</h3>
            <span class="text-[0.75rem] text-slate-500">Nilai data yang diubah tidak ditampilkan di sini</span>
        </div>
        @if($transaksi === [])
            <div class="px-5 py-10 text-center text-slate-500 text-[0.875rem]">
                {{ $blok->isGenesis() ? 'Blok genesis tidak berisi catatan — ia hanya titik awal rantai.' : 'Blok ini tidak berisi catatan.' }}
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($transaksi as $t)
                    <details class="group {{ $t['status'] !== 'utuh' ? 'bg-rose-50/60' : '' }}">
                        <summary class="px-5 py-3 cursor-pointer list-none grid grid-cols-[4.5rem_1fr_auto] md:grid-cols-[4.5rem_13rem_9rem_1fr_9rem_auto] gap-x-3 gap-y-1 items-center text-[0.8125rem] hover:bg-slate-50">
                            <span class="font-mono font-bold text-slate-700">#{{ $t['id'] }}</span>
                            <span class="font-semibold text-navy truncate">{{ $t['aksi'] ?? '—' }}</span>
                            <span class="hidden md:block text-slate-600 truncate">{{ $t['username'] ?? 'sistem' }}</span>
                            <span class="hidden md:block text-slate-500 truncate">{{ $t['objek'] ?? '' }}</span>
                            <span class="hidden md:block text-slate-500 whitespace-nowrap">{{ $t['waktu']?->format('d/m/Y H:i:s') }}</span>
                            <span class="px-2 py-0.5 rounded-md text-[0.71875rem] font-bold uppercase {{ $warnaStatus[$t['status']] }}">{{ $t['status'] }}</span>
                        </summary>
                        <div class="px-5 pb-4 pt-1 text-[0.78125rem] flex flex-col gap-2">
                            <div class="grid sm:grid-cols-[10rem_1fr] gap-1">
                                <span class="text-slate-500">Hash tersegel di blok</span>
                                <code class="font-mono break-all">{{ $t['daun'] ?? '— (tidak ada: disisipkan setelah ditambang)' }}</code>
                                <span class="text-slate-500">Hash dari isi sekarang</span>
                                <code class="font-mono break-all {{ $t['status'] === 'utuh' ? 'text-emerald-700' : 'text-rose-700 font-bold' }}">{{ $t['daun_aktual'] ?? '— (catatan sudah tidak ada)' }}</code>
                            </div>
                            @if($t['bukti'] !== [] || $t['daun'])
                                <div class="mt-1">
                                    <div class="font-bold text-navy mb-1">Bukti Merkle ({{ count($t['bukti']) }} langkah)</div>
                                    <ol class="flex flex-col gap-1">
                                        @foreach($t['bukti'] as $langkah)
                                            <li class="font-mono text-[0.71875rem] text-slate-600">
                                                {{ $loop->iteration }}. gabung dengan saudara di <strong>{{ $langkah['posisi'] }}</strong>: {{ substr($langkah['hash'], 0, 16) }}…
                                            </li>
                                        @endforeach
                                    </ol>
                                    <div class="mt-1.5 {{ $t['bukti_sah'] ? 'text-emerald-700' : 'text-rose-700' }} font-bold">
                                        {{ $t['bukti_sah'] ? '✓ Hash catatan ini, digabung dengan bukti di atas, menghasilkan merkle root blok — catatan terbukti termasuk dalam blok dan tidak berubah.' : '✗ Bukti tidak sampai ke merkle root — catatan ini tidak sama dengan yang disegel.' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
