<?php
/**
 * AJAX Endpoint untuk Pencarian Riwayat Booking Cepat
 * Fun Futsal
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$keyword = isset($_GET['q']) ? cleanInput($_GET['q']) : '';

if (empty($keyword)) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan masukkan Kode Booking atau Nomor WhatsApp.'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Cari berdasarkan kode booking, no hp, atau nama pemesan
    $stmt = $db->prepare("SELECT b.*, l.nama AS nama_lapangan, l.jenis AS jenis_lapangan, l.foto AS foto_lapangan 
                          FROM booking b 
                          JOIN lapangan l ON b.lapangan_id = l.id 
                          WHERE b.kode_booking LIKE :q1 OR b.no_hp LIKE :q2 OR b.nama_pemesan LIKE :q3 
                          ORDER BY b.id DESC LIMIT 10");
    $term = '%' . $keyword . '%';
    $stmt->execute([':q1' => $term, ':q2' => $term, ':q3' => $term]);
    $results = $stmt->fetchAll();

    $list = [];
    foreach ($results as $b) {
        $statusLabel = '';
        $statusClass = '';
        switch (strtolower($b['status'])) {
            case 'confirmed':
                $statusLabel = 'Terkonfirmasi';
                $statusClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                break;
            case 'pending':
                $statusLabel = 'Menunggu Pembayaran';
                $statusClass = 'bg-amber-100 text-amber-800 border-amber-200';
                break;
            case 'completed':
                $statusLabel = 'Selesai';
                $statusClass = 'bg-blue-100 text-blue-800 border-blue-200';
                break;
            case 'cancelled':
                $statusLabel = 'Dibatalkan';
                $statusClass = 'bg-rose-100 text-rose-800 border-rose-200';
                break;
            default:
                $statusLabel = ucfirst($b['status']);
                $statusClass = 'bg-slate-100 text-slate-800 border-slate-200';
                break;
        }

        $list[] = [
            'id' => (int)$b['id'],
            'kode_booking' => $b['kode_booking'],
            'nama_lapangan' => $b['nama_lapangan'],
            'jenis_lapangan' => $b['jenis_lapangan'] ?: 'Futsal Arena',
            'foto_lapangan' => $b['foto_lapangan'] ?: 'assets/images/lapangan-1.jpg',
            'nama_pemesan' => $b['nama_pemesan'],
            'nama_tim' => !empty($b['nama_tim']) ? $b['nama_tim'] : null,
            'no_hp' => $b['no_hp'],
            'tanggal' => $b['tanggal'],
            'tanggal_indo' => formatTanggalIndo($b['tanggal']),
            'jam_mulai' => substr($b['jam_mulai'], 0, 5),
            'jam_selesai' => substr($b['jam_selesai'], 0, 5),
            'durasi' => (int)$b['durasi'],
            'total_harga' => (int)$b['total_harga'],
            'total_harga_formatted' => formatRupiah($b['total_harga']),
            'status' => $b['status'],
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'metode_pembayaran' => strtoupper($b['metode_pembayaran']),
            'invoice_url' => 'invoice.php?kode=' . urlencode($b['kode_booking']),
            'created_at_formatted' => date('d M Y, H:i', strtotime($b['created_at'] ?? 'now')) . ' WIB'
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($list),
        'data' => $list
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mencari riwayat: ' . $e->getMessage()
    ]);
}
