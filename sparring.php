<?php
$pageTitle = 'Papan Sparring - Cari Lawan Futsal';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$pesanSukses = '';
$pesanError = '';

// Handle tambah sparring baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_sparring'])) {
    $namaTim = cleanInput($_POST['nama_tim']);
    $namaKontak = cleanInput($_POST['nama_kontak']);
    $noWa = cleanInput($_POST['no_wa']);
    $tanggal = cleanInput($_POST['tanggal']);
    $jam = cleanInput($_POST['jam']);
    $lapanganId = !empty($_POST['lapangan_id']) ? (int)$_POST['lapangan_id'] : null;
    $levelTim = cleanInput($_POST['level_tim']);
    $catatan = cleanInput($_POST['catatan']);

    if (empty($namaTim) || empty($namaKontak) || empty($noWa) || empty($tanggal) || empty($jam)) {
        $pesanError = 'Harap isi semua kolom wajib!';
    } else {
        $stmt = $db->prepare("INSERT INTO sparring (nama_tim, nama_kontak, no_wa, tanggal, jam, lapangan_id, level_tim, catatan, status) 
                              VALUES (:tim, :kontak, :wa, :tgl, :jam, :lap, :lvl, :catatan, 'terbuka')");
        $stmt->execute([
            ':tim'     => $namaTim,
            ':kontak'  => $namaKontak,
            ':wa'      => $noWa,
            ':tgl'     => $tanggal,
            ':jam'     => $jam,
            ':lap'     => $lapanganId,
            ':lvl'     => $levelTim,
            ':catatan' => $catatan
        ]);
        $pesanSukses = 'Jadwal sparring tim Anda berhasil dipasang!';
    }
}

// Ambil daftar lapangan untuk dropdown
$stmtLap = $db->query("SELECT id, nama FROM lapangan ORDER BY id ASC");
$lapanganList = $stmtLap->fetchAll();

