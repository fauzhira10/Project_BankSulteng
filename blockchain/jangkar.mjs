/**
 * Penghubung aplikasi Laravel dengan kontrak JangkarAudit di Ethereum.
 *
 * Dipanggil oleh App\Services\JangkarEthereumService lewat Process, dan bisa juga
 * dijalankan manual dari folder ini:
 *
 *   node jangkar.mjs deploy                          kompilasi + deploy kontrak baru
 *   node jangkar.mjs info                            jaringan, saldo dompet, isi kontrak
 *   node jangkar.mjs kirim <nomor> <hash> <merkle>   jangkarkan satu blok
 *   node jangkar.mjs baca <nomor> [nomor ...]        baca hash blok yang tercatat
 *
 * Setiap perintah mencetak SATU baris JSON ke stdout; PHP hanya membaca baris itu.
 * Konfigurasi dibaca dari .env aplikasi (satu folder di atas): ETH_RPC_URL,
 * ETH_KUNCI_PRIVAT, ETH_ALAMAT_KONTRAK, dan opsional ETH_BLOK_DEPLOY.
 * Kunci privat tidak pernah dicetak.
 */
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { ethers } from 'ethers';

const DIR = path.dirname(fileURLToPath(import.meta.url));

// Variabel yang dikirim PHP lewat Process tetap diutamakan; .env hanya pelengkap
// saat skrip dijalankan manual.
try {
    process.loadEnvFile(path.join(DIR, '..', '.env'));
} catch {
    // .env tidak ada: andalkan variabel lingkungan yang sudah terpasang.
}

const ABI = [
    'function pemilik() view returns (address)',
    'function jumlahBlok() view returns (uint256)',
    'function jangkarkan(uint256 nomorBlok, bytes32 hashBlok, bytes32 merkleRoot)',
    'function ambil(uint256 nomorBlok) view returns (bytes32 hashBlok, bytes32 merkleRoot, uint256 waktu)',
    'event BlokDijangkarkan(uint256 indexed nomorBlok, bytes32 hashBlok, bytes32 merkleRoot, uint256 waktu)',
    'error BukanPemilik()',
    'error HashKosong()',
    'error SudahDijangkarkan(uint256 nomorBlok)',
];

function keluar(data, kode = 0) {
    process.stdout.write(JSON.stringify(data) + '\n');
    process.exit(kode);
}

function wajib(nama) {
    const nilai = (process.env[nama] ?? '').trim();
    if (nilai === '') {
        throw new Error(`${nama} belum diisi di .env`);
    }

    return nilai;
}

function keBytes32(hex) {
    if (!/^[0-9a-fA-F]{64}$/.test(hex ?? '')) {
        throw new Error(`Hash tidak sah (harus 64 karakter heksadesimal): ${hex}`);
    }

    return '0x' + hex.toLowerCase();
}

const dariBytes32 = (nilai) => nilai.slice(2).toLowerCase();

const penyedia = () => new ethers.JsonRpcProvider(wajib('ETH_RPC_URL'));
const dompet = (p) => new ethers.Wallet(wajib('ETH_KUNCI_PRIVAT'), p);
const kontrak = (pelari) => new ethers.Contract(wajib('ETH_ALAMAT_KONTRAK'), ABI, pelari);

async function namaJaringan(p) {
    const jaringan = await p.getNetwork();

    return jaringan.name === 'unknown' ? `chain-${jaringan.chainId}` : jaringan.name;
}

async function kompilasi() {
    const { default: solc } = await import('solc');
    const sumber = readFileSync(path.join(DIR, 'contracts', 'JangkarAudit.sol'), 'utf8');

    const masukan = {
        language: 'Solidity',
        sources: { 'JangkarAudit.sol': { content: sumber } },
        settings: {
            optimizer: { enabled: true, runs: 200 },
            // paris: tanpa opcode PUSH0/MCOPY, jadi tetap jalan di simpul lokal yang lebih tua.
            evmVersion: 'paris',
            outputSelection: { '*': { '*': ['abi', 'evm.bytecode.object'] } },
        },
    };

    const keluaran = JSON.parse(solc.compile(JSON.stringify(masukan)));
    const galat = (keluaran.errors ?? []).filter((e) => e.severity === 'error');
    if (galat.length > 0) {
        throw new Error('Kompilasi gagal: ' + galat.map((e) => e.formattedMessage).join('\n'));
    }

    const hasil = keluaran.contracts['JangkarAudit.sol'].JangkarAudit;
    const artefak = { abi: hasil.abi, bytecode: '0x' + hasil.evm.bytecode.object, solc: solc.version() };

    mkdirSync(path.join(DIR, 'build'), { recursive: true });
    writeFileSync(path.join(DIR, 'build', 'JangkarAudit.json'), JSON.stringify(artefak, null, 2));

    return artefak;
}

