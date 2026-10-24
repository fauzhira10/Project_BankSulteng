{{-- PARTIAL FOOTER TANDA TANGAN 4 KOLOM --}}
@php
    $ttdConfig = $jurnal->getTtdConfig();
@endphp

<tr>
    <td colspan="2" class="no-padding" style="padding: 0;">
        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr>
                <td colspan="3" class="sig-header" style="width: 74%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Di Selesaikan Oleh,</td>
                <td class="sig-header" style="width: 26%; border-bottom: 1px solid #000;">Di ketahui Oleh,</td>
            </tr>

            <!-- Ruang Kotak Tanda Tangan -->
            <tr>
                @for($s = 1; $s <= 4; $s++)
                    @php
                        $slotData = $ttdConfig['slots'][$s] ?? [
                            'nama' => \App\Models\MasterPejabatTtd::DAFTAR_SLOT[$s]['default_nama'],
                            'jabatan' => \App\Models\MasterPejabatTtd::DAFTAR_SLOT[$s]['default_jabatan'],
                            'ttd_image' => '',
                            'is_kosong' => false,
                        ];
                        $isKosong = !empty($slotData['is_kosong']);
                        $borderRight = $s < 4 ? 'border-right: 1px solid #000;' : '';
                        $colWidth = ($s === 1 || $s === 3) ? '24%' : '26%';
                    @endphp
                    <td class="sig-space-cell" style="width: {{ $colWidth }}; {{ $borderRight }} vertical-align: bottom !important; text-align: center; position: relative; padding: 0 !important;" id="slotContainer_{{ $s }}">
                        
                        {{-- Area Tanda Tangan: Gambar TTD atau Ruang Kosong Bersih --}}
                        <div class="ttd-display-area" id="ttdDisplay_{{ $s }}" style="height: 100px; display: flex; align-items: flex-end; justify-content: center; padding: 0 4px 0 4px;">
                            @if(!$isKosong && !empty($slotData['ttd_image']))
                                <img src="{{ $slotData['ttd_image'] }}" alt="TTD {{ $slotData['nama'] }}" class="ttd-image-element" id="ttdImg_{{ $s }}" style="max-height: 85px; max-width: 90%; object-fit: contain; pointer-events: none; margin-bottom: -10px; position: relative; z-index: 2;">
                            @else
                                <div class="ttd-placeholder-kosong" id="ttdKosong_{{ $s }}" style="height: 85px; width: 100%;"></div>
                            @endif
                        </div>

                    </td>
                @endfor
            </tr>

            <!-- Baris Nama Pejabat (Rata Sejajar Horizontal & Editable) -->
            <tr>
                @for($s = 1; $s <= 4; $s++)
                    @php
                        $slotData = $ttdConfig['slots'][$s] ?? [
                            'nama' => \App\Models\MasterPejabatTtd::DAFTAR_SLOT[$s]['default_nama'],
                        ];
                        $borderRight = $s < 4 ? 'border-right: 1px solid #000;' : '';
                        $colWidth = ($s === 1 || $s === 3) ? '24%' : '26%';
                    @endphp
                    <td class="sig-name-cell" style="width: {{ $colWidth }}; {{ $borderRight }} padding-top: 0 !important;">
                        <div class="sig-name editable-text" id="sigName_{{ $s }}" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Nama" style="position: relative; z-index: 3;">{{ $slotData['nama'] }}</div>
                    </td>
                @endfor
            </tr>

            <!-- Baris Jabatan Pejabat (Editable) -->
            <tr>
                @for($s = 1; $s <= 4; $s++)
                    @php
                        $slotData = $ttdConfig['slots'][$s] ?? [
                            'jabatan' => \App\Models\MasterPejabatTtd::DAFTAR_SLOT[$s]['default_jabatan'],
                        ];
                        $borderRight = $s < 4 ? 'border-right: 1px solid #000;' : '';
                        $colWidth = ($s === 1 || $s === 3) ? '24%' : '26%';
                    @endphp
                    <td class="sig-title-cell" style="width: {{ $colWidth }}; {{ $borderRight }}">
                        <div class="sig-title editable-text" id="sigTitle_{{ $s }}" contenteditable="true" spellcheck="false" title="Klik untuk mengedit Jabatan">{{ $slotData['jabatan'] }}</div>
                    </td>
                @endfor
            </tr>
        </table>
    </td>
</tr>
