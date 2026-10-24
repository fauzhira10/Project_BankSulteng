import React, { useState, useMemo } from 'react';
import { 
  Search, 
  ChevronDown, 
  X, 
  Building2, 
  Layers, 
  TrendingUp, 
  CreditCard, 
  Smartphone,
  SlidersHorizontal,
  Info
} from 'lucide-react';

// ==========================================
// 1. DATA DUMMY (40 Cabang untuk Multi-Cabang & Single ATM)
// ==========================================
const DUMMY_MULTI_BRANCH_DATA = [
  { id: '001', name: 'CABANG UTAMA (PALU)', count: 82 },
  { id: '008', name: 'CABANG PALU BARAT', count: 45 },
  { id: '003', name: 'CABANG POSO', count: 38 },
  { id: '004', name: 'CABANG LUWUK', count: 31 },
  { id: '009', name: 'CABANG JAKARTA', count: 28 },
  { id: '002', name: 'CABANG TOLI TOLI', count: 22 },
  { id: '101', name: 'CABANG DONGGALA', count: 19 },
  { id: '102', name: 'CABANG PARIGI', count: 17 },
  { id: '005', name: 'CABANG BUNGKU', count: 15 },
  { id: '007', name: 'CABANG SIGI', count: 14 },
  { id: '301', name: 'CABANG AMPANA', count: 12 },
  { id: '201', name: 'CABANG BUOL', count: 11 },
  { id: '401', name: 'CABANG KOLONODALE', count: 10 },
  { id: '006', name: 'CABANG SALAKAN', count: 9 },
  { id: '402', name: 'CABANG BANGGAI LAUT', count: 8 },
  { id: '502', name: 'KCP BAHODOPI', count: 8 },
  { id: '801', name: 'KCP TAWAELI', count: 7 },
  { id: '405', name: 'KCP TOILI', count: 6 },
  { id: '404', name: 'KCP BATUI', count: 5 },
  { id: '105', name: 'KCP TOLAI', count: 5 },
  { id: '106', name: 'KCP TINOMBO', count: 4 },
  { id: '411', name: 'KCP MAMOSALATO', count: 4 },
  { id: '412', name: 'KCP TOMATA', count: 4 },
  { id: '413', name: 'KCP BATURUBE', count: 3 },
  { id: '303', name: 'KCP TENTENA', count: 3 },
  { id: '304', name: 'KCP PENDOLO', count: 3 },
  { id: '305', name: 'KCP NAPU', count: 2 },
  { id: '302', name: 'KCP WAKAI', count: 2 },
  { id: '403', name: 'KCP BETELEME', count: 2 },
  { id: '501', name: 'KCP BAHOMOTEFE', count: 2 },
  { id: '701', name: 'KCP KULAWI', count: 2 },
  { id: '103', name: 'KCP LAMBUNU', count: 1 },
  { id: '104', name: 'KCP LABEAN', count: 1 },
  { id: '107', name: 'KCP TINOMBALA', count: 1 },
  { id: '108', name: 'KCP KOTARAYA', count: 1 },
  { id: '202', name: 'KCP SONI', count: 1 },
  { id: '211', name: 'KCP PALELEH', count: 1 },
  { id: '306', name: 'KCP TAMBARANA', count: 1 },
  { id: '406', name: 'KCP MASAMA', count: 1 },
  { id: '407', name: 'KCP BUNTA', count: 1 },
];