async function deploy() {
    const p = penyedia();
    const d = dompet(p);
    const { abi, bytecode } = await kompilasi();

    const pabrik = new ethers.ContractFactory(abi, bytecode, d);
    const k = await pabrik.deploy();
    const tx = k.deploymentTransaction();
    const struk = await tx.wait(1);

    return {
        ok: true,
        jaringan: await namaJaringan(p),
        alamat: await k.getAddress(),
        tx: tx.hash,
        blok_eth: struk.blockNumber,
        pemilik: d.address,
    };
}

async function info() {
    const p = penyedia();
    const hasil = { ok: true, jaringan: await namaJaringan(p) };

    if (process.env.ETH_KUNCI_PRIVAT) {
        const d = dompet(p);
        hasil.dompet = d.address;
        hasil.saldo_eth = ethers.formatEther(await p.getBalance(d.address));
    }

    if (process.env.ETH_ALAMAT_KONTRAK) {
        const k = kontrak(p);
        hasil.kontrak = await k.getAddress();
        hasil.pemilik = await k.pemilik();
        hasil.jumlah_blok = Number(await k.jumlahBlok());
    }

    return hasil;
}

/**
 * Cari transaksi yang dulu menjangkarkan blok ini. Dipakai bila kirim diulang
 * setelah transaksi sebelumnya sebenarnya sudah masuk (mis. PHP terputus sebelum
 * sempat menyimpan hasilnya). RPC publik sering membatasi rentang getLogs, jadi
 * kegagalan di sini tidak dianggap galat.
 */
async function cariTransaksi(k, nomor) {
    try {
        const dari = Number(process.env.ETH_BLOK_DEPLOY || 0);
        const log = await k.queryFilter(k.filters.BlokDijangkarkan(nomor), dari, 'latest');
        if (log.length > 0) {
            return { tx: log[0].transactionHash, blok_eth: log[0].blockNumber };
        }
    } catch {
        // abaikan
    }

    return { tx: null, blok_eth: null };
}

async function kirim(nomorTeks, hashHex, merkleHex) {
    const nomor = BigInt(nomorTeks);
    const hashBlok = keBytes32(hashHex);
    const merkleRoot = keBytes32(merkleHex);

    const p = penyedia();
    const k = kontrak(dompet(p));
    const jaringan = await namaJaringan(p);

    const ada = await k.ambil(nomor);
    if (ada.hashBlok !== ethers.ZeroHash) {
        if (ada.hashBlok.toLowerCase() === hashBlok) {
            return { ok: true, status: 'sudah_ada', jaringan, ...(await cariTransaksi(k, nomor)) };
        }

        // Nomor ini sudah dijangkarkan dengan hash LAIN. Kontrak tidak mengizinkan
        // penimpaan, dan justru ini tanda bahwa blok di server telah berubah.
        return {
            ok: false,
            status: 'konflik',
            jaringan,
            hash_onchain: dariBytes32(ada.hashBlok),
            galat: `Blok #${nomor} sudah tercatat di Ethereum dengan hash berbeda — blok di server telah berubah.`,
        };
    }

    const tx = await k.jangkarkan(nomor, hashBlok, merkleRoot);
    const struk = await tx.wait(1);

    return { ok: true, status: 'terkirim', jaringan, tx: struk.hash, blok_eth: struk.blockNumber };
}

async function baca(...daftarNomor) {
    if (daftarNomor.length === 0) {
        throw new Error('Sebutkan minimal satu nomor blok.');
    }

    const p = penyedia();
    const k = kontrak(p);
    const blok = {};

    for (const nomor of daftarNomor) {
        const r = await k.ambil(BigInt(nomor));
        const ada = r.hashBlok !== ethers.ZeroHash;

        blok[nomor] = {
            ada,
            hash_blok: ada ? dariBytes32(r.hashBlok) : null,
            merkle_root: ada ? dariBytes32(r.merkleRoot) : null,
            waktu: ada ? Number(r.waktu) : null,
        };
    }

    return { ok: true, jaringan: await namaJaringan(p), blok };
}

const [perintah, ...argumen] = process.argv.slice(2);

try {
    let hasil;
    switch (perintah) {
        case 'deploy': hasil = await deploy(); break;
        case 'info': hasil = await info(); break;
        case 'kirim': hasil = await kirim(...argumen); break;
        case 'baca': hasil = await baca(...argumen); break;
        default:
            throw new Error('Perintah tidak dikenal. Pakai: deploy | info | kirim <nomor> <hash> <merkle> | baca <nomor> [nomor ...]');
    }
    keluar(hasil, hasil.ok ? 0 : 2);
} catch (e) {
    keluar({ ok: false, galat: e.shortMessage ?? e.message }, 1);
}
