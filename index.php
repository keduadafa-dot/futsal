<?php
$pageTitle = 'Beranda - Sewa & Booking Lapangan Futsal';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

// Ambil data semua lapangan
$stmtLapangan = $db->query("SELECT * FROM lapangan ORDER BY id ASC");
$daftarLapangan = $stmtLapangan->fetchAll();

// Ambil sparring terbaru
$stmtSparring = $db->query("SELECT s.*, l.nama AS nama_lapangan 
                           FROM sparring s 
                           LEFT JOIN lapangan l ON s.lapangan_id = l.id 
                           WHERE s.status = 'terbuka' AND s.tanggal >= CURDATE() 
                           ORDER BY s.tanggal ASC 
                           LIMIT 3");
$daftarSparring = $stmtSparring->fetchAll();

// Cek status login user
$isUserLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

// Ambil data riwayat booking user jika login
$userRiwayat = [];
if ($isUserLoggedIn) {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userHp = $_SESSION['user_phone'] ?? '';
    $stmtUserRiwayat = $db->prepare("SELECT b.*, l.nama AS nama_lapangan, l.jenis AS jenis_lapangan 
                                     FROM booking b 
                                     JOIN lapangan l ON b.lapangan_id = l.id 
                                     WHERE b.user_id = :uid OR (b.no_hp != '' AND b.no_hp = :hp) 
                                     ORDER BY b.id DESC LIMIT 4");
    $stmtUserRiwayat->execute([':uid' => $userId, ':hp' => $userHp]);
    $userRiwayat = $stmtUserRiwayat->fetchAll();
}

// Ambil riwayat pemesanan terkini di Fun Futsal (untuk live feed / bukti pemesanan)
$stmtRiwayatTerkini = $db->query("SELECT b.*, l.nama AS nama_lapangan, l.jenis AS jenis_lapangan 
                                  FROM booking b 
                                  JOIN lapangan l ON b.lapangan_id = l.id 
                                  ORDER BY b.id DESC 
                                  LIMIT 4");
$riwayatTerkini = $stmtRiwayatTerkini->fetchAll();

// Cek tiket terakhir dari sesi (jika user baru saja memesan di sesi ini)
$lastBooking = null;
if (!empty($_SESSION['last_booking_kode'])) {
    $stmtLast = $db->prepare("SELECT b.*, l.nama AS nama_lapangan, l.jenis AS jenis_lapangan 
                             FROM booking b 
                             JOIN lapangan l ON b.lapangan_id = l.id 
                             WHERE b.kode_booking = :kode LIMIT 1");
    $stmtLast->execute([':kode' => $_SESSION['last_booking_kode']]);
    $lastBooking = $stmtLast->fetch();
}

// Helper untuk menyamarkan nama pemesan pada live feed publik
if (!function_exists('sensorNamaPemesan')) {
    function sensorNamaPemesan($nama) {
        $nama = trim($nama);
        $parts = explode(' ', $nama);
        if (count($parts) > 1) {
            return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
        }
        return $nama;
    }
}
?>

<!-- Hero Section (Sesuai Desain Figma: Banner Gelap dengan Stadion & Aksen Hijau Futsal) -->
<section class="relative bg-slate-900 text-white overflow-hidden py-16 lg:py-24 border-b border-slate-800">
    <!-- Background Image with Overlay (Sepatu Futsal & Lapangan) -->
    <div class="absolute inset-0 z-0">
        <img src="assets/images/hero-sepatu-futsal.jpg" alt="Sepatu Futsal Fun Futsal Arena" class="w-full h-full object-cover opacity-35 object-center">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/80 to-slate-950/40"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-futsal-500/20 border border-futsal-500/30 text-futsal-400 text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-bolt"></i> Reservasi Cepat & Mudah
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight uppercase leading-tight">
                SEWA LAPANGAN FUTSAL <span class="text-futsal-400">PRAKTIS & REAL-TIME</span>
            </h1>

            <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
                Temukan jadwal slot kosong, pilih jenis lantai favorit (FIFA Vinyl, Rumput Sintetis, Interlock), dan dapatkan tiket booking resmi dalam hitungan detik.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="sewa.php" class="bg-futsal-600 hover:bg-futsal-700 text-white font-extrabold px-7 py-3.5 rounded-xl text-sm shadow-xl shadow-futsal-600/30 transition flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Sewa Lapangan Sekarang</span>
                </a>
                <a href="jadwal.php" class="bg-white/10 hover:bg-white/20 text-white font-bold px-6 py-3.5 rounded-xl text-sm backdrop-blur transition flex items-center gap-2 border border-white/20">
                    <i class="fa-solid fa-calendar-days text-futsal-400"></i>
                    <span>Cek Jadwal Slot Kosong</span>
                </a>
            </div>
        </div>

        <!-- Floating Quick Filter Bar (Sesuai UI Figma) -->
        <div class="mt-12 max-w-4xl mx-auto bg-white rounded-2xl p-4 sm:p-5 shadow-2xl border border-slate-100 text-slate-800">
            <form action="sewa.php" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Pilih Lapangan</label>
                    <select name="id" class="w-full bg-slate-50 border border-slate-200 text-xs font-bold rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                        <?php foreach ($daftarLapangan as $lap): ?>
                            <option value="<?= $lap['id'] ?>"><?= htmlspecialchars($lap['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Pilih Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" class="w-full bg-slate-50 border border-slate-200 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                </div>
                <div class="sm:pt-5">
                    <button type="submit" class="w-full bg-futsal-600 hover:bg-futsal-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md shadow-futsal-600/30 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Cari & Pilih Jam</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ======================================================================= -->
<!-- SECTION RIWAYAT BOOKING & CEK TIKET (Dibagian Bawah Form Quick Booking) -->
<!-- ======================================================================= -->
<section id="riwayat" class="py-12 sm:py-16 bg-white border-b border-slate-200 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Riwayat -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-futsal-50 text-futsal-700 border border-futsal-200 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pemesanan Lapangan
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Riwayat Booking & <span class="text-futsal-600">Status Tiket</span>
                </h2>
                <p class="text-slate-500 text-xs sm:text-sm mt-1 max-w-xl">
                    Pantau tiket aktif Anda, cek status pembayaran secara cepat, atau lihat riwayat pemesanan lapangan terbaru.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <?php if ($isUserLoggedIn): ?>
                    <a href="user/riwayat.php" class="bg-futsal-50 hover:bg-futsal-100 text-futsal-800 font-bold text-xs px-4 py-2.5 rounded-xl border border-futsal-200 transition flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-receipt text-futsal-600"></i>
                        <span>Lihat Semua Riwayat (<?= count($userRiwayat) ?>)</span>
                    </a>
                <?php else: ?>
                    <a href="cek_booking.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl border border-slate-200 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-magnifying-glass text-slate-500"></i>
                        <span>Halaman Cek Booking</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($lastBooking): ?>
            <!-- Highlight Tiket Baru Saja Dipesan di Sesi Ini -->
            <div class="mb-8 bg-gradient-to-r from-emerald-500/10 via-futsal-500/10 to-teal-500/10 border-2 border-futsal-500/30 rounded-3xl p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start sm:items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-futsal-600 text-white flex items-center justify-center text-xl shadow-md shadow-futsal-600/30 shrink-0">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="bg-futsal-600 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                    Tiket Terakhir Anda
                                </span>
                                <span class="font-mono text-xs font-black text-slate-900">
                                    #<?= htmlspecialchars($lastBooking['kode_booking']) ?>
                                </span>
                                <?php
                                $stBadge = '';
                                if ($lastBooking['status'] === 'confirmed') {
                                    $stBadge = '<span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200"><i class="fa-solid fa-circle-check mr-1"></i> Terkonfirmasi</span>';
                                } elseif ($lastBooking['status'] === 'pending') {
                                    $stBadge = '<span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200"><i class="fa-solid fa-clock mr-1"></i> Menunggu Pembayaran</span>';
                                } else {
                                    $stBadge = '<span class="bg-slate-100 text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded-full">' . htmlspecialchars(ucfirst($lastBooking['status'])) . '</span>';
                                }
                                echo $stBadge;
                                ?>
                            </div>
                            <h4 class="text-sm sm:text-base font-black text-slate-900">
                                <?= htmlspecialchars($lastBooking['nama_lapangan']) ?>
                            </h4>
                            <p class="text-xs text-slate-600 mt-0.5">
                                <i class="fa-regular fa-calendar text-futsal-600 mr-1"></i> <?= formatTanggalIndo($lastBooking['tanggal']) ?> | 
                                <i class="fa-regular fa-clock text-futsal-600 mr-1"></i> <?= substr($lastBooking['jam_mulai'], 0, 5) ?> - <?= substr($lastBooking['jam_selesai'], 0, 5) ?> WIB (<?= $lastBooking['durasi'] ?> Jam)
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-200">
                        <span class="text-base font-black text-futsal-700"><?= formatRupiah($lastBooking['total_harga']) ?></span>
                        <a href="invoice.php?kode=<?= urlencode($lastBooking['kode_booking']) ?>" class="bg-slate-900 hover:bg-futsal-600 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow">
                            <i class="fa-solid fa-receipt text-xs"></i>
                            <span>Buka Invoice</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isUserLoggedIn): ?>
            <!-- ================= USER LOGIN: DAFTAR RIWAYAT AKUN SAYA ================= -->
            <?php if (empty($userRiwayat)): ?>
                <div class="bg-slate-50 rounded-3xl p-8 text-center border border-slate-200 text-slate-500">
                    <div class="w-16 h-16 rounded-2xl bg-white text-slate-300 flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Belum Ada Riwayat Pemesanan di Akun Anda</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                        Anda belum memiliki jadwal reservasi lapangan. Yuk booking lapangan favorit Anda sekarang!
                    </p>
                    <a href="sewa.php" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-md shadow-futsal-600/30 inline-flex items-center gap-2">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Sewa Lapangan Sekarang</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <?php foreach ($userRiwayat as $b): ?>
                        <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-xs hover:shadow-md hover:border-futsal-300 transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="font-mono text-[11px] font-black text-futsal-700 bg-white border border-slate-200 px-2 py-0.5 rounded-lg shadow-2xs">
                                        #<?= htmlspecialchars($b['kode_booking']) ?>
                                    </span>
                                    <?php
                                    if ($b['status'] === 'confirmed') {
                                        echo '<span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-200 flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> Siap Main</span>';
                                    } elseif ($b['status'] === 'pending') {
                                        echo '<span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-amber-200 flex items-center gap-1"><i class="fa-solid fa-clock"></i> Pending</span>';
                                    } elseif ($b['status'] === 'completed') {
                                        echo '<span class="bg-blue-100 text-blue-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-blue-200 flex items-center gap-1"><i class="fa-solid fa-flag-checkered"></i> Selesai</span>';
                                    } else {
                                        echo '<span class="bg-rose-100 text-rose-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-rose-200 flex items-center gap-1"><i class="fa-solid fa-circle-xmark"></i> Batal</span>';
                                    }
                                    ?>
                                </div>
                                <h3 class="text-sm font-black text-slate-900 leading-snug line-clamp-1">
                                    <?= htmlspecialchars($b['nama_lapangan']) ?>
                                </h3>
                                <span class="text-[10px] text-futsal-600 font-bold block mt-0.5">
                                    <?= htmlspecialchars($b['jenis_lapangan'] ?: 'Lantai Standar') ?>
                                </span>

                                <div class="mt-3 pt-3 border-t border-slate-200/80 space-y-1 text-xs text-slate-600">
                                    <div><i class="fa-regular fa-calendar text-futsal-600 mr-1.5"></i> <?= date('d M Y', strtotime($b['tanggal'])) ?></div>
                                    <div><i class="fa-regular fa-clock text-futsal-600 mr-1.5"></i> <?= substr($b['jam_mulai'], 0, 5) ?> - <?= substr($b['jam_selesai'], 0, 5) ?> WIB (<?= $b['durasi'] ?> Jam)</div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-sm font-black text-slate-900"><?= formatRupiah($b['total_harga']) ?></span>
                                <a href="invoice.php?kode=<?= urlencode($b['kode_booking']) ?>" class="bg-slate-900 hover:bg-futsal-600 text-white font-bold text-[11px] px-3 py-1.5 rounded-xl transition flex items-center gap-1 shadow-xs">
                                    <i class="fa-solid fa-receipt text-[10px]"></i>
                                    <span>Tiket</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 text-center">
                    <a href="user/riwayat.php" class="inline-flex items-center gap-2 text-xs font-bold text-futsal-600 hover:text-futsal-700 bg-futsal-50 hover:bg-futsal-100 border border-futsal-200 px-5 py-2.5 rounded-xl transition">
                        <span>Lihat Seluruh Riwayat Reservasi Anda di Menu Akun</span>
                        <i class="fa-solid fa-arrow-right text-[11px]"></i>
                    </a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- ================= GUEST: FORM CARI RIWAYAT CEPAT & FEED TERKINI ================= -->
            
            <!-- Widget Pencarian Cepat Riwayat Booking -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-5 sm:p-7 shadow-xl mb-8 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-futsal-600/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="max-w-3xl relative z-10">
                    <div class="flex items-center gap-2 text-futsal-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Cek Riwayat Cepat
                    </div>
                    <h3 class="text-lg sm:text-xl font-black text-white">
                        Pernah Booking Lapangan? Cek Riwayat Tiket Anda
                    </h3>
                    <p class="text-slate-300 text-xs sm:text-sm mt-1">
                        Masukkan Nomor WhatsApp saat memesan atau Kode Booking (contoh: <code>BK-20261016-09BA</code>) untuk melihat status dan rincian tiket tanpa perlu login.
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row gap-2.5">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" id="inputRiwayat" placeholder="Masukkan No. WhatsApp (0812...) atau Kode Booking..." class="w-full bg-slate-800/90 border border-slate-700 text-white text-xs sm:text-sm font-semibold rounded-xl pl-11 pr-4 py-3 focus:ring-2 focus:ring-futsal-500 focus:outline-none placeholder-slate-400">
                        </div>
                        <button type="button" id="btnCariRiwayat" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-6 py-3 rounded-xl transition shadow-md shadow-futsal-600/30 flex items-center justify-center gap-2 shrink-0">
                            <i class="fa-solid fa-search"></i>
                            <span>Cari Riwayat</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Container Hasil Pencarian AJAX (Akan Muncul Saat User Mencari) -->
            <div id="containerHasilRiwayat" class="hidden mb-8"></div>

            <!-- Live Feed: Riwayat Pemesanan Terkini di Fun Futsal -->
            <div class="mt-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider">
                            Riwayat Booking Lapangan Terkini
                        </h3>
                    </div>
                    <span class="text-[11px] font-bold text-slate-400">Real-Time Activity</span>
                </div>

                <?php if (empty($riwayatTerkini)): ?>
                    <div class="bg-slate-50 rounded-2xl p-6 text-center border border-slate-200 text-slate-400 text-xs">
                        Belum ada aktivitas pemesanan baru. Ayo jadi yang pertama memesan lapangan hari ini!
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($riwayatTerkini as $r): ?>
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/90 hover:border-futsal-300 hover:shadow-md transition flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-mono text-[10px] font-black text-futsal-700 bg-white border border-slate-200 px-2 py-0.5 rounded-md shadow-2xs">
                                            #<?= htmlspecialchars($r['kode_booking']) ?>
                                        </span>
                                        <?php
                                        if ($r['status'] === 'confirmed') {
                                            echo '<span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200"><i class="fa-solid fa-check text-[9px] mr-0.5"></i> Terkonfirmasi</span>';
                                        } elseif ($r['status'] === 'pending') {
                                            echo '<span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200"><i class="fa-solid fa-clock text-[9px] mr-0.5"></i> Menunggu Bayar</span>';
                                        } else {
                                            echo '<span class="bg-slate-200 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full">' . htmlspecialchars(ucfirst($r['status'])) . '</span>';
                                        }
                                        ?>
                                    </div>

                                    <h4 class="text-xs font-black text-slate-900 leading-snug line-clamp-1">
                                        <?= htmlspecialchars($r['nama_lapangan']) ?>
                                    </h4>

                                    <div class="mt-2 space-y-1 text-[11px] text-slate-500">
                                        <div>
                                            <i class="fa-solid fa-user-circle text-slate-400 mr-1"></i> 
                                            <strong><?= htmlspecialchars(sensorNamaPemesan($r['nama_pemesan'])) ?></strong>
                                            <?php if (!empty($r['nama_tim'])): ?>
                                                <span class="text-slate-400">• <?= htmlspecialchars($r['nama_tim']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <i class="fa-regular fa-calendar text-futsal-600 mr-1"></i> <?= date('d M Y', strtotime($r['tanggal'])) ?>
                                        </div>
                                        <div>
                                            <i class="fa-regular fa-clock text-futsal-600 mr-1"></i> <?= substr($r['jam_mulai'], 0, 5) ?> - <?= substr($r['jam_selesai'], 0, 5) ?> WIB
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-slate-200 flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-800"><?= formatRupiah($r['total_harga']) ?></span>
                                    <a href="invoice.php?kode=<?= urlencode($r['kode_booking']) ?>" class="text-[11px] font-bold text-futsal-600 hover:text-futsal-700 hover:underline flex items-center gap-1">
                                        <span>Detail</span> <i class="fa-solid fa-angle-right text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ajakan Akun Member -->
            <div class="mt-8 bg-slate-50 border border-slate-200 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-center sm:text-left">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-black text-slate-900">Ingin Riwayat Pemesanan Tersimpan Otomatis?</h4>
                        <p class="text-[11px] sm:text-xs text-slate-500">Masuk ke akun Anda atau daftar member gratis untuk nikmati diskon spesial dan kemudahan kelola jadwal.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="login.php" class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow-xs">
                        Masuk
                    </a>
                    <a href="daftar.php" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow-xs">
                        Daftar Akun
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- Section Ayo Booking Sekarang! (Sesuai Layar Figma "Ayo Booking Sekarang!") -->
<section class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Pilihan Fasilitas</span>
            <h2 class="text-3xl font-black text-slate-900 mt-1">Ayo Booking Sekarang!</h2>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">Pilih tipe lapangan terbaik untuk performa maksimal tim futsal Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($daftarLapangan as $lap): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden group">
                    <div>
                        <!-- Foto Lapangan -->
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="<?= htmlspecialchars($lap['foto']) ?>?v=<?= file_exists($lap['foto']) ? filemtime($lap['foto']) : '2' ?>" alt="<?= htmlspecialchars($lap['nama']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span class="absolute top-3 left-3 bg-futsal-600 text-white text-[11px] font-bold px-3 py-1 rounded-full shadow">
                                <?= htmlspecialchars($lap['jenis']) ?>
                            </span>
                        </div>

                        <!-- Info Lapangan -->
                        <div class="p-5 space-y-3">
                            <h3 class="text-lg font-black text-slate-900 group-hover:text-futsal-600 transition">
                                <?= htmlspecialchars($lap['nama']) ?>
                            </h3>
                            <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                <?= htmlspecialchars($lap['deskripsi']) ?>
                            </p>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <span><i class="fa-solid fa-ruler-combined text-futsal-600 mr-1"></i> <?= htmlspecialchars($lap['panjang']) ?> x <?= htmlspecialchars($lap['lebar']) ?></span>
                                <span class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check"></i> Siap Pakai</span>
                            </div>

                            <!-- Tarif Box -->
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-1 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Siang (08:00 - 17:00):</span>
                                    <span class="font-bold text-slate-800"><?= formatRupiah($lap['harga_siang']) ?>/jam</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-500">Malam (17:00 - 24:00):</span>
                                    <span class="font-bold text-futsal-600"><?= formatRupiah($lap['harga_malam']) ?>/jam</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Action -->
                    <div class="p-5 pt-0">
                        <a href="sewa.php?id=<?= $lap['id'] ?>" class="w-full bg-futsal-600 hover:bg-futsal-700 text-white font-bold py-3 px-4 rounded-xl text-center text-xs transition shadow-md shadow-futsal-600/30 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Sewa Lapangan</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Section Alur Pemesanan -->
<section class="py-16 bg-white border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Mudah & Cepat</span>
            <h2 class="text-3xl font-black text-slate-900 mt-1">Alur Pemesanan Lapangan</h2>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">Hanya butuh 4 langkah mudah untuk mengamankan jam main tim Anda.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-2xl bg-futsal-100 text-futsal-700 font-black text-lg flex items-center justify-center mx-auto mb-3">1</div>
                <h3 class="font-bold text-slate-900 text-sm mb-1">Pilih Lapangan</h3>
                <p class="text-xs text-slate-500">Pilih lapangan vinyl, sintetis, atau interlock yang sesuai preferensi.</p>
            </div>
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-2xl bg-futsal-100 text-futsal-700 font-black text-lg flex items-center justify-center mx-auto mb-3">2</div>
                <h3 class="font-bold text-slate-900 text-sm mb-1">Pilih Jam & Slot</h3>
                <p class="text-xs text-slate-500">Klik slot jam yang kosong pada jadwal interaktif real-time.</p>
            </div>
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-2xl bg-futsal-100 text-futsal-700 font-black text-lg flex items-center justify-center mx-auto mb-3">3</div>
                <h3 class="font-bold text-slate-900 text-sm mb-1">Bayar QRIS / Transfer</h3>
                <p class="text-xs text-slate-500">Scan QRIS dari aplikasi apa saja atau transfer ke rekening arena.</p>
            </div>
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center">
                <div class="w-12 h-12 rounded-2xl bg-futsal-600 text-white font-black text-lg flex items-center justify-center mx-auto mb-3 shadow-md shadow-futsal-600/30">4</div>
                <h3 class="font-bold text-slate-900 text-sm mb-1">Dapat Tiket & Main</h3>
                <p class="text-xs text-slate-500">Tunjukkan invoice ke petugas di arena dan mulai kick-off!</p>
            </div>
        </div>
    </div>
</section>

<!-- Section Sparring Teaser -->
<section class="py-16 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Komunitas Futsal</span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Cari Lawan Main (Sparring)</h2>
            </div>
            <a href="sparring.php" class="text-xs font-bold text-futsal-600 hover:text-futsal-700 flex items-center gap-1.5 self-start sm:self-auto">
                <span>Lihat Semua Tantangan</span> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($daftarSparring as $sp): ?>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="bg-futsal-50 text-futsal-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-futsal-200"><?= htmlspecialchars($sp['level_tim']) ?></span>
                            <span class="text-[11px] text-slate-400"><?= date('d M Y', strtotime($sp['tanggal'])) ?></span>
                        </div>
                        <h3 class="text-base font-black text-slate-900"><?= htmlspecialchars($sp['nama_tim']) ?></h3>
                        <div class="text-xs text-slate-500 mt-2 space-y-1">
                            <div><i class="fa-regular fa-clock text-futsal-600 mr-1.5"></i> <?= htmlspecialchars($sp['jam']) ?></div>
                            <div><i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i> <?= htmlspecialchars($sp['nama_lapangan'] ?: 'Fun Futsal') ?></div>
                        </div>
                        <p class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 italic mt-3 line-clamp-2">"<?= htmlspecialchars($sp['catatan']) ?>"</p>
                    </div>

                    <a href="https://wa.me/<?= sanitizeWhatsApp($sp['no_wa']) ?>" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-3 rounded-xl text-center text-xs transition flex items-center justify-center gap-1.5 shadow">
                        <i class="fa-brands fa-whatsapp text-sm"></i>
                        <span>Hubungi Lawan</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION GALERI PENGGUNA & KOMUNITAS FUN FUTSAL -->
<section class="py-16 bg-white border-t border-slate-200" id="galeri">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Galeri -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10">
            <div>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-futsal-100 text-futsal-800 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-camera-retro"></i> Momen Komunitas
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Galeri Pengguna Fun Futsal</h2>
                <p class="text-slate-500 text-xs sm:text-sm mt-1 max-w-xl">
                    Intip keseruan pertandingan, selebrasi gol, dan aksi tim-tim yang sudah bermain di arena <strong>Fun Futsal</strong>.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="sewa.php" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-futsal-600/30 transition flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Booking Jam Mainmu</span>
                </a>
            </div>
        </div>

        <!-- Highlight Stats Banner -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10 text-center">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-2xl sm:text-3xl font-black text-futsal-700 block">5.400+</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jam Main Terlaksana</span>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 block">180+</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klub & Tim Aktif</span>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-2xl sm:text-3xl font-black text-amber-500 block"><i class="fa-solid fa-star text-base"></i> 4.9/5</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Rating Pemain</span>
            </div>
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                <span class="text-2xl sm:text-3xl font-black text-emerald-600 block">100%</span>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Standar FIFA Terjamin</span>
            </div>
        </div>

        <!-- Grid Galeri Foto Pengguna -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Foto 1: Garuda FC vs Barata Squad -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/galeri-garuda-vs-barata.jpg?v=<?= file_exists('assets/images/galeri-garuda-vs-barata.jpg') ? filemtime('assets/images/galeri-garuda-vs-barata.jpg') : '2' ?>" alt="Garuda FC vs Barata Squad" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-futsal-600/90 backdrop-blur text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
                            Lapangan 1 - Vinyl FIFA
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 248 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-futsal-300 font-semibold block uppercase">Sparring Match • 14 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Garuda FC vs Barata Squad</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Lantai vinyl sangat nyaman, tidak licin sama sekali walau tanding 2 jam penuh tempo cepat!"</p>
                    </div>
                </div>
            </div>

            <!-- Foto 2: Selebrasi Menit Akhir -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/galeri-selebrasi.jpg?v=<?= file_exists('assets/images/galeri-selebrasi.jpg') ? filemtime('assets/images/galeri-selebrasi.jpg') : '2' ?>" alt="Selebrasi Gol Bintang Muda" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-emerald-600/90 backdrop-blur text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
                            Lapangan 2 - Rumput Sintetis
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 312 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Fun League • 12 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Bintang Muda FC Selebrasi Menit Akhir</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Gol penentu kemenangan di menit ke-39! Rumput sintetisnya empuk dan lampu LED-nya sangat terang."</p>
                    </div>
                </div>
            </div>

            <!-- Foto 3: Kickers United & Alumni FC -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/galeri-tim.jpg?v=<?= file_exists('assets/images/galeri-tim.jpg') ? filemtime('assets/images/galeri-tim.jpg') : '2' ?>" alt="Kickers United & Alumni FC" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-blue-600/90 backdrop-blur text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
                            Lapangan 3 - Interlock Poly
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 189 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-blue-300 font-semibold block uppercase">Friendly Night • 10 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Kickers United & Alumni FC</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Papan skor digital & sound system-nya bikin suasana main futsal serasa tanding di liga pro!"</p>
                    </div>
                </div>
            </div>

            <!-- Foto 4: Sepatu Futsal & Bola Match Resmi -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/lapangan-4.jpg?v=<?= file_exists('assets/images/lapangan-4.jpg') ? filemtime('assets/images/lapangan-4.jpg') : '2' ?>" alt="Sepatu dan Bola Futsal Resmi" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-futsal-600/90 backdrop-blur text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
                            Komunitas Futsal
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 415 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-futsal-300 font-semibold block uppercase">Weekend Cup • 08 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Sepatu Futsal & Bola Match Resmi</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Sol karet datar dan bola futsal low-bounce bikin kontrol bola sangat presisi dan nyaman."</p>
                    </div>
                </div>
            </div>

            <!-- Foto 5: Bola Futsal di Lapangan Rumput Sintetis -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/galeri-kiper.jpg?v=<?= file_exists('assets/images/galeri-kiper.jpg') ? filemtime('assets/images/galeri-kiper.jpg') : '2' ?>" alt="Bola Futsal di Lapangan Rumput Sintetis" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-emerald-600/90 backdrop-blur text-white text-[10px] font-extrabold px-3 py-1 rounded-full shadow">
                            Bola Resmi Futsal
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 274 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-emerald-300 font-semibold block uppercase">Low-Bounce Ball • 05 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Bola Futsal Resmi Lapangan Rumput</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Bola futsal low-bounce standar resmi di atas rumput sintetis Fun Futsal, nyaman untuk kontrol presisi."</p>
                    </div>
                </div>
            </div>

            <!-- Foto 6: Rajawali Futsal Club Angkat Trofi Juara 1 -->
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="assets/images/galeri-trofi.jpg?v=<?= file_exists('assets/images/galeri-trofi.jpg') ? filemtime('assets/images/galeri-trofi.jpg') : '2' ?>" alt="Penyerahan Piala Juara Turnamen Futsal" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/30 to-transparent flex flex-col justify-between p-5 text-white">
                    <div class="flex justify-between items-center">
                        <span class="bg-yellow-500 text-slate-900 text-[10px] font-black px-3 py-1 rounded-full shadow">
                            <i class="fa-solid fa-trophy mr-1"></i> Juara 1 Turnamen
                        </span>
                        <span class="bg-black/50 backdrop-blur text-white text-[10px] font-bold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-heart text-rose-500 mr-1"></i> 520 Suka
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-yellow-300 font-semibold block uppercase">Fun Futsal Championship • 01 Okt 2026</span>
                        <h3 class="text-base font-black text-white leading-snug">Rajawali Futsal Club Angkat Trofi</h3>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2">"Juara 1 Fun Futsal Community Cup! Terima kasih panitia atas penyelenggaraan yang rapi & berkelas."</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Social Proof Callout Banner -->
        <div class="mt-12 bg-gradient-to-r from-futsal-700 via-futsal-600 to-teal-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur px-3 py-1 rounded-full text-xs font-bold">
                    <i class="fa-brands fa-instagram text-rose-300"></i> @funfutsal.id
                </div>
                <h3 class="text-2xl font-black">Mau Momen Tim Kamu Dipajang di Sini?</h3>
                <p class="text-xs sm:text-sm text-futsal-100 max-w-xl">
                    Sewa lapangan di <strong>Fun Futsal</strong>, abadikan foto atau video tanding timmu, dan tag akun Instagram kami untuk kesempatan memenangkan merchandise eksklusif!
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 flex-shrink-0">
                <a href="sewa.php" class="bg-white text-futsal-800 hover:bg-slate-100 font-black text-xs px-6 py-3.5 rounded-2xl shadow-lg transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bolt text-amber-500"></i>
                    <span>Sewa Lapangan Sekarang</span>
                </a>
            </div>
        </div>

    </div>
</section>

<!-- Script Interaktif Pencarian Cepat Riwayat Booking -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputRiwayat = document.getElementById('inputRiwayat');
    const btnCariRiwayat = document.getElementById('btnCariRiwayat');
    const containerHasil = document.getElementById('containerHasilRiwayat');

    if (btnCariRiwayat && inputRiwayat && containerHasil) {
        function cariRiwayatBooking() {
            const query = inputRiwayat.value.trim();
            if (!query) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kolom Pencarian Kosong',
                        text: 'Silakan ketikkan Nomor WhatsApp atau Kode Booking Anda terlebih dahulu.',
                        confirmButtonColor: '#16a34a'
                    });
                } else {
                    alert('Silakan masukkan Nomor WhatsApp atau Kode Booking.');
                }
                inputRiwayat.focus();
                return;
            }

            const originalBtnHtml = btnCariRiwayat.innerHTML;
            btnCariRiwayat.disabled = true;
            btnCariRiwayat.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Mencari...</span>';

            containerHasil.classList.remove('hidden');
            containerHasil.innerHTML = `
                <div class="bg-white rounded-2xl p-6 text-center border border-slate-200 shadow-sm animate-pulse">
                    <i class="fa-solid fa-circle-notch fa-spin text-2xl text-futsal-600 mb-2"></i>
                    <p class="text-xs font-bold text-slate-700">Mencari riwayat reservasi Anda...</p>
                </div>
            `;

            fetch('ajax_riwayat.php?q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(res => {
                    btnCariRiwayat.disabled = false;
                    btnCariRiwayat.innerHTML = originalBtnHtml;

                    if (!res.success || !res.data || res.data.length === 0) {
                        containerHasil.innerHTML = `
                            <div class="bg-white rounded-2xl p-6 text-center border border-slate-200 shadow-sm">
                                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-2 text-xl">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </div>
                                <h4 class="text-sm font-bold text-slate-800">Riwayat Booking Tidak Ditemukan</h4>
                                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                                    Tidak ditemukan tiket dengan kata kunci <strong>"${escapeHtml(query)}"</strong>. Pastikan Nomor WhatsApp atau Kode Booking yang Anda masukkan sudah benar.
                                </p>
                                <button type="button" onclick="document.getElementById('containerHasilRiwayat').classList.add('hidden')" class="mt-3 text-xs text-slate-400 hover:text-slate-600 font-bold underline">
                                    Tutup Hasil Pencarian
                                </button>
                            </div>
                        `;
                        return;
                    }

                    let html = `
                        <div class="bg-white rounded-2xl p-5 border border-futsal-200 shadow-lg">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <h4 class="text-sm font-black text-slate-900">Ditemukan ${res.data.length} Riwayat Reservasi</h4>
                                </div>
                                <button type="button" onclick="document.getElementById('containerHasilRiwayat').classList.add('hidden')" class="text-xs text-slate-400 hover:text-slate-600 font-bold px-2 py-1 rounded-lg hover:bg-slate-100 transition">
                                    <i class="fa-solid fa-xmark mr-1"></i> Tutup
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    `;

                    res.data.forEach(item => {
                        html += `
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col justify-between hover:border-futsal-400 transition shadow-xs">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="font-mono text-xs font-black text-futsal-700 bg-white border border-slate-200 px-2 py-0.5 rounded-lg shadow-2xs">#${escapeHtml(item.kode_booking)}</span>
                                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border ${item.status_class}">${escapeHtml(item.status_label)}</span>
                                    </div>
                                    <h5 class="text-sm font-black text-slate-900 leading-snug">${escapeHtml(item.nama_lapangan)}</h5>
                                    <span class="text-[10px] text-futsal-600 font-bold block mt-0.5">${escapeHtml(item.jenis_lapangan)}</span>

                                    <div class="mt-3 pt-2.5 border-t border-slate-200/80 space-y-1 text-xs text-slate-600">
                                        <div><i class="fa-regular fa-calendar text-futsal-600 mr-1.5 w-3.5 text-center"></i> ${escapeHtml(item.tanggal_indo)}</div>
                                        <div><i class="fa-regular fa-clock text-futsal-600 mr-1.5 w-3.5 text-center"></i> ${escapeHtml(item.jam_mulai)} - ${escapeHtml(item.jam_selesai)} WIB (${item.durasi} Jam)</div>
                                        <div><i class="fa-solid fa-user text-slate-400 mr-1.5 w-3.5 text-center"></i> <strong>${escapeHtml(item.nama_pemesan)}</strong> ${item.nama_tim ? '• ' + escapeHtml(item.nama_tim) : ''}</div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                                    <span class="text-sm font-black text-slate-900">${escapeHtml(item.total_harga_formatted)}</span>
                                    <a href="${item.invoice_url}" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg transition shadow-xs flex items-center gap-1.5">
                                        <i class="fa-solid fa-receipt text-[11px]"></i>
                                        <span>Buka Tiket / Invoice</span>
                                    </a>
                                </div>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;

                    containerHasil.innerHTML = html;
                    containerHasil.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                })
                .catch(err => {
                    btnCariRiwayat.disabled = false;
                    btnCariRiwayat.innerHTML = originalBtnHtml;
                    containerHasil.innerHTML = `
                        <div class="bg-rose-50 text-rose-700 rounded-2xl p-4 text-xs font-semibold border border-rose-200 text-center">
                            Gagal menghubungkan ke server. Silakan coba kembali beberapa saat lagi.
                        </div>
                    `;
                });
        }

        btnCariRiwayat.addEventListener('click', cariRiwayatBooking);
        inputRiwayat.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cariRiwayatBooking();
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
