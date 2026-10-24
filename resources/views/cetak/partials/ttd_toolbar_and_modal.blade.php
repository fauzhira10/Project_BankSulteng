{{-- TOOLBAR AKSI ATAS (HANYA DITAMPILKAN DI LAYAR, OTOMATIS HILANG SAAT CETAK) --}}
@php
    $semuaPejabatData = \App\Models\MasterPejabatTtd::getAktifGrouped();
@endphp

<div class="no-print" style="width: 210mm; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 10px 16px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px;">
    
    {{-- Kiri: Tombol Kembali & Info --}}
    <div style="display: flex; align-items: center; gap: 12px;">
        <button type="button" onclick="handleKembali()" class="btn btn-back" style="background-color: #475569; color: #ffffff; border: none; padding: 7px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 12px;">
            ⬅ Kembali
        </button>
        <div>
            <div style="font-weight: bold; font-size: 13px; color: #0f172a;">
                {{ $judulForm ?? 'Form Penyelesaian Keluhan Nasabah' }}
            </div>
            <div style="font-size: 11px; color: #64748b;">
                Tiket: <strong>{{ $jurnal->no_tiket }}</strong> &bull; Nasabah: <strong>{{ $jurnal->nama_nasabah }}</strong>
            </div>
        </div>
    </div>

    {{-- Kanan: Pengendali TTD Digital & Tombol Cetak --}}
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        
        {{-- Sakelar Selektif Per-Orang (Chip Toggles 1-4) --}}
        <div style="display: inline-flex; align-items: center; gap: 4px; background: #f8fafc; padding: 3px 6px; border-radius: 6px; border: 1px solid #cbd5e1;">
            <span style="font-size: 11px; font-weight: 700; color: #475569; margin-right: 2px;">Pilih TTD:</span>
            <button type="button" id="chipSlot_1" onclick="toggleSlotTtd(1)" title="Aktifkan/Nonaktifkan TTD Staf" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; gap: 3px; transition: all 0.2s;">
                <span id="chipIcon_1">☐</span> 1. Staf
            </button>
            <button type="button" id="chipSlot_2" onclick="toggleSlotTtd(2)" title="Aktifkan/Nonaktifkan TTD Pemimpin Unit" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; gap: 3px; transition: all 0.2s;">
                <span id="chipIcon_2">☐</span> 2. P.Unit
            </button>
            <button type="button" id="chipSlot_3" onclick="toggleSlotTtd(3)" title="Aktifkan/Nonaktifkan TTD PINBAG" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; gap: 3px; transition: all 0.2s;">
                <span id="chipIcon_3">☐</span> 3. PINBAG
            </button>
            <button type="button" id="chipSlot_4" onclick="toggleSlotTtd(4)" title="Aktifkan/Nonaktifkan TTD Pemimpin Divisi" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; background: #ffffff; color: #64748b; display: inline-flex; align-items: center; gap: 3px; transition: all 0.2s;">
                <span id="chipIcon_4">☐</span> 4. P.Divisi
            </button>
        </div>

        {{-- Sakelar Cepat Pasang / Kosongkan --}}
        <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 6px; border: 1px solid #cbd5e1; gap: 2px;">
            <button type="button" onclick="pasangTtdStandar()" id="btnPasangStandar" title="Pasang TTD hanya untuk 2 orang (Staf & Pemimpin Unit)" style="padding: 5px 10px; border: none; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; background: transparent; color: #0284c7; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                🟢 Pasang TTD (Staf & Unit)
            </button>
            <button type="button" onclick="pasangSemuaTtd()" id="btnPasangSemua" title="Isi semua kotak dengan TTD digital pejabat utama" style="padding: 5px 10px; border: none; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; background: transparent; color: #475569; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                ⚡ Pasang Semua TTD
            </button>
            <button type="button" onclick="kosongkanSemuaTtd()" id="btnKosongkanSemua" title="Kosongkan seluruh tanda tangan untuk mode TTD basah/pena & stempel fisik" style="padding: 5px 10px; border: none; border-radius: 4px; font-size: 11px; font-weight: bold; cursor: pointer; background: #475569; color: #ffffff; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                ⚪ Kosongkan Semua (TTD Basah)
            </button>
        </div>

        {{-- Tombol Cetak --}}
        <button type="button" onclick="window.print()" class="btn btn-print" style="background-color: #1e3a8a; color: #ffffff; border: none; padding: 7px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; box-shadow: 0 2px 4px rgba(30,58,138,0.2);">
            🖨️ Cetak Form Ini
        </button>
    </div>
</div>

