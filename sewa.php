<?php
$pageTitle = 'Sewa Lapangan Futsal - Fun Futsal';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Ambil list semua lapangan (6 Lapangan)
$stmtAllLap = $db->query("SELECT * FROM lapangan ORDER BY id ASC");
$semuaLapangan = $stmtAllLap->fetchAll();

// Lapangan terpilih
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : ($semuaLapangan[0]['id'] ?? 1);
$stmtSelected = $db->prepare("SELECT * FROM lapangan WHERE id = :id");
$stmtSelected->execute([':id' => $selectedId]);
$lapangan = $stmtSelected->fetch();

if (!$lapangan) {
    $lapangan = $semuaLapangan[0] ?? null;
}

$isDitutup = (($lapangan['status'] ?? 'tersedia') === 'ditutup' || ($lapangan['kondisi'] ?? 'Baik') === 'Buruk');

// Tanggal sewa terpilih (default hari ini)
$selectedDate = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$selectedDateEnd = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : $selectedDate;

// Hitung jumlah hari
$dateStartObj = new DateTime($selectedDate);
$dateEndObj = new DateTime($selectedDateEnd);
if ($dateEndObj < $dateStartObj) {
    $selectedDateEnd = $selectedDate;
    $dateEndObj = new DateTime($selectedDateEnd);
}
$diffDays = $dateStartObj->diff($dateEndObj)->days + 1;

