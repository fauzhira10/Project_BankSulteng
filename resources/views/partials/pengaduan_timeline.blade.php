{{-- Linimasa status pengaduan. Pemakaian: @include('partials.pengaduan_timeline', ['pengaduan' => $pengaduan]) --}}
@php
    $p = $pengaduan;
    $ditolak = $p->status === \App\Enums\PengaduanStatus::Ditolak;

    $langkah = [
        [
            'label' => 'Terkirim',
            'waktu' => $p->created_at,
            'ket'   => 'Dikirim oleh ' . $p->nama_pelapor,
            'warna' => 'emerald',
        ],
        [
            'label' => 'Diterima Pusat',
            'waktu' => $p->diterima_at,
            'ket'   => $p->penerima?->name ? 'Diverifikasi oleh ' . $p->penerima->name : 'Menunggu verifikasi Admin Pusat',
            'warna' => 'emerald',
        ],
    ];

    if (! $ditolak || $p->diproses_at) {
        $langkah[] = [
            'label' => 'Dalam Proses Jurnal',
            'waktu' => $p->diproses_at,
            'ket'   => $p->jurnal ? 'Tiket: ' . ($p->jurnal->no_tiket ?: '-') : 'Belum dimasukkan ke jurnal',
            'warna' => 'emerald',
        ];
    }

    $langkah[] = $ditolak
        ? ['label' => 'Ditolak', 'waktu' => $p->ditolak_at, 'ket' => 'Lihat catatan dari pusat', 'warna' => 'rose']
        : ['label' => 'Selesai', 'waktu' => $p->selesai_at, 'ket' => $p->selesai_at ? 'Penanganan dinyatakan selesai' : 'Menunggu penyelesaian pusat', 'warna' => 'emerald'];

    $indexAktif = null;
    foreach ($langkah as $i => $l) {
        if (! $l['waktu']) { $indexAktif = $i; break; }
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 {{ count($langkah) === 3 ? 'xl:grid-cols-3' : 'xl:grid-cols-4' }} gap-3">
    @foreach($langkah as $i => $l)
        @php
            $sudah = (bool) $l['waktu'];
            $aktif = $indexAktif === $i;
            if ($sudah) {
                $lingkaran = $l['warna'] === 'rose' ? 'bg-rose-500 border-rose-500 text-white' : 'bg-emerald-500 border-emerald-500 text-white';
                $kotak = 'border-slate-200 bg-white';
            } elseif ($aktif) {
                $lingkaran = 'bg-white border-brand-blue text-brand-blue animate-pulse-soft';
                $kotak = 'border-brand-blue/40 bg-sky-50/60';
            } else {
                $lingkaran = 'bg-slate-100 border-slate-300 text-slate-500';
                $kotak = 'border-dashed border-slate-200 bg-slate-50/60';
            }
        @endphp
        <div class="flex items-start gap-3 p-3 rounded-xl border {{ $kotak }}">
            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center shrink-0 text-xs font-bold {{ $lingkaran }}">
                @if($sudah)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                @else
                    {{ $i + 1 }}
                @endif
            </div>
            <div class="min-w-0">
                <div class="text-[0.78125rem] font-bold {{ $sudah || $aktif ? 'text-navy' : 'text-slate-500' }}">{{ $l['label'] }}</div>
                <div class="text-[0.71875rem] text-slate-500 mt-0.5 leading-snug">
                    @if($l['waktu'])
                        <span class="font-semibold text-slate-600">{{ $l['waktu']->translatedFormat('d M Y, H:i') }}</span><br>
                    @endif
                    <span class="{{ $sudah ? '' : 'italic' }}">{{ $l['ket'] }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