const DUMMY_TABLE_ROWS = [
  {
    id: 1,
    terminal: 'BANK LAIN',
    category: 'multi',
    type: 'off_us',
    branches: DUMMY_MULTI_BRANCH_DATA,
    totalComplaints: 457,
    totalNominal: 324500000,
    dominantIssues: 'TARIK TUNAI (285x), TRANSFER (112x)',
  },
  {
    id: 2,
    terminal: 'MOBILE BANKING',
    category: 'multi',
    type: 'digital',
    branches: DUMMY_MULTI_BRANCH_DATA.slice(0, 25),
    totalComplaints: 198,
    totalNominal: 142000000,
    dominantIssues: 'TRANSFER BI-FAST (120x), PEMBELIAN PULSA (45x)',
  },
  {
    id: 3,
    terminal: '180 - CRM.PALUBARAT',
    category: 'single',
    type: 'atm',
    branchName: 'CABANG PALU BARAT',
    branchCode: '008',
    location: 'KANTOR PALU BARAT',
    totalComplaints: 42,
    totalNominal: 38500000,
    dominantIssues: 'UANG TIDAK KELUAR (28x), KARTU TERTELAN (14x)',
  },
  {
    id: 4,
    terminal: '209 - WCR.PALBAR',
    category: 'single',
    type: 'atm',
    branchName: 'CABANG PALU BARAT',
    branchCode: '008',
    location: 'SWISS BELL HOTEL',
    totalComplaints: 26,
    totalNominal: 19000000,
    dominantIssues: 'TARIK TUNAI (19x), SALDO TERPOTONG (7x)',
  },
  {
    id: 5,
    terminal: '101 - CRM.UTAMA1',
    category: 'single',
    type: 'atm',
    branchName: 'CABANG UTAMA',
    branchCode: '001',
    location: 'KANTOR CABANG UTAMA',
    totalComplaints: 23,
    totalNominal: 17500000,
    dominantIssues: 'UANG TIDAK KELUAR (15x), RESI TIDAK KELUAR (8x)',
  },
];

