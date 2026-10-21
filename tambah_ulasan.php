<?php
/**
 * Handler Penambahan Ulasan Lapangan
 * Fun Futsal
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    $lapanganId = (int)($_POST['lapangan_id'] ?? 1);
    $namaUser = cleanInput($_POST['nama_user'] ?? 'Pemain Fun Futsal');
    $rating = (int)($_POST['rating'] ?? 5);
    $komentar = cleanInput($_POST['komentar'] ?? '');
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if ($rating < 1 || $rating > 5) $rating = 5;

    if (!empty($komentar)) {
        $stmt = $db->prepare("INSERT INTO ulasan (user_id, lapangan_id, nama_user, rating, komentar) VALUES (:uid, :lid, :nama, :rating, :komentar)");
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $lapanganId,
            ':nama' => $namaUser,
            ':rating' => $rating,
            ':komentar' => $komentar
        ]);
    }

    header("Location: sewa.php?id=" . $lapanganId . "&review_success=1");
    exit;
}

header("Location: index.php");
exit;