{{-- TOAST NOTIFIKASI --}}
<div id="ttdToast" class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #0f172a; color: #ffffff; padding: 10px 18px; border-radius: 8px; font-size: 12px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none; align-items: center; gap: 8px; border-left: 4px solid #10b981;">
    <span id="ttdToastMsg">Notifikasi</span>
</div>

<script>
    // Master data pejabat aktif
    const masterPejabatJson = @json($semuaPejabatData);
    const idJurnalAktif = {{ $jurnal->id }};
    const csrfToken = "{{ csrf_token() }}";

    // Data State Lokal untuk 4 Slot
    let ttdState = {
        1: { nama: '', jabatan: '', nip: '', ttd_image: '', is_kosong: true, pejabat_id: null },
        2: { nama: '', jabatan: '', nip: '', ttd_image: '', is_kosong: true, pejabat_id: null },
        3: { nama: '', jabatan: '', nip: '', ttd_image: '', is_kosong: true, pejabat_id: null },
        4: { nama: '', jabatan: '', nip: '', ttd_image: '', is_kosong: true, pejabat_id: null }
    };

    // Inisialisasi dari DOM saat load
    document.addEventListener('DOMContentLoaded', function() {
        for (let s = 1; s <= 4; s++) {
            const nameEl = document.getElementById(`sigName_${s}`);
            const titleEl = document.getElementById(`sigTitle_${s}`);
            const imgEl = document.getElementById(`ttdImg_${s}`);

            ttdState[s].nama = nameEl ? nameEl.innerText.trim() : '';
            ttdState[s].jabatan = titleEl ? titleEl.innerText.trim() : '';
            ttdState[s].ttd_image = imgEl ? imgEl.src : '';
            ttdState[s].is_kosong = (!imgEl || imgEl.style.display === 'none');

            // Simpan referensi pejabat default jika belum ada gambar
            const daftar = masterPejabatJson[s] || [];
            const pDefault = daftar.find(p => p.is_default) || daftar[0];
            if (pDefault) {
                if (!ttdState[s].ttd_image) {
                    ttdState[s].ttd_image = pDefault.ttd_image || '';
                }
                if (!ttdState[s].nama) {
                    ttdState[s].nama = pDefault.nama || '';
                }
                if (!ttdState[s].jabatan) {
                    ttdState[s].jabatan = pDefault.jabatan || '';
                }
                ttdState[s].pejabat_id = pDefault.id;
            }
            // Sinkronisasi status awal ke seluruh elemen termasuk slip
            renderSlotUI(s);
        }

        updateGlobalVisuals();
    });

    // Perbarui status chip toggle dan tombol cepat
    function updateGlobalVisuals() {
        for (let s = 1; s <= 4; s++) {
            const chip = document.getElementById(`chipSlot_${s}`);
            const icon = document.getElementById(`chipIcon_${s}`);
            const isAktif = !ttdState[s].is_kosong && !!ttdState[s].ttd_image;

            if (chip && icon) {
                if (isAktif) {
                    chip.style.background = '#0284c7';
                    chip.style.color = '#ffffff';
                    chip.style.borderColor = '#0284c7';
                    icon.innerText = '☑️';
                } else {
                    chip.style.background = '#ffffff';
                    chip.style.color = '#64748b';
                    chip.style.borderColor = '#cbd5e1';
                    icon.innerText = '☐';
                }
            }
        }

        const btnStandar = document.getElementById('btnPasangStandar');
        const btnSemua = document.getElementById('btnPasangSemua');
        const btnKosong = document.getElementById('btnKosongkanSemua');

        const s1 = !ttdState[1].is_kosong && !!ttdState[1].ttd_image;
        const s2 = !ttdState[2].is_kosong && !!ttdState[2].ttd_image;
        const s3 = !ttdState[3].is_kosong && !!ttdState[3].ttd_image;
        const s4 = !ttdState[4].is_kosong && !!ttdState[4].ttd_image;

        const isSemuaKosong = !s1 && !s2 && !s3 && !s4;
        const isSemuaAktif = s1 && s2 && s3 && s4;
        const isStandarAktif = s1 && s2 && !s3 && !s4;

        if (btnStandar) {
            btnStandar.style.background = isStandarAktif ? '#0284c7' : 'transparent';
            btnStandar.style.color = isStandarAktif ? '#ffffff' : '#0284c7';
        }
        if (btnSemua) {
            btnSemua.style.background = isSemuaAktif ? '#0284c7' : 'transparent';
            btnSemua.style.color = isSemuaAktif ? '#ffffff' : '#475569';
        }
        if (btnKosong) {
            btnKosong.style.background = isSemuaKosong ? '#475569' : 'transparent';
            btnKosong.style.color = isSemuaKosong ? '#ffffff' : '#475569';
        }
    }

    // Render Visual Slot Berdasarkan State (Bersih tanpa teks placeholder)
    function renderSlotUI(slot) {
        const data = ttdState[slot];
        const container = document.getElementById(`ttdDisplay_${slot}`);
        const nameEl = document.getElementById(`sigName_${slot}`);
        const titleEl = document.getElementById(`sigTitle_${slot}`);

        if (nameEl && data.nama) nameEl.innerText = data.nama;
        if (titleEl && data.jabatan) titleEl.innerText = data.jabatan;

        if (container) {
            if (data.is_kosong || !data.ttd_image) {
                container.innerHTML = `<div class="ttd-placeholder-kosong" id="ttdKosong_${slot}" style="height: 85px; width: 100%;"></div>`;
            } else {
                container.innerHTML = `
                    <img src="${data.ttd_image}" alt="TTD ${data.nama}" class="ttd-image-element" id="ttdImg_${slot}" style="max-height: 85px; max-width: 90%; object-fit: contain; pointer-events: none; margin-bottom: -10px; position: relative; z-index: 2;">
                `;
            }
        }

        // Sinkronisasi Otomatis ke Tabel Slip Jurnal / Voucher (Kolom Paraf & Tanggal)
        const slipCells = document.querySelectorAll(`.slip-ttd-slot-${slot}`);
        slipCells.forEach(cell => {
            const maxHeight = cell.getAttribute('data-max-h') || '20px';
            if (data.is_kosong || !data.ttd_image) {
                cell.innerHTML = '';
            } else {
                cell.innerHTML = `
                    <img src="${data.ttd_image}" alt="TTD ${data.nama}" style="max-height: ${maxHeight}; max-width: 95%; width: auto; height: auto; object-fit: contain; display: block; margin: auto; pointer-events: none;">
                `;
            }
        });
    }

    // Pastikan data pejabat terisi
    function ensurePejabatData(slot) {
        if (!ttdState[slot].ttd_image) {
            const daftar = masterPejabatJson[slot] || [];
            const pDefault = daftar.find(p => p.is_default) || daftar[0];
            if (pDefault) {
                ttdState[slot].pejabat_id = pDefault.id;
                ttdState[slot].nama = pDefault.nama;
                ttdState[slot].jabatan = pDefault.jabatan;
                ttdState[slot].ttd_image = pDefault.ttd_image || '';
            }
        }
    }

    // 1. Toggle TTD Individu per Slot (1, 2, 3, atau 4)
    function toggleSlotTtd(slot) {
        ensurePejabatData(slot);
        ttdState[slot].is_kosong = !ttdState[slot].is_kosong;
        renderSlotUI(slot);
        updateGlobalVisuals();

        const labelSlot = ['', '1. Staf', '2. Pemimpin Unit', '3. PINBAG', '4. Pemimpin Divisi'][slot] || ('Slot ' + slot);
        if (!ttdState[slot].is_kosong) {
            showToast(`🟢 TTD ${labelSlot} diaktifkan.`);
        } else {
            showToast(`⚪ TTD ${labelSlot} dinonaktifkan (kosong).`);
        }
    }

    // 2. Preset Cepat: Pasang TTD Standar (Hanya 2 Orang: Slot 1 Staf & Slot 2 P.Unit)
    function pasangTtdStandar() {
        for (let s = 1; s <= 4; s++) {
            ensurePejabatData(s);
            if (s === 1 || s === 2) {
                ttdState[s].is_kosong = false;
            } else {
                ttdState[s].is_kosong = true;
            }
            renderSlotUI(s);
        }
        updateGlobalVisuals();
        showToast('🟢 TTD Standar aktif: 2 orang (Staf & Pemimpin Unit).');
    }

    // 3. Global Switcher: Pasang Semua TTD Digital (4 Pejabat)
    function pasangSemuaTtd() {
        for (let s = 1; s <= 4; s++) {
            ensurePejabatData(s);
            ttdState[s].is_kosong = false;
            renderSlotUI(s);
        }
        updateGlobalVisuals();
        showToast('🟢 Seluruh tanda tangan digital berhasil dipasang.');
    }

    // 4. Global Switcher: Kosongkan Semua TTD (Mode TTD Basah Kertas)
    function kosongkanSemuaTtd() {
        for (let s = 1; s <= 4; s++) {
            ttdState[s].is_kosong = true;
            renderSlotUI(s);
        }
        updateGlobalVisuals();
        showToast('⚪ Seluruh tanda tangan dikosongkan (Mode TTD Basah).');
    }

    function showToast(msg) {
        const toast = document.getElementById('ttdToast');
        const msgEl = document.getElementById('ttdToastMsg');
        if (!toast) return;
        msgEl.innerText = msg;
        toast.style.display = 'flex';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
</script>