// ==========================================
// 2. KOMPONEN DETAIL MULTI-CABANG (EXPANDED MODAL PANEL)
// ==========================================
function MultiBranchExpandedPanel({ branches, totalCount, onClose, terminalName }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('count-desc');

  const maxCount = useMemo(() => {
    return Math.max(...branches.map((b) => b.count), 1);
  }, [branches]);

  const filteredBranches = useMemo(() => {
    return branches
      .filter((b) => b.name.toLowerCase().includes(searchTerm.toLowerCase()) || b.id.includes(searchTerm))
      .sort((a, b) => {
        if (sortBy === 'count-desc') return b.count - a.count;
        if (sortBy === 'count-asc') return a.count - b.count;
        if (sortBy === 'name-asc') return a.name.localeCompare(b.name);
        return 0;
      });
  }, [branches, searchTerm, sortBy]);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-xl max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200">
        
        {/* Header Modal */}
        <div className="px-6 py-4.5 bg-gradient-to-r from-slate-900 via-slate-800 to-navy text-white flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-sky-500/20 border border-sky-400/30 flex items-center justify-center text-sky-300">
              <Layers className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="font-bold text-base text-white">Distribusi Keluhan Multi-Cabang</h3>
                <span className="px-2 py-0.5 rounded-full bg-amber-400/20 border border-amber-400/40 text-amber-300 font-bold text-xs">
                  {branches.length} Cabang
                </span>
              </div>
              <p className="text-xs text-slate-300 mt-0.5">
                Rincian transaksi keluhan untuk <span className="font-semibold text-white">{terminalName}</span>
              </p>
            </div>
          </div>
          <button 
            onClick={onClose}
            className="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Toolbar Pencarian & Filter */}
        <div className="p-4 bg-slate-50 border-b border-slate-200 space-y-3">
          <div className="flex items-center gap-3">
            <div className="relative flex-1">
              <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
              <input
                type="text"
                placeholder="Cari nama atau kode cabang..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full h-9 pl-9 pr-8 text-xs font-medium bg-white border border-slate-300 rounded-xl focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/15 focus:outline-none transition-all placeholder:text-slate-400"
                autoFocus
              />
              {searchTerm && (
                <button 
                  onClick={() => setSearchTerm('')} 
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>

            {/* Sort Dropdown */}
            <div className="flex items-center gap-1.5 shrink-0">
              <SlidersHorizontal className="w-3.5 h-3.5 text-slate-500" />
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="h-9 px-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl focus:border-brand-blue focus:outline-none cursor-pointer"
              >
                <option value="count-desc">Keluhan Tertinggi</option>
                <option value="count-asc">Keluhan Terendah</option>
                <option value="name-asc">Nama Cabang (A-Z)</option>
              </select>
            </div>
          </div>

          <div className="flex items-center justify-between text-[11px] text-slate-500">
            <span>
              Menampilkan <strong>{filteredBranches.length}</strong> dari <strong>{branches.length}</strong> cabang pelapor
            </span>
            <span>Total: <strong>{totalCount}</strong> keluhan</span>
          </div>
        </div>

        {/* Scrollable List of Branches with Proportional Bars */}
        <div className="flex-1 overflow-y-auto p-4 divide-y divide-slate-100">
          {filteredBranches.length > 0 ? (
            <div className="space-y-2.5">
              {filteredBranches.map((branch, idx) => {
                const percentage = Math.round((branch.count / maxCount) * 100);
                const shareOfTotal = ((branch.count / totalCount) * 100).toFixed(1);

                return (
                  <div 
                    key={branch.id} 
                    className="p-2.5 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200/80 transition-all flex items-center gap-3"
                  >
                    {/* Rank Badge */}
                    <span className={`w-6 h-6 rounded-lg flex items-center justify-center font-bold text-[10.5px] shrink-0 ${
                      idx === 0 
                        ? 'bg-amber-100 text-amber-800 border border-amber-200' 
                        : idx === 1 
                        ? 'bg-slate-200 text-slate-700' 
                        : idx === 2 
                        ? 'bg-orange-100 text-orange-800' 
                        : 'bg-slate-100 text-slate-500'
                    }`}>
                      {idx + 1}
                    </span>

                    {/* Branch Info & Visual Bar */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between gap-2 mb-1.5">
                        <div className="flex items-center gap-1.5 truncate">
                          <span className="font-bold text-xs text-slate-800 truncate">{branch.name}</span>
                          <span className="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 font-mono">
                            {branch.id}
                          </span>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                          <span className="font-extrabold text-xs text-navy px-2 py-0.5 rounded-md bg-sky-50 border border-sky-100">
                            {branch.count} <span className="font-normal text-[10px] text-slate-500">kasus</span>
                          </span>
                        </div>
                      </div>

                      {/* Proportional Progress Bar */}
                      <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div 
                          className={`h-full rounded-full transition-all duration-500 ${
                            idx === 0 
                              ? 'bg-gradient-to-r from-amber-400 to-amber-500' 
                              : idx < 3 
                              ? 'bg-gradient-to-r from-sky-400 to-brand-blue' 
                              : 'bg-gradient-to-r from-slate-300 to-slate-400'
                          }`}
                          style={{ width: `${Math.max(percentage, 4)}%` }}
                        />
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="py-12 text-center text-slate-400">
              <Search className="w-8 h-8 mx-auto mb-2 opacity-40" />
              <p className="text-xs font-semibold text-slate-600">Tidak ada cabang yang cocok</p>
              <p className="text-[11px] text-slate-400 mt-0.5">Coba kata kunci pencarian yang lain</p>
            </div>
          )}
        </div>

        {/* Footer Modal */}
        <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
          <div className="text-[11px] text-slate-500 flex items-center gap-1.5">
            <Info className="w-3.5 h-3.5 text-sky-600" />
            <span>Mini bar proporsional terhadap cabang pelapor tertinggi ({maxCount} kasus).</span>
          </div>
          <button
            onClick={onClose}
            className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
          >
            Tutup
          </button>
        </div>

      </div>
    </div>
  );
}

// ==========================================
// 3. KOMPONEN SEL KANTOR CABANG PENGELOLA (CELL COMPONENT)
// ==========================================
function BranchManagerCell({ row }) {
  const [isExpanded, setIsExpanded] = useState(false);

  // Jika Single Branch (ATM fisik Bank Sulteng)
  if (row.category !== 'multi') {
    return (
      <div className="space-y-0.5">
        <div className="flex items-center gap-1.5">
          <Building2 className="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span className="font-bold text-xs text-slate-800">{row.branchName}</span>
        </div>
        <div className="text-[11px] text-slate-500 pl-5 flex items-center gap-2">
          <span>Kode: <strong className="font-mono text-slate-600">{row.branchCode}</strong></span>
          {row.location && (
            <>
              <span className="text-slate-300">•</span>
              <span className="text-slate-500 truncate max-w-[180px]">{row.location}</span>
            </>
          )}
        </div>
      </div>
    );
  }

  // JIKA MULTI-CABANG (BANK LAIN / MOBILE BANKING)
  const branches = row.branches || [];
  const topBranches = branches.slice(0, 3); // Default state: Top 3 Cabang
  const remainingCount = branches.length - topBranches.length;
  const maxCountInGroup = Math.max(...branches.map((b) => b.count), 1);

  return (
    <div className="space-y-2 py-1">
      {/* Badge Ringkas Multi-Cabang */}
      <div className="flex items-center justify-between gap-2">
        <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 font-bold text-[11px]">
          <Layers className="w-3 h-3 text-amber-600 shrink-0" />
          <span>Multi-Cabang ({branches.length} Cabang)</span>
        </div>
        <span className="text-[10.5px] text-slate-400 font-medium">Top {topBranches.length} Pelapor:</span>
      </div>

      {/* Mini Horizontal Distribution List (Top 3) */}
      <div className="space-y-1.5 bg-slate-50/80 p-2 rounded-xl border border-slate-100">
        {topBranches.map((branch, idx) => {
          const barWidth = Math.round((branch.count / maxCountInGroup) * 100);

          return (
            <div key={branch.id} className="flex items-center gap-2 text-[11px]">
              {/* Branch Code & Name */}
              <div className="flex items-center gap-1.5 min-w-0 max-w-[170px]" title={branch.id ? `[${branch.id}] ${branch.name}` : branch.name}>
                {branch.id && (
                  <span className="px-1.5 py-0.5 rounded bg-slate-200/90 text-slate-700 font-mono font-bold text-[9.5px] shrink-0">
                    {branch.id}
                  </span>
                )}
                <span className="font-semibold text-slate-700 truncate text-[11px]">
                  {branch.name}
                </span>
              </div>

              {/* Mini Horizontal Bar */}
              <div className="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div 
                  className={`h-full rounded-full ${
                    idx === 0 ? 'bg-amber-500' : 'bg-brand-blue'
                  }`} 
                  style={{ width: `${Math.max(barWidth, 8)}%` }} 
                />
              </div>

              {/* Nilai Kasus */}
              <span className="font-bold text-slate-800 text-[10.5px] shrink-0 min-w-[24px] text-right">
                {branch.count}
              </span>
            </div>
          );
        })}
      </div>

      {/* Progressive Disclosure Action: Button to Expand Modal/Drawer */}
      <button
        onClick={() => setIsExpanded(true)}
        className="inline-flex items-center gap-1 text-[11px] font-bold text-brand-blue hover:text-navy hover:underline transition-colors cursor-pointer group"
      >
        {remainingCount > 0 ? (
          <>
            <span>+{remainingCount} cabang lainnya (lihat semua)</span>
            <ChevronDown className="w-3 h-3 transition-transform group-hover:translate-y-0.5" />
          </>
        ) : (
          <span>Lihat rincian cabang ›</span>
        )}
      </button>

      {/* Modal Drawer jika tombol di-klik */}
      {isExpanded && (
        <MultiBranchExpandedPanel
          branches={branches}
          totalCount={row.totalComplaints}
          terminalName={row.terminal}
          onClose={() => setIsExpanded(false)}
        />
      )}
    </div>
  );
}

// ==========================================
// 4. KOMPONEN UTAMA TABEL MONITORING ATM
// ==========================================
export default function AtmMonitoringTable() {
  return (
    <div className="max-w-7xl mx-auto p-6 font-sans antialiased text-slate-800">
      
      {/* Header Info */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-extrabold text-navy flex items-center gap-2">
            <Building2 className="w-6 h-6 text-brand-blue" />
            <span>Monitoring Keluhan Mesin ATM & Multi-Cabang</span>
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Redesain visual hierarchy kolom Kantor Cabang Pengelola dengan mini-bar proporsional dan progressive disclosure modal.
          </p>
        </div>

        <div className="flex items-center gap-2 text-xs bg-sky-50 border border-sky-200 text-sky-900 px-3.5 py-2 rounded-xl">
          <Info className="w-4 h-4 text-sky-600 shrink-0" />
          <span>Row height tetap ramping dan seragam tanpa melar 8x.</span>
        </div>
      </div>

      {/* Table Container Card */}
      <div className="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        
        {/* Table Top Header Bar */}
        <div className="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
          <div className="flex items-center gap-2">
            <h2 className="text-sm font-bold text-navy">Daftar Terminal & Agregasi Keluhan</h2>
            <span className="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold text-[11px]">
              {DUMMY_TABLE_ROWS.length} Unit / Grup
            </span>
          </div>
        </div>

        {/* Main Table */}
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-xs">
            <thead>
              <tr className="bg-slate-100/75 border-b-2 border-slate-200">
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider w-[50px] text-center">
                  No
                </th>
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider w-[180px]">
                  Kode / Nama Terminal ATM
                </th>
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider w-[320px]">
                  Kantor Cabang Pengelola
                </th>
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider text-center w-[130px]">
                  Jumlah Keluhan
                </th>
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider text-right w-[160px]">
                  Total Nominal (Rp)
                </th>
                <th className="px-4.5 py-3.5 text-slate-600 font-bold text-xs uppercase tracking-wider min-w-[220px]">
                  Jenis Gangguan Terbanyak
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {DUMMY_TABLE_ROWS.map((row, index) => {
                return (
                  <tr key={row.id} className="hover:bg-slate-50/80 transition-colors align-top">
                    {/* No */}
                    <td className="px-4.5 py-4 text-center font-semibold text-slate-500">
                      {index + 1}
                    </td>

                    {/* Terminal Name */}
                    <td className="px-4.5 py-4">
                      <div className="font-bold text-navy text-sm flex items-center gap-1.5">
                        {row.type === 'off_us' && <CreditCard className="w-4 h-4 text-amber-600 shrink-0" />}
                        {row.type === 'digital' && <Smartphone className="w-4 h-4 text-indigo-600 shrink-0" />}
                        {row.type === 'atm' && <Building2 className="w-4 h-4 text-sky-600 shrink-0" />}
                        <span>{row.terminal}</span>
                      </div>
                      <div className="text-[11px] text-slate-400 mt-0.5">
                        {row.category === 'multi' ? 'Grup Kanal Transaksi' : 'Mesin ATM Fisik'}
                      </div>
                    </td>

                    {/* Kantor Cabang Pengelola (REDESIGNED CELL) */}
                    <td className="px-4.5 py-3.5">
                      <BranchManagerCell row={row} />
                    </td>

                    {/* Jumlah Keluhan */}
                    <td className="px-4.5 py-4 text-center">
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-100 text-sky-800 font-extrabold text-xs">
                        <TrendingUp className="w-3.5 h-3.5 text-sky-600" />
                        <span>{row.totalComplaints} Kasus</span>
                      </span>
                    </td>

                    {/* Total Nominal */}
                    <td className="px-4.5 py-4 text-right font-extrabold text-navy text-sm tabular-nums">
                      Rp {row.totalNominal.toLocaleString('id-ID')}
                    </td>

                    {/* Jenis Gangguan Terbanyak */}
                    <td className="px-4.5 py-4">
                      <span className="text-xs text-slate-600 leading-relaxed font-medium">
                        {row.dominantIssues}
                      </span>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {/* Table Footer */}
        <div className="px-6 py-3.5 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
          <span>Menampilkan 5 dari 5 data terminal</span>
          <span className="font-semibold text-navy">Bank Sulteng E-Jurnal System</span>
        </div>

      </div>
    </div>
  );
}
