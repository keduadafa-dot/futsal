<?php
$pageTitle = 'Invoice Bukti Pemesanan';
require_once __DIR__ . '/includes/functions.php';

$db = getDBConnection();
$settings = getPengaturan();

$kodeBooking = isset($_GET['kode']) ? cleanInput($_GET['kode']) : '';

$stmt = $db->prepare("SELECT b.*, l.nama AS nama_lapangan, l.jenis AS jenis_lapangan, l.foto AS foto_lapangan 
                      FROM booking b 
                      JOIN lapangan l ON b.lapangan_id = l.id 
                      WHERE b.kode_booking = :kode");
$stmt->execute([':kode' => $kodeBooking]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: cek_booking.php?error=notfound");
    exit;
}

$pesanWa = "Halo Admin " . $settings['nama_arena'] . ", saya ingin konfirmasi booking lapangan:%0A" .
           "- Kode Booking: *" . $booking['kode_booking'] . "*%0A" .
           "- Lapangan: *" . $booking['nama_lapangan'] . "*%0A" .
           "- Nama: " . $booking['nama_pemesan'] . " (" . ($booking['nama_tim'] ?: 'Personal') . ")%0A" .
           "- Tanggal: " . formatTanggalIndo($booking['tanggal']) . "%0A" .
           "- Jam: " . substr($booking['jam_mulai'], 0, 5) . " - " . substr($booking['jam_selesai'], 0, 5) . " WIB (" . $booking['durasi'] . " Jam)%0A" .
           "- Total: *" . formatRupiah($booking['total_harga']) . "*%0A" .
           "- Metode: " . strtoupper($booking['metode_pembayaran']) . "%0A%0AMohon diverifikasi. Terima kasih!";

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100 py-6 sm:py-10 pb-32 md:pb-12 min-h-screen">
    <div class="max-w-2xl mx-auto px-3 sm:px-6">
        
        <!-- Header Actions: Kembali & Cetak -->
        <div class="flex items-center justify-between mb-4 no-print">
            <a href="index.php" class="text-xs font-bold text-slate-600 hover:text-futsal-600 flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                <i class="fa-solid fa-arrow-left"></i> Beranda
            </a>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-print text-futsal-600"></i> Cetak
                </button>
            </div>
        </div>

        <!-- Main Invoice Ticket Card (Matching Figma Green Card Style) -->
        <div class="bg-white rounded-3xl overflow-hidden shadow-xl border border-slate-200 print:shadow-none print:border-none">
            
            <!-- Green Header Section (Sesuai Desain Figma Card Hijau) -->
            <div class="bg-gradient-to-br from-futsal-600 via-futsal-700 to-futsal-800 p-6 sm:p-8 text-white relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                
                <div class="relative z-10 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider">
                            <i class="fa-solid fa-receipt"></i> INVOICE BOOKING
                        </div>
                        <div class="text-[11px] text-futsal-100 font-semibold">
                            <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?> WIB
                        </div>
                    </div>

                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white"><?= htmlspecialchars($settings['nama_arena']) ?></h1>
                        <p class="text-xs text-futsal-100 mt-1 line-clamp-1"><?= htmlspecialchars($settings['alamat']) ?></p>
                    </div>

                    <div class="bg-black/20 backdrop-blur-md p-3.5 rounded-2xl border border-white/20 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-futsal-200 block uppercase font-bold">Kode Reservasi:</span>
                            <span class="font-mono text-lg sm:text-xl font-black tracking-wider text-white"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                        </div>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($booking['kode_booking']) ?>'); Swal.fire({title:'Tersalin!', text:'Kode booking telah disalin', icon:'success', timer:1200, showConfirmButton:false});" class="text-xs bg-white/20 hover:bg-white/30 text-white font-bold px-3 py-1.5 rounded-xl transition">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Pembayaran Bar -->
            <div class="bg-slate-50 px-6 py-3.5 border-b border-slate-200 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-bold">Status:</span>
                <div><?= getStatusBadge($booking['status']) ?></div>
            </div>

            <!-- Detail Pesanan -->
            <div class="p-6 sm:p-8 space-y-5">
                
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Pemesan:</span>
                        <span class="font-bold text-slate-800 text-right"><?= htmlspecialchars($booking['nama_pemesan']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Tim:</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($booking['nama_tim'] ?: '-') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">WhatsApp:</span>
                        <span class="font-semibold text-slate-800 text-right"><?= htmlspecialchars($booking['no_hp']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Metode Bayar:</span>
                        <span class="font-bold text-futsal-700 uppercase text-right"><?= htmlspecialchars($booking['metode_pembayaran']) ?></span>
                    </div>
                </div>

                <!-- Lapangan & Jadwal -->
                <div class="border border-slate-200 rounded-2xl p-4 bg-white space-y-2 text-xs">
                    <div class="font-black text-slate-900 text-sm"><?= htmlspecialchars($booking['nama_lapangan']) ?></div>
                    <div class="text-[11px] text-futsal-600 font-semibold"><?= htmlspecialchars($booking['jenis_lapangan']) ?></div>
                    <div class="pt-2 border-t border-slate-100 flex justify-between items-center text-slate-600">
                        <span>
                            <i class="fa-regular fa-calendar text-futsal-600 mr-1"></i> 
                            <?= formatTanggalIndo($booking['tanggal']) ?>
                            <?php if (!empty($booking['tanggal_selesai']) && $booking['tanggal_selesai'] !== $booking['tanggal']): ?>
                                s/d <?= formatTanggalIndo($booking['tanggal_selesai']) ?> (<?= $booking['total_hari'] ?> Hari)
                            <?php endif; ?>
                        </span>
                        <span class="font-mono font-bold text-slate-900"><?= substr($booking['jam_mulai'], 0, 5) ?> - <?= substr($booking['jam_selesai'], 0, 5) ?> (<?= $booking['durasi'] ?> Jam/hari)</span>
                    </div>

                    <?php if (!empty($booking['tambah_jam']) && $booking['tambah_jam'] > 0): ?>
                        <div class="pt-2 border-t border-slate-100 flex justify-between items-center text-amber-700 bg-amber-50 p-2 rounded-xl">
                            <span class="font-bold"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Perpanjangan Jam Main di Kasir:</span>
                            <span class="font-black">+<?= (int)$booking['tambah_jam'] ?> Jam (+<?= formatRupiah($booking['biaya_tambahan']) ?>)</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($booking['diskon']) && $booking['diskon'] > 0): ?>
                        <div class="pt-2 border-t border-slate-100 flex justify-between items-center text-emerald-700 bg-emerald-50 p-2 rounded-xl">
                            <span class="font-bold"><i class="fa-solid fa-crown text-amber-500 mr-1"></i> Diskon Member (<?= htmlspecialchars($booking['kode_member']) ?>):</span>
                            <span class="font-black">- <?= formatRupiah($booking['diskon']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($booking['status_refund'])): ?>
                        <div class="pt-2 border-t border-slate-100 p-2.5 rounded-xl <?= $booking['status_refund'] === 'refunded' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?>">
                            <div class="font-bold flex items-center justify-between text-xs">
                                <span><i class="fa-solid fa-rotate-left mr-1"></i> Status Pengembalian Dana (Refund):</span>
                                <span class="uppercase font-black px-2 py-0.5 rounded-full text-[10px] <?= $booking['status_refund'] === 'refunded' ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' ?>">
                                    <?= $booking['status_refund'] === 'refunded' ? 'Dana Berhasil Dikembalikan' : 'Menunggu Proses Kasir/Admin' ?>
                                </span>
                            </div>
                            <?php if (!empty($booking['rekening_refund'])): ?>
                                <div class="text-[11px] mt-1 text-slate-600">Rekening Tujuan: <strong><?= htmlspecialchars($booking['rekening_refund']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($booking['alasan_batal'])): ?>
                                <div class="text-[11px] mt-0.5 text-slate-500 italic">Alasan: "<?= htmlspecialchars($booking['alasan_batal']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Total Biaya Box -->
                <div class="bg-futsal-50 p-4 rounded-2xl border border-futsal-200 flex justify-between items-center">
                    <div>
                        <span class="text-[10px] text-futsal-700 block uppercase font-bold">Total Pembayaran</span>
                        <span class="text-xs text-slate-500">
                            <?= !empty($booking['total_hari']) && $booking['total_hari'] > 1 ? $booking['total_hari'] . ' Hari Sewa' : 'Tarif sewa ' . $booking['durasi'] . ' jam' ?>
                        </span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-futsal-700"><?= formatRupiah($booking['total_harga']) ?></span>
                </div>

                <!-- Barcode & Petunjuk Tiket -->
                <div class="pt-2 text-center space-y-2">
                    <div class="flex items-center justify-center gap-0.5 h-10 w-44 mx-auto">
                        <span class="w-1 bg-slate-900 h-full"></span>
                        <span class="w-0.5 bg-slate-900 h-full"></span>
                        <span class="w-2 bg-slate-900 h-full"></span>
                        <span class="w-1 bg-slate-900 h-full"></span>
                        <span class="w-0.5 bg-slate-900 h-full"></span>
                        <span class="w-3 bg-slate-900 h-full"></span>
                        <span class="w-1 bg-slate-900 h-full"></span>
                        <span class="w-2 bg-slate-900 h-full"></span>
                        <span class="w-0.5 bg-slate-900 h-full"></span>
                        <span class="w-1.5 bg-slate-900 h-full"></span>
                        <span class="w-1 bg-slate-900 h-full"></span>
                        <span class="w-2.5 bg-slate-900 h-full"></span>
                    </div>
                    <span class="font-mono text-xs font-bold text-slate-700 block"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                    <p class="text-[11px] text-slate-400">Tunjukkan barcode / kode ini kepada kasir arena saat kick-off.</p>
                </div>

            </div>
        </div>

        <!-- Floating WhatsApp Button on Mobile -->
        <div class="mt-6 text-center no-print">
            <a href="https://wa.me/<?= sanitizeWhatsApp($settings['no_wa']) ?>?text=<?= $pesanWa ?>" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-6 py-3.5 rounded-2xl text-xs sm:text-sm shadow-xl shadow-emerald-600/30 transition">
                <i class="fa-brands fa-whatsapp text-lg"></i>
                <span>Konfirmasi via WhatsApp Admin</span>
            </a>
        </div>

    </div>
</div>

<style>
    @media print {
        .no-print, header, footer, nav { display: none !important; }
        body { background: white !important; padding: 0 !important; }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
