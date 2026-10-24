{{-- Pagination ringkas memakai kelas .page-btn. Pemakaian: @include('partials.pagination_sederhana', ['paginator' => $pengaduans]) --}}
@php $pg = $paginator; @endphp
<div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-5">
    <div class="text-xs text-slate-500">
        Menampilkan <strong>{{ $pg->firstItem() ?? 0 }}–{{ $pg->lastItem() ?? 0 }}</strong> dari <strong class="text-navy">{{ number_format($pg->total(), 0, ',', '.') }}</strong> data
    </div>
    @if($pg->hasPages())
        <div class="flex items-center gap-1 flex-wrap">
            @if($pg->onFirstPage())
                <span class="page-btn disabled">‹ Sebelumnya</span>
            @else
                <a href="{{ $pg->previousPageUrl() }}" class="page-btn">‹ Sebelumnya</a>
            @endif

            @php
                $cur = $pg->currentPage(); $last = $pg->lastPage();
                $mulai = max(1, $cur - 2); $akhir = min($last, $cur + 2);
            @endphp
            @if($mulai > 1)
                <a href="{{ $pg->url(1) }}" class="page-btn">1</a>
                @if($mulai > 2)<span class="page-btn-dots">…</span>@endif
            @endif
            @for($i = $mulai; $i <= $akhir; $i++)
                @if($i == $cur)
                    <span class="page-btn active">{{ $i }}</span>
                @else
                    <a href="{{ $pg->url($i) }}" class="page-btn">{{ $i }}</a>
                @endif
            @endfor
            @if($akhir < $last)
                @if($akhir < $last - 1)<span class="page-btn-dots">…</span>@endif
                <a href="{{ $pg->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if($pg->hasMorePages())
                <a href="{{ $pg->nextPageUrl() }}" class="page-btn">Berikutnya ›</a>
            @else
                <span class="page-btn disabled">Berikutnya ›</span>
            @endif
        </div>
    @endif
</div>
