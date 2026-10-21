<?php
$pageTitle = 'Cek Status Booking Lapangan';
require_once __DIR__ . '/includes/header.php';

$db = getDBConnection();
$searchKeyword = isset($_GET['q']) ? cleanInput($_GET['q']) : '';

$results = [];
if (!empty($searchKeyword)) {
    $stmt = $db->prepare("SELECT b.*, l.nama AS nama_lapangan 
                         FROM booking b 
                         JOIN lapangan l ON b.lapangan_id = l.id 
                         WHERE b.kode_booking LIKE :q1 OR b.no_hp LIKE :q2 OR b.nama_pemesan LIKE :q3 
                         ORDER BY b.id DESC LIMIT 10");
    $searchTerm = "%" . $searchKeyword . "%";
    $stmt->execute([':q1' => $searchTerm, ':q2' => $searchTerm, ':q3' => $searchTerm]);
    $results = $stmt->fetchAll();
}
?>

<div class="bg-slate-100 py-12 min-h-[70vh]">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-2xl bg-futsal-100 text-futsal-600 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Cek Status Booking & Invoice</h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">Masukkan Kode Booking (Contoh: <code>BK-20261016-01</code>) atau Nomor WhatsApp Anda.</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white p-4 sm:p-6 rounded-3xl border border-slate-200 shadow-xl mb-8">
            <form action="cek_booking.php" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($searchKeyword) ?>" required placeholder="Masukkan Kode Booking atau No WhatsApp..." class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm font-semibold rounded-2xl pl-11 pr-4 py-3.5 focus:ring-2 focus:ring-futsal-500 focus:outline-none">
                </div>
                <button type="submit" class="bg-futsal-600 hover:bg-futsal-700 text-white font-bold px-6 py-3.5 rounded-2xl text-sm transition shadow-md shadow-futsal-600/30 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-search"></i>
                    <span>Cari Tiket</span>
                </button>
            </form>
        </div>

        <!-- Search Results -->
        <?php if (!empty($searchKeyword)): ?>
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-slate-700 flex items-center justify-between px-2">
                    <span>Hasil Pencarian: <?= count($results) ?> Ditemukan</span>
                    <a href="cek_booking.php" class="text-xs text-futsal-600 font-semibold hover:underline">Reset Pencarian</a>
                </h2>

                <?php if (empty($results)): ?>
                    <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 text-slate-500">
                        <i class="fa-solid fa-circle-question text-4xl text-slate-300 mb-3"></i>
                        <p class="text-sm font-semibold text-slate-700">Kode Booking Tidak Ditemukan</p>
                        <p class="text-xs text-slate-400 mt-1">Pastikan kode yang dimasukkan sudah benar atau coba cari menggunakan nomor WhatsApp.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $b): ?>
                        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-sm text-futsal-700"><?= htmlspecialchars($b['kode_booking']) ?></span>
                                    <?= getStatusBadge($b['status']) ?>
                                </div>
                                <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($b['nama_lapangan']) ?></h3>
                                <p class="text-xs text-slate-500">
                                    <i class="fa-regular fa-calendar mr-1"></i> <?= formatTanggalIndo($b['tanggal']) ?> | 
                                    <i class="fa-regular fa-clock mr-1"></i> <?= substr($b['jam_mulai'], 0, 5) ?> - <?= substr($b['jam_selesai'], 0, 5) ?> WIB
                                </p>
                                <p class="text-xs text-slate-600">
                                    Atas Nama: <strong class="text-slate-800"><?= htmlspecialchars($b['nama_pemesan']) ?></strong> <?= $b['nama_tim'] ? '(' . htmlspecialchars($b['nama_tim']) . ')' : '' ?>
                                </p>
                            </div>

                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                <div class="text-base font-black text-slate-900">
                                    <?= formatRupiah($b['total_harga']) ?>
                                </div>
                                <a href="invoice.php?kode=<?= urlencode($b['kode_booking']) ?>" class="bg-slate-900 hover:bg-futsal-600 text-white font-bold text-xs px-4 py-2 rounded-xl transition flex items-center gap-1.5 shadow">
                                    <i class="fa-solid fa-receipt text-xs"></i>
                                    <span>Lihat Invoice</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Quick Help Box -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 text-xs text-slate-500 space-y-3 shadow-xs">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-futsal-600"></i> Informasi Pencarian
                </h3>
                <p>1. Kode booking otomatis diberikan sesaat setelah Anda menyelesaikan formulir pemesanan.</p>
                <p>2. Format kode booking biasanya berawalan <code>BK-YYYYMMDD-XXXX</code>.</p>
                <p>3. Jika lupa kode booking, masukkan nomor WhatsApp yang Anda gunakan saat memesan lapangan.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
