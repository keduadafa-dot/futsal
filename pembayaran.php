<?php
$pageTitle = 'Konfirmasi Pembayaran Sewa';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib Login / Daftar Sebelum Menyelesaikan Pembayaran
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    $lapId = isset($_POST['lapangan_id']) ? (int)$_POST['lapangan_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 1);
    $_SESSION['login_notice'] = "Silakan masuk ke akun atau mendaftar terlebih dahulu untuk menyelesaikan pembayaran sewa lapangan.";
    header("Location: login.php?redirect=" . urlencode("sewa.php?id=" . $lapId));
    exit;
}

$db = getDBConnection();
$settings = getPengaturan();

// Jika form disubmit dari sewa.php atau langsung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lapangan_id'])) {
    $lapanganId = (int)$_POST['lapangan_id'];
    $tanggal = cleanInput($_POST['tanggal']);
    $tanggalSelesai = !empty($_POST['tanggal_selesai']) ? cleanInput($_POST['tanggal_selesai']) : $tanggal;
    $totalHari = !empty($_POST['total_hari']) ? max(1, (int)$_POST['total_hari']) : 1;
    $jamMulai = cleanInput($_POST['jam_mulai']);
    $jamSelesai = cleanInput($_POST['jam_selesai']);
    $durasi = (int)$_POST['durasi'];
    $totalHarga = (int)$_POST['total_harga'];
    $kodeMember = !empty($_POST['kode_member']) ? cleanInput($_POST['kode_member']) : null;
    $diskon = !empty($_POST['diskon']) ? (int)$_POST['diskon'] : 0;
    $namaPemesan = !empty($_POST['nama_pemesan']) ? cleanInput($_POST['nama_pemesan']) : ($_SESSION['user_name'] ?? 'Pelanggan Fun Futsal');
    $noHp = !empty($_POST['no_hp']) ? cleanInput($_POST['no_hp']) : ($_SESSION['user_phone'] ?? '081234567890');
    $namaTim = cleanInput($_POST['nama_tim'] ?? ($_SESSION['user_team'] ?? ''));
    $catatan = cleanInput($_POST['catatan'] ?? '');

    // Ambil info lapangan
    $stmtLap = $db->prepare("SELECT * FROM lapangan WHERE id = :id");
    $stmtLap->execute([':id' => $lapanganId]);
    $lapangan = $stmtLap->fetch();

    if (!$lapangan) {
        header("Location: sewa.php");
        exit;
    }

    // Jika user submit pembayaran final
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'final_bayar') {
        $metodePembayaran = !empty($_POST['sub_metode']) ? cleanInput($_POST['sub_metode']) : cleanInput($_POST['metode_pembayaran'] ?? 'QRIS');
        $kodeBooking = generateKodeBooking();

        // Handle upload bukti bayar jika ada
        $buktiBayarName = null;
        if (isset($_FILES['bukti_bayar']) && $_FILES['bukti_bayar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (in_array($ext, $allowed)) {
                $newFileName = 'BUKTI-' . time() . '-' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $uploadDir . $newFileName)) {
                    $buktiBayarName = 'uploads/' . $newFileName;
                }
            }
        }

        // Cek kembali ketersediaan slot sebelum simpan
        if (!isSlotAvailable($lapanganId, $tanggal, $jamMulai, $jamSelesai)) {
            $errorSlot = "Maaf, slot waktu tersebut baru saja dipesan oleh orang lain. Silakan pilih slot lain.";
        } else {
            // Simpan ke database (dengan user_id jika user sudah login)
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $stmtInsert = $db->prepare("INSERT INTO booking 
                (user_id, kode_booking, lapangan_id, nama_pemesan, no_hp, nama_tim, tanggal, tanggal_selesai, total_hari, jam_mulai, jam_selesai, durasi, total_harga, kode_member, diskon, metode_pembayaran, bukti_bayar, status, catatan) 
                VALUES 
                (:uid, :kode, :lapangan_id, :nama, :hp, :tim, :tgl, :tgl_selesai, :total_hari, :jam_m, :jam_s, :durasi, :total, :kode_member, :diskon, :metode, :bukti, 'pending', :catatan)");
            
            $stmtInsert->execute([
                ':uid'         => $userId,
                ':kode'        => $kodeBooking,
                ':lapangan_id' => $lapanganId,
                ':nama'        => $namaPemesan,
                ':hp'          => $noHp,
                ':tim'         => $namaTim,
                ':tgl'         => $tanggal,
                ':tgl_selesai' => $tanggalSelesai,
                ':total_hari'  => $totalHari,
                ':jam_m'       => $jamMulai,
                ':jam_s'       => $jamSelesai,
                ':durasi'      => $durasi,
                ':total'       => $totalHarga,
                ':kode_member' => $kodeMember,
                ':diskon'      => $diskon,
                ':metode'      => $metodePembayaran,
                ':bukti'       => $buktiBayarName,
                ':catatan'     => $catatan,
            ]);

            // Simpan kode booking terakhir di session untuk riwayat cepat
            $_SESSION['last_booking_kode'] = $kodeBooking;

            // Redirect ke invoice
            header("Location: invoice.php?kode=" . $kodeBooking);
            exit;
        }
    }
} else {
    header("Location: sewa.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-slate-100 py-6 sm:py-10 pb-32 md:pb-12 min-h-screen">
    <div class="max-w-2xl mx-auto px-3 sm:px-6">
        
        <!-- Header Step Progress Mobile -->
        <div class="flex items-center justify-between mb-4">
            <a href="sewa.php?id=<?= $lapanganId ?>" class="text-xs font-bold text-slate-600 hover:text-futsal-600 flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <span class="text-xs font-bold text-futsal-700 bg-futsal-100 px-3 py-1 rounded-full">
                Langkah 2 dari 2
            </span>
        </div>

        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-slate-900">Pembayaran Sewa</h1>
            <p class="text-slate-500 text-xs mt-1">Konfirmasi jadwal dan pilih cara pembayaran.</p>
        </div>

        <?php if (!empty($errorSlot)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-2xl text-xs sm:text-sm mb-5 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-base"></i>
                <div><?= $errorSlot ?> <a href="sewa.php?id=<?= $lapanganId ?>" class="font-bold underline ml-1">Pilih Ulang</a></div>
            </div>
        <?php endif; ?>

        <form action="pembayaran.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="aksi" value="final_bayar">
            <input type="hidden" name="lapangan_id" value="<?= $lapanganId ?>">
            <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
            <input type="hidden" name="tanggal_selesai" value="<?= $tanggalSelesai ?>">
            <input type="hidden" name="total_hari" value="<?= $totalHari ?>">
            <input type="hidden" name="jam_mulai" value="<?= $jamMulai ?>">
            <input type="hidden" name="jam_selesai" value="<?= $jamSelesai ?>">
            <input type="hidden" name="durasi" value="<?= $durasi ?>">
            <input type="hidden" name="total_harga" value="<?= $totalHarga ?>">
            <input type="hidden" name="kode_member" value="<?= htmlspecialchars($kodeMember ?? '') ?>">
            <input type="hidden" name="diskon" value="<?= $diskon ?>">
            <input type="hidden" name="nama_pemesan" value="<?= htmlspecialchars($namaPemesan) ?>">
            <input type="hidden" name="no_hp" value="<?= htmlspecialchars($noHp) ?>">
            <input type="hidden" name="nama_tim" value="<?= htmlspecialchars($namaTim) ?>">
            <input type="hidden" name="catatan" value="<?= htmlspecialchars($catatan) ?>">

            <!-- Ringkasan Singkat Card -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="text-sm font-black text-slate-900"><?= htmlspecialchars($lapangan['nama']) ?></div>
                        <div class="text-[11px] text-futsal-600 font-semibold"><?= htmlspecialchars($lapangan['jenis']) ?> (<?= htmlspecialchars($lapangan['kategori'] ?? 'Indoor') ?>)</div>
                    </div>
                    <span class="text-base font-black text-futsal-700"><?= formatRupiah($totalHarga) ?></span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs text-slate-600">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Pemesan:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($namaPemesan) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">WhatsApp:</span>
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($noHp) ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Tanggal Main:</span>
                        <span class="font-bold text-slate-800">
                            <?= date('d M Y', strtotime($tanggal)) ?><?= ($tanggalSelesai !== $tanggal) ? ' s/d ' . date('d M Y', strtotime($tanggalSelesai)) . ' (' . $totalHari . ' Hari)' : '' ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Jam & Durasi:</span>
                        <span class="font-bold text-futsal-700"><?= substr($jamMulai, 0, 5) ?> - <?= substr($jamSelesai, 0, 5) ?> (<?= $durasi ?> Jam/hari)</span>
                    </div>
                </div>

                <?php if ($diskon > 0): ?>
                    <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 flex items-center justify-between text-xs">
                        <span class="text-amber-800 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-crown text-amber-500"></i> Diskon Membership (<?= htmlspecialchars($kodeMember) ?>):
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pilihan Metode Pembayaran -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-futsal-600"></i>
                        <span>Pilih Metode Pembayaran</span>
                    </h2>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved"></i> Aman &amp; Terverifikasi
                    </span>
                </div>

                <!-- Input Hidden untuk Menyimpan Detail Metode Terpilih -->
                <input type="hidden" name="sub_metode" id="input_sub_metode" value="QRIS - GoPay">

                <div class="space-y-3">
                    <!-- Option 1: QRIS e-Wallet -->
                    <div class="payment-card border-2 border-futsal-600 bg-futsal-50/40 rounded-2xl p-4 transition-all" id="card-qris">
                        <label class="cursor-pointer flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-futsal-600 text-xl shadow-xs">
                                    <i class="fa-solid fa-qrcode"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-slate-900 text-sm">QRIS e-Wallet</span>
                                        <span class="text-[10px] font-extrabold bg-futsal-600 text-white px-2 py-0.5 rounded-full">Instan</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">GoPay, DANA, OVO, ShopeePay &amp; Semua e-Wallet / m-Banking</p>
                                </div>
                            </div>
                            <input type="radio" name="metode_pembayaran" value="qris" checked class="text-futsal-600 focus:ring-futsal-500 w-4 h-4 cursor-pointer">
                        </label>

                        <!-- Sub-Options e-Wallet (Hanya tampil jika QRIS dipilih) -->
                        <div id="qris-details" class="mt-4 pt-4 border-t border-slate-200/80 space-y-3">
                            <div class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                                <span>Pilih e-Wallet:</span>
                                <span class="text-[10px] text-slate-400 font-normal">Klik untuk ganti QR Code</span>
                            </div>
                            
                            <!-- Grid 4 e-Wallet -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <!-- GoPay -->
                                <button type="button" class="ewallet-btn active flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-futsal-600 shadow-sm transition hover:border-futsal-500"
                                    data-wallet="GoPay"
                                    data-label="QRIS - GoPay"
                                    data-qr="assets/images/qris-gopay.png?v=<?= time() ?>">
                                    <img src="assets/images/logo-gopay.svg" alt="GoPay" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">GoPay</span>
                                </button>

                                <!-- DANA -->
                                <button type="button" class="ewallet-btn flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-slate-200 shadow-sm transition hover:border-futsal-500"
                                    data-wallet="DANA"
                                    data-label="QRIS - DANA"
                                    data-qr="assets/images/qris-dana.png?v=<?= time() ?>">
                                    <img src="assets/images/logo-dana.svg" alt="DANA" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">DANA</span>
                                </button>

                                <!-- OVO -->
                                <button type="button" class="ewallet-btn flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-slate-200 shadow-sm transition hover:border-futsal-500"
                                    data-wallet="OVO"
                                    data-label="QRIS - OVO"
                                    data-qr="assets/images/qris-ovo.svg?v=<?= time() ?>">
                                    <img src="assets/images/logo-ovo.svg" alt="OVO" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">OVO</span>
                                </button>

                                <!-- ShopeePay -->
                                <button type="button" class="ewallet-btn flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-slate-200 shadow-sm transition hover:border-futsal-500"
                                    data-wallet="ShopeePay"
                                    data-label="QRIS - ShopeePay"
                                    data-qr="assets/images/qris-shopeepay.svg?v=<?= time() ?>">
                                    <img src="assets/images/logo-shopeepay.svg" alt="ShopeePay" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">ShopeePay</span>
                                </button>
                            </div>

                            <!-- QR Code Preview Box -->
                            <div class="bg-white rounded-2xl p-4 border border-slate-200 flex flex-col items-center text-center">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-[11px] font-extrabold text-slate-700 mb-2.5">
                                    <i class="fa-solid fa-qrcode text-futsal-600"></i>
                                    <span>Scan Kode QRIS <span id="current-wallet-name" class="text-futsal-700 font-black">GoPay</span></span>
                                </div>
                                <div class="bg-slate-50 p-3 rounded-2xl border-2 border-dashed border-slate-300 shadow-inner">
                                    <img id="qris-display-img" src="assets/images/qris-gopay.png?v=<?= time() ?>" alt="QRIS Futsal" class="w-48 h-48 sm:w-56 sm:h-56 object-contain bg-white rounded-xl shadow-xs transition-all duration-300">
                                </div>
                                <p class="text-[11px] text-slate-500 mt-2 font-medium">Buka aplikasi dompet digital &amp; scan QR di atas</p>
                                <div class="text-[10px] text-slate-400 mt-0.5">NMID: ID102008889999001 &bull; A/N: Fun Futsal Arena</div>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Transfer Bank -->
                    <div class="payment-card border-2 border-slate-200 bg-white rounded-2xl p-4 transition-all" id="card-transfer">
                        <label class="cursor-pointer flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-700 text-xl shadow-xs">
                                    <i class="fa-solid fa-building-columns"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-slate-900 text-sm">Transfer Bank</span>
                                        <span class="text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">BCA, Mandiri, BRI</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Transfer langsung via ATM, Mobile Banking, atau Internet Banking</p>
                                </div>
                            </div>
                            <input type="radio" name="metode_pembayaran" value="transfer" class="text-futsal-600 focus:ring-futsal-500 w-4 h-4 cursor-pointer">
                        </label>

                        <!-- Sub-Options Bank (Hanya tampil jika Transfer dipilih) -->
                        <div id="transfer-details" class="hidden mt-4 pt-4 border-t border-slate-200/80 space-y-3">
                            <div class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                                <span>Pilih Bank Tujuan:</span>
                                <span class="text-[10px] text-slate-400 font-normal">Klik untuk ganti nomor rekening</span>
                            </div>

                            <!-- Grid 3 Bank -->
                            <div class="grid grid-cols-3 gap-2.5">
                                <!-- Bank BCA -->
                                <button type="button" class="bank-btn active flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-futsal-600 shadow-sm transition hover:border-futsal-500"
                                    data-bank="Bank BCA"
                                    data-label="Transfer Bank BCA"
                                    data-rek="8830123456"
                                    data-an="Fun Futsal Arena"
                                    data-logo="assets/images/logo-bca.svg">
                                    <img src="assets/images/logo-bca.svg" alt="Bank BCA" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">BCA</span>
                                </button>

                                <!-- Bank Mandiri -->
                                <button type="button" class="bank-btn flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-slate-200 shadow-sm transition hover:border-futsal-500"
                                    data-bank="Bank Mandiri"
                                    data-label="Transfer Bank Mandiri"
                                    data-rek="1370098765432"
                                    data-an="Fun Futsal Arena"
                                    data-logo="assets/images/logo-mandiri.svg">
                                    <img src="assets/images/logo-mandiri.svg" alt="Bank Mandiri" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">Mandiri</span>
                                </button>

                                <!-- Bank BRI -->
                                <button type="button" class="bank-btn flex flex-col items-center justify-center p-2.5 bg-white rounded-xl border-2 border-slate-200 shadow-sm transition hover:border-futsal-500"
                                    data-bank="Bank BRI"
                                    data-label="Transfer Bank BRI"
                                    data-rek="012301098765501"
                                    data-an="Fun Futsal Arena"
                                    data-logo="assets/images/logo-bri.svg">
                                    <img src="assets/images/logo-bri.svg" alt="Bank BRI" class="h-6 w-auto object-contain mb-1">
                                    <span class="text-[11px] font-bold text-slate-800">BRI</span>
                                </button>
                            </div>

                            <!-- Bank Info Card -->
                            <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <img id="current-bank-logo" src="assets/images/logo-bca.svg" alt="Bank" class="h-5 w-auto object-contain">
                                        <span id="current-bank-name" class="font-extrabold text-xs text-slate-800">Bank BCA</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-futsal-700 bg-futsal-50 border border-futsal-200 px-2 py-0.5 rounded-full">Rekening Resmi Arena</span>
                                </div>
                                <div class="flex items-center justify-between bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                                    <div>
                                        <div class="text-[10px] font-semibold text-slate-400">Nomor Rekening:</div>
                                        <div id="current-bank-rek" class="font-mono text-base sm:text-lg font-black text-slate-900 tracking-wider">8830123456</div>
                                        <div class="text-[10px] text-slate-500">A/N: <span id="current-bank-an" class="font-bold text-slate-700">Fun Futsal Arena</span></div>
                                    </div>
                                    <button type="button" id="btn-copy-rek" class="text-xs font-bold text-futsal-600 bg-white border border-futsal-200 hover:bg-futsal-50 px-3 py-2 rounded-xl shadow-xs transition flex items-center gap-1.5 active:scale-95">
                                        <i class="fa-regular fa-copy"></i>
                                        <span id="copy-text">Salin No. Rek</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Option 3: Bayar Kasir -->
                    <div class="payment-card border-2 border-slate-200 bg-white rounded-2xl p-4 transition-all" id="card-cash">
                        <label class="cursor-pointer flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-700 text-xl shadow-xs">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <span class="font-black text-slate-900 text-sm block">Bayar di Tempat (Kasir / DP)</span>
                                    <span class="text-[11px] text-slate-500">Bayar tunai di lokasi counter saat kick-off</span>
                                </div>
                            </div>
                            <input type="radio" name="metode_pembayaran" value="cash" class="text-futsal-600 focus:ring-futsal-500 w-4 h-4 cursor-pointer">
                        </label>
                        
                        <!-- Sub Details Cash -->
                        <div id="cash-details" class="hidden mt-4 pt-4 border-t border-slate-200/80 text-xs text-slate-600 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                            <i class="fa-solid fa-circle-info text-futsal-600 mr-1.5"></i>
                            Silakan datang ke resepsionis/kasir Fun Futsal <strong>15 menit sebelum kick-off</strong> untuk menyelesaikan pembayaran tunai dan konfirmasi peminjaman rompi &amp; bola.
                        </div>
                    </div>
                </div>

                <!-- Bukti Bayar Upload Mobile -->
                <div class="pt-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                        Upload Bukti Transfer / Resi <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="file" name="bukti_bayar" accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-futsal-50 file:text-futsal-700 bg-white border border-slate-200 rounded-xl p-1">
                </div>
            </div>

            <!-- Submit Final Button -->
            <button type="submit" class="w-full bg-futsal-600 hover:bg-futsal-700 text-white font-black py-4 px-4 rounded-2xl text-sm shadow-xl shadow-futsal-600/30 transition flex items-center justify-center gap-2 active:scale-[0.99]">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span>Konfirmasi &amp; Terbitkan Invoice</span>
            </button>
        </form>
    </div>
</div>

<script>
    // Elements
    const paymentRadios = document.querySelectorAll('input[name="metode_pembayaran"]');
    const inputSubMetode = document.getElementById('input_sub_metode');
    
    const cardQris = document.getElementById('card-qris');
    const cardTransfer = document.getElementById('card-transfer');
    const cardCash = document.getElementById('card-cash');

    const qrisDetails = document.getElementById('qris-details');
    const transferDetails = document.getElementById('transfer-details');
    const cashDetails = document.getElementById('cash-details');

    const ewalletBtns = document.querySelectorAll('.ewallet-btn');
    const qrisDisplayImg = document.getElementById('qris-display-img');
    const currentWalletName = document.getElementById('current-wallet-name');

    const bankBtns = document.querySelectorAll('.bank-btn');
    const currentBankLogo = document.getElementById('current-bank-logo');
    const currentBankName = document.getElementById('current-bank-name');
    const currentBankRek = document.getElementById('current-bank-rek');
    const currentBankAn = document.getElementById('current-bank-an');
    const btnCopyRek = document.getElementById('btn-copy-rek');
    const copyText = document.getElementById('copy-text');

    // Switch Main Payment Method
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            // Reset all cards
            [cardQris, cardTransfer, cardCash].forEach(card => {
                card.classList.remove('border-futsal-600', 'bg-futsal-50/40');
                card.classList.add('border-slate-200', 'bg-white');
            });
            // Hide all sub details
            qrisDetails.classList.add('hidden');
            transferDetails.classList.add('hidden');
            cashDetails.classList.add('hidden');

            const val = radio.value;
            if (val === 'qris') {
                cardQris.classList.add('border-futsal-600', 'bg-futsal-50/40');
                cardQris.classList.remove('border-slate-200', 'bg-white');
                qrisDetails.classList.remove('hidden');
                
                // Set active e-wallet label
                const activeWallet = document.querySelector('.ewallet-btn.active');
                if (activeWallet) {
                    inputSubMetode.value = activeWallet.dataset.label;
                }
            } else if (val === 'transfer') {
                cardTransfer.classList.add('border-futsal-600', 'bg-futsal-50/40');
                cardTransfer.classList.remove('border-slate-200', 'bg-white');
                transferDetails.classList.remove('hidden');

                // Set active bank label
                const activeBank = document.querySelector('.bank-btn.active');
                if (activeBank) {
                    inputSubMetode.value = activeBank.dataset.label;
                }
            } else if (val === 'cash') {
                cardCash.classList.add('border-futsal-600', 'bg-futsal-50/40');
                cardCash.classList.remove('border-slate-200', 'bg-white');
                cashDetails.classList.remove('hidden');
                inputSubMetode.value = 'Bayar di Tempat (Kasir)';
            }
        });
    });

    // Switch e-Wallet (GoPay, DANA, OVO, ShopeePay)
    ewalletBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            ewalletBtns.forEach(b => {
                b.classList.remove('active', 'border-futsal-600');
                b.classList.add('border-slate-200');
            });
            btn.classList.add('active', 'border-futsal-600');
            btn.classList.remove('border-slate-200');

            const wallet = btn.dataset.wallet;
            const qrSrc = btn.dataset.qr;
            const label = btn.dataset.label;

            currentWalletName.textContent = wallet;
            qrisDisplayImg.src = qrSrc;
            inputSubMetode.value = label;
        });
    });

    // Switch Bank (BCA, Mandiri, BRI)
    bankBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            bankBtns.forEach(b => {
                b.classList.remove('active', 'border-futsal-600');
                b.classList.add('border-slate-200');
            });
            btn.classList.add('active', 'border-futsal-600');
            btn.classList.remove('border-slate-200');

            const bank = btn.dataset.bank;
            const rek = btn.dataset.rek;
            const an = btn.dataset.an;
            const logo = btn.dataset.logo;
            const label = btn.dataset.label;

            currentBankName.textContent = bank;
            currentBankRek.textContent = rek;
            currentBankAn.textContent = an;
            currentBankLogo.src = logo;
            inputSubMetode.value = label;
        });
    });

    // Copy Account Number
    if (btnCopyRek) {
        btnCopyRek.addEventListener('click', () => {
            const rekText = currentBankRek.textContent.trim();
            navigator.clipboard.writeText(rekText).then(() => {
                copyText.textContent = 'Tersalin!';
                btnCopyRek.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
                btnCopyRek.classList.remove('text-futsal-600', 'border-futsal-200');
                
                setTimeout(() => {
                    copyText.textContent = 'Salin No. Rek';
                    btnCopyRek.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
                    btnCopyRek.classList.add('text-futsal-600', 'border-futsal-200');
                }, 2000);
            }).catch(err => {
                // Fallback prompt
                prompt("Salin nomor rekening:", rekText);
            });
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
