<?php
$pageTitle = 'Jadwal & Ketersediaan Slot Lapangan';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();

$selectedDate = isset($_GET['tanggal']) ? cleanInput($_GET['tanggal']) : date('Y-m-d');

// Ambil semua lapangan
$stmtLap = $db->query("SELECT * FROM lapangan ORDER BY id ASC");
$lapanganList = $stmtLap->fetchAll();

// Ambil semua booking untuk tanggal terpilih
$stmtBooking = $db->prepare("SELECT b.*, l.nama AS nama_lapangan 
                             FROM booking b 
                             JOIN lapangan l ON b.lapangan_id = l.id 
                             WHERE b.tanggal = :tanggal AND b.status IN ('pending', 'confirmed')");
$stmtBooking->execute([':tanggal' => $selectedDate]);
$bookingHariIni = $stmtBooking->fetchAll();

// Index booking by lapangan_id and hour
$scheduleMap = []; // [lapangan_id][hour] = booking
foreach ($bookingHariIni as $b) {
    $lapId = $b['lapangan_id'];
    $startH = (int)date('H', strtotime($b['jam_mulai']));
    $endH = (int)date('H', strtotime($b['jam_selesai']));
    for ($h = $startH; $h < $endH; $h++) {
        $scheduleMap[$lapId][$h] = $b;
    }
}
?>

<div class="bg-slate-100 py-10 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <span class="text-futsal-600 font-bold text-xs uppercase tracking-widest">Ketersediaan Real-Time</span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Jadwal Slot Lapangan Futsal</h1>
                <p class="text-slate-500 text-xs sm:text-sm mt-1">Lihat jam yang kosong dan langsung klik untuk sewa lapangan.</p>
            </div>

            <!-- Date Picker Form -->
            <form action="jadwal.php" method="GET" class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-sm">
                <i class="fa-regular fa-calendar text-futsal-600 ml-2"></i>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" onchange="this.form.submit()" class="text-xs sm:text-sm font-bold text-slate-800 bg-transparent focus:outline-none pr-3">
                <button type="submit" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition">
                    Lihat
                </button>
            </form>
        </div>

        <!-- Legend / Petunjuk -->
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 text-xs">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded bg-emerald-500 inline-block"></span>
                    <span class="font-semibold text-slate-700">Slot Kosong (Tersedia untuk Booking)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3.5 h-3.5 rounded bg-rose-500 inline-block"></span>
                    <span class="font-semibold text-slate-700">Sudah Dipesan (Terisi)</span>
                </div>
            </div>
            <div class="text-slate-500 font-medium">
                Jadwal: <strong class="text-slate-800"><?= formatTanggalIndo($selectedDate) ?></strong>
            </div>
        </div>

        <!-- Schedule Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="p-4 font-bold text-center w-28 border-r border-slate-800">Jam Slot</th>
                            <?php foreach ($lapanganList as $lap): ?>
                                <th class="p-4 font-bold text-center border-r border-slate-800 last:border-r-0">
                                    <div class="text-sm font-black"><?= htmlspecialchars($lap['nama']) ?></div>
                                    <div class="text-[11px] text-futsal-400 font-medium"><?= htmlspecialchars($lap['jenis']) ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php 
                        for ($jam = 8; $jam < 24; $jam++): 
                            $timeStr = sprintf('%02d:00', $jam);
                            $nextStr = sprintf('%02d:00', $jam + 1);
                        ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Jam Column -->
                                <td class="p-3.5 text-center font-bold font-mono text-slate-700 bg-slate-50 border-r border-slate-200">
                                    <?= $timeStr ?> - <?= $nextStr ?>
                                </td>

                                <!-- Lapangan Columns -->
                                <?php foreach ($lapanganList as $lap): 
                                    $lapId = $lap['id'];
                                    $isBooked = isset($scheduleMap[$lapId][$jam]);
                                    $book = $isBooked ? $scheduleMap[$lapId][$jam] : null;
                                ?>
                                    <td class="p-2.5 text-center border-r border-slate-100 last:border-r-0">
                                        <?php if ($isBooked): ?>
                                            <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-2.5 flex flex-col items-center justify-center shadow-xs">
                                                <span class="font-bold text-[11px] flex items-center gap-1">
                                                    <i class="fa-solid fa-lock text-[10px]"></i> Terisi
                                                </span>
                                                <span class="text-[10px] text-rose-500 truncate max-w-[140px] font-medium mt-0.5">
                                                    <?= htmlspecialchars($book['nama_tim'] ?: $book['nama_pemesan']) ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <a href="sewa.php?id=<?= $lap['id'] ?>&tanggal=<?= urlencode($selectedDate) ?>" 
                                               class="group bg-emerald-50 hover:bg-emerald-600 border border-emerald-200 hover:border-emerald-600 rounded-xl p-2.5 flex flex-col items-center justify-center transition shadow-xs cursor-pointer block">
                                                <span class="font-bold text-[11px] text-emerald-700 group-hover:text-white flex items-center gap-1">
                                                    <i class="fa-solid fa-check text-[10px]"></i> Tersedia
                                                </span>
                                                <span class="text-[10px] text-emerald-600 group-hover:text-emerald-100 font-semibold mt-0.5">
                                                    Pesan Slot
                                                </span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CTA Bottom -->
        <div class="mt-8 bg-gradient-to-r from-futsal-600 to-futsal-700 rounded-2xl p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
            <div>
                <h3 class="text-lg font-black">Mau Booking untuk Turnamen / Rutin Mingguan?</h3>
                <p class="text-xs text-futsal-100 mt-0.5">Dapatkan diskon khusus untuk booking rutin atau pemesanan lebih dari 4 jam.</p>
            </div>
            <a href="https://wa.me/<?= sanitizeWhatsApp($settings['no_wa']) ?>" target="_blank" class="bg-white text-futsal-700 font-bold px-5 py-2.5 rounded-xl text-xs hover:bg-futsal-50 transition shadow shrink-0 flex items-center gap-2">
                <i class="fa-brands fa-whatsapp text-sm"></i>
                <span>Hubungi Admin via WA</span>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