// Ambil daftar sparring terbuka
$stmtSparring = $db->query("SELECT s.*, l.nama AS nama_lapangan 
                           FROM sparring s 
                           LEFT JOIN lapangan l ON s.lapangan_id = l.id 
                           WHERE s.status = 'terbuka' AND s.tanggal >= CURDATE() 
                           ORDER BY s.tanggal ASC, s.created_at DESC");
$sparringList = $stmtSparring->fetchAll();
?>

<div class="bg-slate-100 py-10 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Komunitas & Pertandingan</span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Papan Sparring (Cari Lawan)</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1">Temukan lawan tanding selevel untuk uji coba strategi dan silaturahmi antar tim.</p>
            </div>
            <a href="#formSparring" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs sm:text-sm px-5 py-3 rounded-xl transition shadow-md shadow-futsal-600/30 flex items-center gap-2 self-start md:self-auto">
                <i class="fa-solid fa-plus-circle"></i>
                <span>Pasang Tim Anda</span>
            </a>
        </div>

        <?php if ($pesanSukses): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-sm mb-6 flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-xl text-emerald-600"></i>
                <div><?= $pesanSukses ?></div>
            </div>
        <?php endif; ?>

        <?php if ($pesanError): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-sm mb-6 flex items-center gap-3">
                <i class="fa-solid fa-circle-exclamation text-xl text-rose-600"></i>
                <div><?= $pesanError ?></div>
            </div>
        <?php endif; ?>

        <!-- Grid Sparring List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php if (empty($sparringList)): ?>
                <div class="col-span-full bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm text-slate-500">
                    <i class="fa-solid fa-handshake-angle text-5xl text-slate-300 mb-3"></i>
                    <h3 class="font-bold text-slate-800 text-lg">Belum Ada Tim Yang Mencari Lawan</h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto mt-1 mb-5">Jadilah tim pertama yang menantang tim lain bermain di Fun Futsal!</p>
                    <a href="#formSparring" class="bg-futsal-600 text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-futsal-700 transition">
                        Pasang Info Sparring
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($sparringList as $sp): ?>
                    <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="bg-futsal-50 text-futsal-700 border border-futsal-200 text-[11px] font-bold px-3 py-1 rounded-full">
                                    <?= htmlspecialchars($sp['level_tim']) ?>
                                </span>
                                <span class="text-xs text-slate-500 font-semibold flex items-center gap-1">
                                    <i class="fa-regular fa-calendar text-futsal-600"></i> <?= date('d M Y', strtotime($sp['tanggal'])) ?>
                                </span>
                            </div>

                            <h3 class="text-xl font-black text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-futsal-600 text-base"></i>
                                <?= htmlspecialchars($sp['nama_tim']) ?>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Kontak: <strong class="text-slate-700"><?= htmlspecialchars($sp['nama_kontak']) ?></strong></p>

                            <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 my-4 space-y-1.5 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="fa-regular fa-clock text-futsal-600 w-4 text-center"></i>
                                    <span>Jam: <b><?= htmlspecialchars($sp['jam']) ?></b></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-location-dot text-rose-500 w-4 text-center"></i>
                                    <span>Lokasi: <b><?= htmlspecialchars($sp['nama_lapangan'] ?: 'Fun Futsal') ?></b></span>
                                </div>
                            </div>

                            <p class="text-slate-600 text-xs italic bg-amber-50/50 p-3 rounded-xl border border-amber-100">
                                "<?= htmlspecialchars($sp['catatan']) ?>"
                            </p>
                        </div>

                        <a href="https://wa.me/<?= sanitizeWhatsApp($sp['no_wa']) ?>?text=Halo%20<?= urlencode($sp['nama_kontak']) ?>%20dari%20tim%20<?= urlencode($sp['nama_tim']) ?>,%20kami%20tertarik%20untuk%20sparring%20futsal%20pada%20tanggal%20<?= urlencode($sp['tanggal']) ?>.%20Apakah%20masih%20tersedia?" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-2xl text-center text-xs transition flex items-center justify-center gap-2 shadow">
                            <i class="fa-brands fa-whatsapp text-sm"></i>
                            <span>Ajak Sparring (WhatsApp)</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Form Tambah Sparring -->
        <div id="formSparring" class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200 shadow-xl max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Buat Tantangan</span>
                <h2 class="text-2xl font-black text-slate-900 mt-1">Pasang Postingan Cari Lawan</h2>
                <p class="text-slate-500 text-xs sm:text-sm mt-1">Isi formulir di bawah ini agar tim lain bisa menghubungi Anda via WhatsApp.</p>
            </div>

            <form action="sparring.php" method="POST" class="space-y-4">
                <input type="hidden" name="tambah_sparring" value="1">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Tim Futsal <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_tim" required placeholder="Contoh: Barata FC" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Kontak / Kapten <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_kontak" required placeholder="Contoh: Coach Ardi" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                        <input type="tel" name="no_wa" required placeholder="Contoh: 081234567890" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Level Permainan Tim <span class="text-rose-500">*</span></label>
                        <select name="level_tim" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                            <option value="Fun / Santai">Fun / Santai (Bermain santai & keringat)</option>
                            <option value="Menengah" selected>Menengah (Sudah terbiasa taktik)</option>
                            <option value="Kompetitif / Pro">Kompetitif / Pro (Persiapan Turnamen)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tanggal Main <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Perkiraan Jam <span class="text-rose-500">*</span></label>
                        <input type="text" name="jam" required placeholder="Contoh: 19:00 - 21:00" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pilihan Lapangan</label>
                        <select name="lapangan_id" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                            <option value="">Bebas / Terserah</option>
                            <?php foreach ($lapanganList as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan / Deskripsi Tambahan</label>
                    <textarea name="catatan" rows="3" placeholder="Contoh: Sistem sewa lapangan 50:50, tim kami umur 20-30 thn, main santai tanpa sleding." class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full bg-futsal-600 hover:bg-futsal-700 text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-futsal-600/30 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Pasang Postingan Sparring</span>
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
