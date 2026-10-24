{{-- Kartu ringkasan status pengaduan.
     Pemakaian: @include('partials.pengaduan_stats', ['stats' => $stats, 'urlDasar' => route(...), 'nilaiSemua' => '' atau 'semua', 'statusAktif' => request('status')]) --}}
@php
    $nilaiSemua = $nilaiSemua ?? '';
    $statusAktif = $statusAktif ?? null;
    $kartu = [
        ['kunci' => 'total', 'label' => 'Total Pengaduan', 'nilai' => $nilaiSemua, 'ikon' => 'bg-brand-blue-light text-brand-blue', 'svg' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>'],
        ['kunci' => 'Terkirim', 'label' => 'Menunggu Verifikasi', 'nilai' => 'Terkirim', 'ikon' => 'bg-amber-100 text-amber-600', 'svg' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>'],
        ['kunci' => 'Diterima', 'label' => 'Diterima Pusat', 'nilai' => 'Diterima', 'ikon' => 'bg-sky-100 text-sky-600', 'svg' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'],
        ['kunci' => 'Diproses', 'label' => 'Dalam Proses', 'nilai' => 'Diproses', 'ikon' => 'bg-violet-100 text-violet-600', 'svg' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>'],
        ['kunci' => 'Selesai', 'label' => 'Selesai', 'nilai' => 'Selesai', 'ikon' => 'bg-emerald-100 text-emerald-600', 'svg' => '<polyline points="20 6 9 17 4 12"></polyline>'],
        ['kunci' => 'Ditolak', 'label' => 'Ditolak', 'nilai' => 'Ditolak', 'ikon' => 'bg-rose-100 text-rose-600', 'svg' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'],
    ];
@endphp
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-6">
    @foreach($kartu as $k)
        @php $aktif = $statusAktif !== null && (string) $statusAktif === (string) $k['nilai']; @endphp
        <a href="{{ $urlDasar }}?status={{ $k['nilai'] }}" class="stat-card {{ $aktif ? 'ring-2 ring-brand-blue/40 border-brand-blue/40' : '' }}" style="padding: 14px 16px; gap: 12px;">
            <div class="stat-icon {{ $k['ikon'] }}" style="width: 40px; height: 40px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $k['svg'] !!}</svg>
            </div>
            <div class="stat-info min-w-0">
                <div class="stat-count" style="font-size: 1.25rem;">{{ number_format($stats[$k['kunci']] ?? 0, 0, ',', '.') }}</div>
                <div class="stat-label truncate" style="font-size: 0.71875rem;">{{ $k['label'] }}</div>
            </div>
        </a>
    @endforeach
</div>