// Ambil slot jam yang sudah di-booking pada tanggal & lapangan ini
$stmtBooked = $db->prepare("SELECT jam_mulai, jam_selesai, status FROM booking 
                           WHERE lapangan_id = :lapangan_id 
                             AND tanggal = :tanggal 
                             AND status IN ('pending', 'confirmed')");
$stmtBooked->execute([':lapangan_id' => $lapangan['id'], ':tanggal' => $selectedDate]);
$bookedRows = $stmtBooked->fetchAll();

$bookedHours = [];
foreach ($bookedRows as $b) {
    $startH = (int)date('H', strtotime($b['jam_mulai']));
    $endH = (int)date('H', strtotime($b['jam_selesai']));
    for ($h = $startH; $h < $endH; $h++) {
        $bookedHours[] = sprintf('%02d:00', $h);
    }
}

// Auto-populate data jika user login
$defaultNama = $_SESSION['user_name'] ?? 'Pelanggan Fun Futsal';
$defaultHp = $_SESSION['user_phone'] ?? '081234567890';
$defaultTim = $_SESSION['user_team'] ?? '';
$userIsMember = !empty($_SESSION['is_member']);
$userMemberCode = $_SESSION['kode_member'] ?? '';

// Ambil Ulasan Lapangan ini
$stmtUlasan = $db->prepare("SELECT * FROM ulasan WHERE lapangan_id = :lid ORDER BY id DESC LIMIT 5");
$stmtUlasan->execute([':lid' => $lapangan['id']]);
$daftarUlasan = $stmtUlasan->fetchAll();
$avgRating = 5.0;

// Array fasilitas
$fasilitasList = !empty($lapangan['fasilitas']) ? array_map('trim', explode(',', $lapangan['fasilitas'])) : [
    'Lantai Standar FIFA Grade',
    'Bola Futsal Match Pro Gratis',
    'Rompi Tanding 2 Warna Lengkap',
    'Ruang Ganti & Shower Panas/Dingin',
    'Papan Skor Digital & Sound System',
    'Free Wi-Fi 100 Mbps',
    'Tribun Penonton',
    'Musholla & Parkir Luas'
];
?>

<div class="bg-slate-100 py-4 sm:py-8 pb-32 md:pb-12">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        
        <!-- Mobile Header Bar: Kembali & Judul -->
        <div class="flex items-center justify-between mb-4">
            <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-futsal-600 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs">
                <i class="fa-solid fa-arrow-left"></i> <span>Kembali ke Beranda</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-700 bg-white border border-slate-200 px-3 py-1 rounded-full">
                    <?= htmlspecialchars($lapangan['kategori'] ?? 'Indoor') ?>
                </span>
                <span class="text-xs font-bold text-futsal-700 bg-futsal-100 px-3 py-1 rounded-full flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i> <?= htmlspecialchars($lapangan['jenis']) ?>
                </span>
            </div>
        </div>

        <!-- 6 Tabs Pilihan Lapangan (3 Vinyl, 2 Rumput, 1 Turnamen) -->
        <div class="flex items-center gap-2.5 overflow-x-auto no-scrollbar pb-2 mb-4 -mx-3 px-3 sm:mx-0 sm:px-0">
            <?php foreach ($semuaLapangan as $item): ?>
                <?php 
                $itemTutup = (($item['status'] ?? 'tersedia') === 'ditutup' || ($item['kondisi'] ?? 'Baik') === 'Buruk');
                $isSpecial = !empty($item['is_turnamen']);
                ?>
                <a href="sewa.php?id=<?= $item['id'] ?>&tanggal=<?= urlencode($selectedDate) ?>&tanggal_selesai=<?= urlencode($selectedDateEnd) ?>" 
                   class="whitespace-nowrap px-4 py-2.5 rounded-2xl font-bold text-xs transition flex items-center gap-2 flex-shrink-0 relative <?= $item['id'] == $lapangan['id'] ? ($isSpecial ? 'bg-amber-500 text-slate-950 font-black shadow-md' : 'bg-futsal-600 text-white shadow-md shadow-futsal-600/30') : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200' ?>">
                    <i class="fa-solid <?= $isSpecial ? 'fa-trophy text-amber-900' : 'fa-futbol text-[11px]' ?>"></i>
                    <span><?= htmlspecialchars($item['nama']) ?></span>
                    <?php if ($itemTutup): ?>
                        <span class="bg-rose-500 text-white text-[9px] px-1.5 py-0.2 rounded-full font-bold ml-1">Tutup</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Banner Jika Lapangan Sedang Ditutup Sementara -->
        <?php if ($isDitutup): ?>
            <div class="bg-rose-50 border-2 border-rose-300 text-rose-800 p-4 sm:p-5 rounded-3xl mb-6 shadow-sm flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 text-xl font-bold">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-rose-900">LAPANGAN SEDANG DITUTUP SEMENTARA</h3>
                    <p class="text-xs text-rose-700 mt-0.5 leading-relaxed">
                        Lapangan ini saat ini ditutup sementara oleh pengelola karena sedang dalam pemeliharaan (Status Kondisi: <strong><?= htmlspecialchars($lapangan['kondisi']) ?></strong>).
                        User tidak dapat melakukan reservasi untuk lapangan ini. Silakan pilih lapangan lain pada tab di atas.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Banner Khusus Lapangan Turnamen -->
        <?php if (!empty($lapangan['is_turnamen'])): ?>
            <div class="bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 text-slate-950 p-4 sm:p-5 rounded-3xl mb-6 shadow-lg flex items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-slate-950 text-amber-400 flex items-center justify-center text-2xl flex-shrink-0 shadow">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-900 bg-amber-300/80 px-2 py-0.5 rounded-md inline-block mb-0.5">
                            Arena Resmi Turnamen & Kejuaraan
                        </div>
                        <h2 class="text-base sm:text-lg font-black text-slate-950 leading-tight">FIFA Grand Arena Turnamen Pro</h2>
                        <p class="text-xs text-slate-800 font-medium">Dilengkapi Tribun 500 Penonton, Lampu Siaran TV, Ruang Wasit & VAR System Ready.</p>
                    </div>
                </div>
                <span class="hidden sm:inline-block bg-slate-950 text-white font-bold text-xs px-4 py-2 rounded-xl shadow">
                    Turnamen Ready
                </span>
            </div>
        <?php endif; ?>

        <form action="pembayaran.php" method="POST" id="formBooking">
            <input type="hidden" name="lapangan_id" value="<?= $lapangan['id'] ?>">
            <input type="hidden" name="tanggal" value="<?= $selectedDate ?>">
            <input type="hidden" name="tanggal_selesai" value="<?= $selectedDateEnd ?>">
            <input type="hidden" name="total_hari" id="inputTotalHari" value="<?= $diffDays ?>">
            <input type="hidden" name="jam_mulai" id="inputJamMulai" value="">
            <input type="hidden" name="jam_selesai" id="inputJamSelesai" value="">
            <input type="hidden" name="durasi" id="inputDurasi" value="0">
            <input type="hidden" name="total_harga" id="inputTotalHarga" value="0">
            <input type="hidden" name="kode_member" id="inputKodeMember" value="<?= htmlspecialchars($userMemberCode) ?>">
            <input type="hidden" name="diskon" id="inputDiskon" value="0">

            <!-- Data Pemesan Otomatis (Tanpa Perlu Diketik) -->
            <input type="hidden" name="nama_pemesan" value="<?= htmlspecialchars($defaultNama) ?>">
            <input type="hidden" name="no_hp" value="<?= htmlspecialchars($defaultHp) ?>">
            <input type="hidden" name="nama_tim" value="<?= htmlspecialchars($defaultTim) ?>">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-8 items-start">
                
                <!-- Kolom Kiri: Detail Lapangan, Deskripsi, Fasilitas, Slot Jam & Ulasan -->
                <div class="lg:col-span-7 space-y-4 sm:space-y-6">
                    
                    <!-- Card Lapangan: Foto, Deskripsi & Fasilitas Lengkap -->
                    <div class="bg-white rounded-3xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                        <div class="relative rounded-2xl overflow-hidden aspect-[16/10] mb-4 border border-slate-100 bg-slate-900">
                            <img src="<?= htmlspecialchars($lapangan['foto']) ?>?v=<?= file_exists($lapangan['foto']) ? filemtime($lapangan['foto']) : '2' ?>" alt="<?= htmlspecialchars($lapangan['nama']) ?>" class="w-full h-full object-cover">
                            
                            <div class="absolute bottom-2.5 left-2.5 right-2.5 flex justify-between items-end">
                                <span class="bg-slate-900/80 backdrop-blur text-white font-bold text-[10px] sm:text-xs px-2.5 py-1 rounded-lg">
                                    <i class="fa-solid fa-ruler-combined mr-1"></i> <?= htmlspecialchars($lapangan['panjang']) ?> x <?= htmlspecialchars($lapangan['lebar']) ?>
                                </span>
                                <span class="bg-futsal-600 text-white font-extrabold text-[11px] sm:text-xs px-2.5 py-1 rounded-lg shadow">
                                    Mulai <?= formatRupiah($lapangan['harga_siang']) ?>/jam
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-futsal-100 text-futsal-800">
                                <?= htmlspecialchars($lapangan['kategori'] ?? 'Indoor') ?>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                <?= htmlspecialchars($lapangan['jenis']) ?>
                            </span>
                            <!-- Kondisi Badge -->
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $lapangan['kondisi'] === 'Buruk' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                Kondisi: <?= htmlspecialchars($lapangan['kondisi']) ?>
                            </span>
                        </div>

                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight"><?= htmlspecialchars($lapangan['nama']) ?></h1>
                        
                        <!-- Deskripsi Lapangan -->
                        <div class="mt-3 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-align-left text-futsal-600"></i> Deskripsi Lapangan
                            </h3>
                            <p class="text-slate-600 text-xs sm:text-sm leading-relaxed"><?= htmlspecialchars($lapangan['deskripsi']) ?></p>
                        </div>

                        <!-- Fasilitas Lapangan Lengkap (Selayaknya Turnamen / Pro) -->
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-amber-500"></i> Fasilitas Lapangan Termasuk:
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <?php foreach ($fasilitasList as $fas): ?>
                                    <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100 text-slate-700">
                                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                        <span class="font-medium text-xs leading-tight"><?= htmlspecialchars($fas) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Info Tarif Siang vs Malam -->
                        <div class="grid grid-cols-2 gap-2.5 pt-4 mt-4 border-t border-slate-100 text-xs">
                            <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                <span class="text-slate-400 block text-[10px] font-semibold uppercase">Tarif Siang (08:00 - 17:00)</span>
                                <span class="font-black text-slate-900 text-base mt-0.5 block"><?= formatRupiah($lapangan['harga_siang']) ?><span class="text-[10px] font-normal text-slate-500"> / jam</span></span>
                            </div>
                            <div class="bg-futsal-50/60 p-3 rounded-2xl border border-futsal-100">
                                <span class="text-futsal-700 block text-[10px] font-semibold uppercase">Tarif Malam (17:00 - 24:00)</span>
                                <span class="font-black text-futsal-700 text-base mt-0.5 block"><?= formatRupiah($lapangan['harga_malam']) ?><span class="text-[10px] font-normal text-futsal-500"> / jam</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Pilihan Tanggal & Multi-Day Date Picker (Cukup Tanggal Saja & Bisa Lebih Dari 1 Hari) -->
                    <div class="bg-white rounded-3xl p-4 sm:p-6 border border-slate-200 shadow-sm">
                        
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-slate-800 uppercase mb-2">
                                <i class="fa-regular fa-calendar text-futsal-600 mr-1"></i> Pilih Tanggal Main (Bisa Lebih Dari 1 Hari)
                            </label>

                            <!-- Date Range Picker: Tanggal Mulai & Tanggal Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Mulai Tanggal:</span>
                                    <input type="date" id="pickerDateStart" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" 
                                           onchange="updateDateRange()"
                                           class="w-full bg-white border border-slate-300 text-slate-900 text-xs font-bold rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Sampai Tanggal (Multi-Hari / Turnamen):</span>
                                    <input type="date" id="pickerDateEnd" value="<?= htmlspecialchars($selectedDateEnd) ?>" min="<?= date('Y-m-d') ?>" 
                                           onchange="updateDateRange()"
                                           class="w-full bg-white border border-slate-300 text-slate-900 text-xs font-bold rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                                </div>
                            </div>

                            <div class="mt-2 text-xs font-semibold text-futsal-700 flex items-center justify-between">
                                <span>Total Booking: <strong><?= $diffDays ?> Hari</strong> (<?= formatTanggalIndo($selectedDate) ?><?= $diffDays > 1 ? ' s/d ' . formatTanggalIndo($selectedDateEnd) : '' ?>)</span>
                                <?php if ($diffDays > 1): ?>
                                    <span class="bg-amber-100 text-amber-900 px-2.5 py-0.5 rounded-full text-[10px] font-bold">Multi-Hari Event / Turnamen</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Keterangan Warna Slot -->
                        <div class="flex items-center gap-3 text-[11px] mb-4 p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="flex items-center gap-1 text-slate-600"><span class="w-3 h-3 rounded bg-white border-2 border-slate-300 inline-block"></span> Kosong</span>
                            <span class="flex items-center gap-1 text-slate-900 font-bold"><span class="w-3 h-3 rounded bg-futsal-600 inline-block"></span> Dipilih</span>
                            <span class="flex items-center gap-1 text-slate-400"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-300 inline-block"></span> Terisi</span>
                        </div>

                        <!-- Grid Tombol Slot Jam -->
                        <?php if ($isDitutup): ?>
                            <div class="p-8 text-center text-rose-500 font-bold text-xs bg-rose-50 rounded-2xl border border-rose-200">
                                <i class="fa-solid fa-lock text-3xl mb-2 text-rose-400 block"></i>
                                Pemilihan jam tidak tersedia karena lapangan ditutup sementara.
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <?php 
                                for ($jam = 8; $jam < 24; $jam++): 
                                    $timeStr = sprintf('%02d:00', $jam);
                                    $isBooked = in_array($timeStr, $bookedHours);
                                    $isNight = $jam >= (int)$lapangan['jam_malam_mulai'];
                                    $priceHour = $isNight ? (int)$lapangan['harga_malam'] : (int)$lapangan['harga_siang'];
                                ?>
                                    <?php if ($isBooked): ?>
                                        <button type="button" disabled class="p-2 sm:p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-400 text-xs font-semibold cursor-not-allowed text-center opacity-60">
                                            <span class="block font-bold text-xs"><?= $timeStr ?></span>
                                            <span class="text-[9px] text-rose-500 font-medium">Terisi</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                onclick="toggleSlot('<?= $timeStr ?>', <?= $jam ?>, <?= $priceHour ?>, this)"
                                                class="slot-btn p-2 sm:p-3 rounded-2xl bg-white hover:border-futsal-500 border-2 border-slate-200 text-slate-800 text-xs font-bold text-center transition active:scale-95 cursor-pointer shadow-xs"
                                                data-jam="<?= $jam ?>"
                                                data-timestr="<?= $timeStr ?>"
                                                data-price="<?= $priceHour ?>">
                                            <span class="block font-black text-xs sm:text-sm text-slate-900"><?= $timeStr ?></span>
                                            <span class="text-[10px] text-slate-500 font-normal block leading-tight mt-0.5"><?= formatRupiah($priceHour) ?></span>
                                        </button>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SECTION ULASAN LAPANGAN DARI USER -->
                    <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 class="text-sm font-black text-slate-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-star text-amber-400"></i>
                                    <span>Ulasan & Review Pemain</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">Pengalaman asli dari para member yang sudah bermain.</p>
                            </div>
                            <button type="button" onclick="bukaModalUlasan()" class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                <i class="fa-solid fa-pen"></i> Tulis Ulasan
                            </button>
                        </div>

                        <?php if (empty($daftarUlasan)): ?>
                            <p class="text-xs text-slate-400 italic text-center py-4">Belum ada ulasan untuk lapangan ini. Jadilah yang pertama memberi review!</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($daftarUlasan as $ul): ?>
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-black text-slate-800"><?= htmlspecialchars($ul['nama_user']) ?></span>
                                            <div class="text-amber-400 text-xs">
                                                <?php for ($i=0; $i<$ul['rating']; $i++): ?>★<?php endfor; ?>
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-600 italic">"<?= htmlspecialchars($ul['komentar']) ?>"</p>
                                        <span class="text-[10px] text-slate-400 block"><?= date('d M Y', strtotime($ul['created_at'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Kolom Kanan: Ringkasan Sewa, Diskon Membership & Checkout (Tanpa Form Data Pemesan) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm sticky top-24">
                        
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-receipt text-futsal-600"></i>
                                <span>Ringkasan Sewa</span>
                            </h2>
                            <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                Instant Booking
                            </span>
                        </div>

                        <!-- Info Akun Pemesan / Keterangan Wajib Login Sebelum Booking -->
                        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                            <div class="mt-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">Penyewa Terverifikasi:</span>
                                    <?php if ($userIsMember): ?>
                                        <span class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                            <i class="fa-solid fa-crown text-amber-500"></i> Member <?= strtoupper($_SESSION['tipe_member'] ?? 'Gold') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-bold text-futsal-700 bg-futsal-100 px-2 py-0.5 rounded-full">
                                            <i class="fa-solid fa-circle-check"></i> Pengguna Aktif
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm font-black text-slate-800 mt-1 flex items-center gap-2">
                                    <i class="fa-solid fa-user-circle text-futsal-600 text-base"></i>
                                    <span><?= htmlspecialchars($defaultNama) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    WhatsApp: <strong class="text-slate-700"><?= htmlspecialchars($defaultHp) ?></strong>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- KETERANGAN HARUS LOGIN ATAU DAFTAR TERLEBIH DAHULU -->
                            <div class="mt-4 p-4 rounded-2xl border-2 border-amber-300 bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-100/60 shadow-sm space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs flex-shrink-0">
                                        <i class="fa-solid fa-user-lock"></i>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 block">Status Pengguna</span>
                                        <h4 class="text-xs font-black text-slate-900 leading-tight">Belum Masuk Akun</h4>
                                    </div>
                                </div>
                                
                                <p class="text-xs text-amber-950 leading-relaxed font-medium">
                                    Demi ketepatan jadwal tanding, penerbitan invoice resmi, serta mendapatkan <strong>Diskon Member 15%</strong>, Anda wajib <strong>Masuk</strong> atau <strong>Daftar Akun Baru</strong> terlebih dahulu sebelum menyelesaikan pembayaran.
                                </p>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <a href="login.php?redirect=<?= urlencode('sewa.php?id=' . $lapangan['id'] . '&tanggal=' . $selectedDate) ?>" class="bg-futsal-600 hover:bg-futsal-700 text-white font-black py-2.5 px-3 rounded-xl text-center text-xs transition shadow-md shadow-futsal-600/20 flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-right-to-bracket"></i>
                                        <span>Masuk Akun</span>
                                    </a>
                                    <a href="daftar.php?redirect=<?= urlencode('sewa.php?id=' . $lapangan['id'] . '&tanggal=' . $selectedDate) ?>" class="bg-white hover:bg-slate-50 text-slate-800 border-2 border-slate-200 font-bold py-2.5 px-3 rounded-xl text-center text-xs transition shadow-xs flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-user-plus text-futsal-600"></i>
                                        <span>Daftar Baru</span>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Detail Booking Card -->
                        <div class="mt-4 space-y-2.5 text-xs text-slate-600">
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-400">Lapangan:</span>
                                <span class="font-bold text-slate-900"><?= htmlspecialchars($lapangan['nama']) ?></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-400">Tanggal Main:</span>
                                <span class="font-bold text-slate-900"><?= formatTanggalIndo($selectedDate) ?><?= $diffDays > 1 ? ' (' . $diffDays . ' Hari)' : '' ?></span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-400">Slot Jam:</span>
                                <span class="font-extrabold text-futsal-700" id="desktopSlotJam">Pilih jam main</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-slate-100">
                                <span class="text-slate-400">Durasi Per Hari:</span>
                                <span class="font-bold text-slate-900" id="desktopDurasi">0 Jam</span>
                            </div>
                        </div>

                        <!-- INPUT KODE MEMBERSHIP (FITUR DISKON MEMBER) -->
                        <div class="mt-4 p-3.5 bg-gradient-to-r from-amber-50 to-yellow-50 rounded-2xl border border-amber-200">
                            <label class="block text-[11px] font-black text-amber-950 uppercase mb-1.5 flex items-center justify-between">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-crown text-amber-500"></i> Kode Membership</span>
                                <span class="text-[10px] text-amber-700 font-bold">Diskon 15%</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="memberCodeInput" value="<?= htmlspecialchars($userMemberCode) ?>" placeholder="Contoh: MEMBER-GARUDA" class="w-full bg-white border border-amber-300 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 uppercase focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <button type="button" onclick="terapkanDiskonMember()" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl transition shadow-xs flex-shrink-0">
                                    Terapkan
                                </button>
                            </div>
                            <div id="memberDiscountBadge" class="hidden mt-2 text-[11px] text-emerald-700 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Kode Member Valid! Diskon 15% Diterapkan.
                            </div>
                        </div>

                        <!-- Catatan Tambahan (Opsional) -->
                        <div class="mt-3">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Catatan Tambahan (Opsional)</label>
                            <input type="text" name="catatan" placeholder="Contoh: Rompi ekstra / persiapan turnamen" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                        </div>

                        <!-- Kalkulasi Biaya & Diskon -->
                        <div class="mt-4 pt-4 border-t border-slate-200 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-500">
                                <span>Subtotal Tarif (x<span id="labelTotalHari"><?= $diffDays ?></span> Hari):</span>
                                <span class="font-bold text-slate-800" id="desktopSubtotal">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-emerald-600 font-bold hidden" id="rowDiskon">
                                <span>Potongan Diskon Member (15%):</span>
                                <span id="desktopDiskonVal">- Rp 0</span>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase block leading-none">Total Pembayaran</span>
                                    <span class="text-2xl font-black text-futsal-700 mt-1 block leading-tight" id="desktopTotalHarga">Rp 0</span>
                                </div>
                                <span class="text-[10px] text-slate-400">Garansi slot aman</span>
                            </div>

                            <button type="submit" id="desktopBtnSubmit" disabled class="w-full mt-3 bg-slate-300 text-slate-500 font-black py-4 px-4 rounded-2xl text-center text-sm transition shadow cursor-not-allowed flex items-center justify-center gap-2">
                                <span>Lanjut ke Pembayaran</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

            <!-- FIXED BOTTOM ACTION BAR ON MOBILE -->
            <div class="md:hidden fixed bottom-14 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 shadow-[0_-5px_20px_rgba(0,0,0,0.08)]">
                <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
                    <div>
                        <span class="text-[10px] text-slate-400 block leading-none">Total (<?= $diffDays ?> Hari)</span>
                        <div class="text-base font-black text-futsal-700 leading-tight mt-0.5" id="mobileTotalHarga">Rp 0</div>
                        <span class="text-[10px] text-slate-500 font-semibold" id="mobileSlotJam">Pilih jam main</span>
                    </div>

                    <button type="submit" id="mobileBtnSubmit" disabled class="bg-slate-300 text-slate-500 font-black text-xs px-5 py-3 rounded-xl transition shadow cursor-not-allowed flex items-center gap-2">
                        <span>Lanjut Bayar</span>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>

<!-- MODAL TULIS ULASAN LAPANGAN -->
<div id="modalUlasan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
            <h3 class="text-base font-black text-slate-900">Beri Ulasan Lapangan</h3>
            <button onclick="tutupModalUlasan()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form action="tambah_ulasan.php" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="lapangan_id" value="<?= $lapangan['id'] ?>">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Anda</label>
                <input type="text" name="nama_user" required value="<?= htmlspecialchars($defaultNama) ?>" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3.5 py-2.5">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Rating Bintang</label>
                <select name="rating" class="w-full bg-slate-50 border border-slate-200 text-xs font-bold rounded-xl px-3.5 py-2.5">
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Sangat Puas</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Bagus</option>
                    <option value="3">⭐⭐⭐ 3 - Cukup</option>
                    <option value="2">⭐⭐ 2 - Kurang</option>
                    <option value="1">⭐ 1 - Sangat Kurang</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Komentar / Review</label>
                <textarea name="komentar" required rows="3" placeholder="Ceritakan kondisi lantai, fasilitas, pencahayaan..." class="w-full bg-slate-50 border border-slate-200 text-xs rounded-xl px-3.5 py-2.5"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="tutupModalUlasan()" class="flex-1 bg-slate-100 text-slate-600 font-bold text-xs py-2.5 rounded-xl">Batal</button>
                <button type="submit" class="flex-1 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs py-2.5 rounded-xl transition">Kirim Ulasan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let selectedSlots = [];
    let slotPrices = {};
    let totalHari = <?= (int)$diffDays ?>;
    let isMemberDiscountApplied = false;
    let discountRate = 0.15; // 15% diskon member

    function updateDateRange() {
        const start = document.getElementById('pickerDateStart').value;
        const end = document.getElementById('pickerDateEnd').value;
        location.href = 'sewa.php?id=<?= $lapangan['id'] ?>&tanggal=' + start + '&tanggal_selesai=' + end;
    }

    function toggleSlot(timeStr, jam, price, btn) {
        <?php if ($isDitutup): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Lapangan Ditutup',
                text: 'Lapangan ini sedang ditutup sementara oleh pengelola.',
                confirmButtonColor: '#e11d48'
            });
            return;
        <?php endif; ?>

        const index = selectedSlots.indexOf(jam);
        if (index > -1) {
            selectedSlots.splice(index, 1);
            delete slotPrices[jam];
            btn.classList.remove('bg-futsal-600', 'text-white', 'border-futsal-600');
            btn.classList.add('bg-white', 'text-slate-800', 'border-slate-200');
        } else {
            selectedSlots.push(jam);
            selectedSlots.sort((a, b) => a - b);
            slotPrices[jam] = price;
            btn.classList.add('bg-futsal-600', 'text-white', 'border-futsal-600');
            btn.classList.remove('bg-white', 'text-slate-800', 'border-slate-200');
        }

        updateSummary();
    }

    function terapkanDiskonMember() {
        const code = document.getElementById('memberCodeInput').value.trim().toUpperCase();
        if (code === '') {
            Swal.fire('Info', 'Silakan masukkan kode membership Anda.', 'info');
            return;
        }

        // Cek kode member demo atau prefix MEMBER-
        if (code === 'MEMBER-GARUDA' || code.startsWith('MEMBER') || code.startsWith('FUN-')) {
            isMemberDiscountApplied = true;
            document.getElementById('inputKodeMember').value = code;
            document.getElementById('memberDiscountBadge').classList.remove('hidden');
            Swal.fire({
                icon: 'success',
                title: 'Diskon Member Berhasil!',
                text: 'Selamat, kode member valid! Anda mendapatkan potongan 15%.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            updateSummary();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Kode Tidak Dikenal',
                text: 'Kode membership tidak ditemukan atau sudah tidak aktif. Coba gunakan: MEMBER-GARUDA',
                confirmButtonColor: '#e11d48'
            });
        }
    }

    function updateSummary() {
        const desktopSlotJam = document.getElementById('desktopSlotJam');
        const desktopDurasi = document.getElementById('desktopDurasi');
        const desktopSubtotal = document.getElementById('desktopSubtotal');
        const desktopDiskonVal = document.getElementById('desktopDiskonVal');
        const rowDiskon = document.getElementById('rowDiskon');
        const desktopTotalHarga = document.getElementById('desktopTotalHarga');
        const desktopBtn = document.getElementById('desktopBtnSubmit');

        const mobileSlotJam = document.getElementById('mobileSlotJam');
        const mobileTotalHarga = document.getElementById('mobileTotalHarga');
        const mobileBtn = document.getElementById('mobileBtnSubmit');

        const inputJamMulai = document.getElementById('inputJamMulai');
        const inputJamSelesai = document.getElementById('inputJamSelesai');
        const inputDurasi = document.getElementById('inputDurasi');
        const inputTotalHarga = document.getElementById('inputTotalHarga');
        const inputDiskon = document.getElementById('inputDiskon');

        if (selectedSlots.length === 0) {
            desktopSlotJam.innerText = 'Pilih jam main';
            desktopDurasi.innerText = '0 Jam';
            desktopSubtotal.innerText = 'Rp 0';
            desktopTotalHarga.innerText = 'Rp 0';
            desktopBtn.disabled = true;
            desktopBtn.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
            desktopBtn.classList.remove('bg-futsal-600', 'hover:bg-futsal-700', 'text-white', 'cursor-pointer');

            mobileSlotJam.innerText = 'Pilih jam main';
            mobileTotalHarga.innerText = 'Rp 0';
            mobileBtn.disabled = true;
            mobileBtn.classList.add('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
            mobileBtn.classList.remove('bg-futsal-600', 'text-white', 'cursor-pointer');
            return;
        }

        const minJam = Math.min(...selectedSlots);
        const maxJam = Math.max(...selectedSlots) + 1;

        let pricePerDay = 0;
        selectedSlots.forEach(j => { pricePerDay += (slotPrices[j] || 0); });

        const subtotal = pricePerDay * totalHari;
        let nilaiDiskon = 0;
        if (isMemberDiscountApplied) {
            nilaiDiskon = Math.round(subtotal * discountRate);
            rowDiskon.classList.remove('hidden');
            desktopDiskonVal.innerText = '- Rp ' + nilaiDiskon.toLocaleString('id-ID');
        } else {
            rowDiskon.classList.add('hidden');
        }

        const finalTotal = subtotal - nilaiDiskon;

        const startStr = (minJam < 10 ? '0' : '') + minJam + ':00';
        const endStr = (maxJam < 10 ? '0' : '') + maxJam + ':00';
        const textJam = startStr + ' - ' + endStr;

        desktopSlotJam.innerText = textJam;
        desktopDurasi.innerText = selectedSlots.length + ' Jam/Hari';
        desktopSubtotal.innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
        desktopTotalHarga.innerText = 'Rp ' + finalTotal.toLocaleString('id-ID');

        desktopBtn.disabled = false;
        desktopBtn.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
        desktopBtn.classList.add('bg-futsal-600', 'hover:bg-futsal-700', 'text-white', 'cursor-pointer');

        mobileSlotJam.innerText = textJam + ' (' + selectedSlots.length + ' Jam)';
        mobileTotalHarga.innerText = 'Rp ' + finalTotal.toLocaleString('id-ID');
        mobileBtn.disabled = false;
        mobileBtn.classList.remove('bg-slate-300', 'text-slate-500', 'cursor-not-allowed');
        mobileBtn.classList.add('bg-futsal-600', 'text-white', 'cursor-pointer');

        inputJamMulai.value = startStr + ':00';
        inputJamSelesai.value = endStr + ':00';
        inputDurasi.value = selectedSlots.length;
        inputTotalHarga.value = finalTotal;
        inputDiskon.value = nilaiDiskon;
    }

    function bukaModalUlasan() {
        document.getElementById('modalUlasan').classList.remove('hidden');
    }
    function tutupModalUlasan() {
        document.getElementById('modalUlasan').classList.add('hidden');
    }

    const isUserLoggedIn = <?= (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) ? 'true' : 'false' ?>;

    document.getElementById('formBooking').addEventListener('submit', function(e) {
        if (selectedSlots.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Jam Main',
                text: 'Silakan klik salah satu slot jam yang tersedia terlebih dahulu.',
                confirmButtonColor: '#16a34a'
            });
            return false;
        }

        if (!isUserLoggedIn) {
            e.preventDefault();
            const redirectUrl = encodeURIComponent('sewa.php?id=<?= $lapangan['id'] ?>&tanggal=' + encodeURIComponent('<?= $selectedDate ?>'));
            Swal.fire({
                icon: 'info',
                title: '<span class="text-base sm:text-lg font-black text-slate-900">Perlu Masuk Akun Terlebih Dahulu</span>',
                html: `
                    <div class="text-left text-xs text-slate-600 space-y-3 mt-2">
                        <p class="leading-relaxed">
                            Halo pemain futsal! Untuk melanjutkan pemesanan lapangan <strong><?= htmlspecialchars(addslashes($lapangan['nama'])) ?></strong> dan menerbitkan invoice resmi, silakan <strong>Masuk</strong> ke akun Anda atau <strong>Daftar Akun Baru</strong>.
                        </p>
                        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 space-y-1.5 text-emerald-950">
                            <div class="font-bold flex items-center gap-1.5 text-xs text-emerald-900"><i class="fa-solid fa-circle-check text-emerald-600"></i> Keuntungan Memiliki Akun Fun Futsal:</div>
                            <ul class="list-disc pl-5 space-y-1 text-[11px] text-emerald-800">
                                <li>Jadwal tanding terkunci aman & bebas risiko bentrok</li>
                                <li>E-Invoice & barcode sewa instan untuk kasir lapangan</li>
                                <li>Potongan Diskon Member 15% setiap kali bermain</li>
                                <li>Layanan ganti jadwal & pengajuan refund online transparan</li>
                            </ul>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-right-to-bracket mr-1"></i> Masuk Sekarang',
                cancelButtonText: '<i class="fa-solid fa-user-plus mr-1"></i> Buat Akun Baru',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#0284c7',
                showCloseButton: true,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php?redirect=' + redirectUrl;
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = 'daftar.php?redirect=' + redirectUrl;
                }
            });
            return false;
        }
    });

    // Auto apply jika user adalah member aktif
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($userIsMember): ?>
            terapkanDiskonMember();
        <?php endif; ?>
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
