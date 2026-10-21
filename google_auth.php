<?php
/**
 * Modul Autentikasi Google Sign-In & Sign-Up
 * Fun Futsal
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

$db = getDBConnection();
$settings = getPengaturan();

// Akun Google Demo siap pakai untuk simulasi instan
$googleAccounts = [
    [
        'email' => 'dimas.prayoga@gmail.com',
        'nama' => 'Dimas Prayoga',
        'foto' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80',
        'google_id' => 'google_1029384756',
        'no_hp' => '081298765432',
        'nama_tim' => 'Garuda FC'
    ],
    [
        'email' => 'budi.santoso@gmail.com',
        'nama' => 'Budi Santoso',
        'foto' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=150&q=80',
        'google_id' => 'google_9876543210',
        'no_hp' => '085712345678',
        'nama_tim' => 'Barata Squad'
    ],
    [
        'email' => 'reza.futsal@gmail.com',
        'nama' => 'Reza Pratama',
        'foto' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
        'google_id' => 'google_5566778899',
        'no_hp' => '082155558888',
        'nama_tim' => 'Thunder FC'
    ]
];

// Proses Login / Daftar Google jika ada aksi
if (isset($_GET['select_account'])) {
    $accIdx = (int)$_GET['select_account'];
    $selected = $googleAccounts[$accIdx] ?? $googleAccounts[0];

    loginWithGoogleData($db, $selected);
    header("Location: user/index.php?google_login=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_custom_email'])) {
    $email = cleanInput($_POST['google_custom_email']);
    $nama = cleanInput($_POST['google_custom_name'] ?? 'Pengguna Google');
    
    $googleData = [
        'email' => $email,
        'nama' => $nama,
        'foto' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
        'google_id' => 'google_' . time(),
        'no_hp' => '08' . rand(111111111, 999999999),
        'nama_tim' => 'Fun Futsal Team'
    ];

    loginWithGoogleData($db, $googleData);
    header("Location: user/index.php?google_login=1");
    exit;
}

// 1-Click Login Cepat langsung jika diminta
if (isset($_GET['instant'])) {
    loginWithGoogleData($db, $googleAccounts[0]);
    header("Location: user/index.php?google_login=1");
    exit;
}

function loginWithGoogleData($db, $g) {
    // Cek apakah user dengan email / google_id sudah ada
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email OR google_id = :gid LIMIT 1");
    $stmt->execute([':email' => $g['email'], ':gid' => $g['google_id']]);
    $user = $stmt->fetch();

    if ($user) {
        // Update data jika perlu
        $stmtUp = $db->prepare("UPDATE users SET google_id = :gid, auth_provider = 'google', foto = :foto WHERE id = :id");
        $stmtUp->execute([':gid' => $g['google_id'], ':foto' => $g['foto'], ':id' => $user['id']]);
        $userId = $user['id'];
        $userRole = $user['role'];
        $userName = $user['nama_lengkap'];
        $userPhone = $user['no_hp'];
        $userTeam = $user['nama_tim'];
    } else {
        // Buat user baru dari Google
        $username = explode('@', $g['email'])[0] . '_' . rand(10, 99);
        $dummyPass = password_hash('google_auth_' . time(), PASSWORD_BCRYPT);

        $stmtIns = $db->prepare("INSERT INTO users 
            (username, password, nama_lengkap, email, no_hp, nama_tim, role, auth_provider, google_id, foto, status) 
            VALUES 
            (:u, :p, :nama, :email, :hp, :tim, 'user', 'google', :gid, :foto, 'aktif')");
        
        $stmtIns->execute([
            ':u' => $username,
            ':p' => $dummyPass,
            ':nama' => $g['nama'],
            ':email' => $g['email'],
            ':hp' => $g['no_hp'],
            ':tim' => $g['nama_tim'],
            ':gid' => $g['google_id'],
            ':foto' => $g['foto']
        ]);

        $userId = $db->lastInsertId();
        $userRole = 'user';
        $userName = $g['nama'];
        $userPhone = $g['no_hp'];
        $userTeam = $g['nama_tim'];
    }

    // Set Session Login
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $g['email'];
    $_SESSION['user_phone'] = $userPhone;
    $_SESSION['user_team'] = $userTeam;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['user_foto'] = $g['foto'];
    $_SESSION['auth_provider'] = 'google';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk dengan Google - <?= htmlspecialchars($settings['nama_arena']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Roboto', sans-serif; }</style>
</head>
<body class="bg-slate-900/40 min-h-screen flex items-center justify-center p-4 backdrop-blur-sm">

    <div class="max-w-md w-full bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-200">
        <!-- Google Header -->
        <div class="text-center mb-6">
            <svg class="w-10 h-10 mx-auto mb-3" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <h1 class="text-xl font-bold text-slate-800">Login dengan Google</h1>
            <p class="text-xs text-slate-500 mt-1">Lanjutkan ke <strong><?= htmlspecialchars($settings['nama_arena']) ?></strong></p>
        </div>

        <!-- Account Picker List -->
        <div class="space-y-2 mb-6">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Pilih Akun Google:</span>
            <?php foreach ($googleAccounts as $idx => $acc): ?>
                <a href="google_auth.php?select_account=<?= $idx ?>" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50 border border-slate-200 hover:border-slate-300 transition group">
                    <img src="<?= $acc['foto'] ?>" alt="<?= htmlspecialchars($acc['nama']) ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                    <div class="flex-grow min-w-0">
                        <div class="text-sm font-bold text-slate-800 truncate group-hover:text-blue-600"><?= htmlspecialchars($acc['nama']) ?></div>
                        <div class="text-xs text-slate-500 truncate"><?= htmlspecialchars($acc['email']) ?></div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-blue-600 text-xs"></i>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Form Tambah Akun Google Lain -->
        <details class="group border-t border-slate-100 pt-4 mb-4">
            <summary class="text-xs text-blue-600 font-semibold cursor-pointer hover:underline list-none flex items-center justify-between">
                <span>Gunakan akun Google lainnya</span>
                <i class="fa-solid fa-plus text-[10px]"></i>
            </summary>
            <form action="google_auth.php" method="POST" class="mt-3 space-y-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap</label>
                    <input type="text" name="google_custom_name" required placeholder="Nama Anda" class="w-full bg-white border border-slate-200 text-xs rounded-xl px-3 py-2 text-slate-800">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Email Google (@gmail.com)</label>
                    <input type="email" name="google_custom_email" required placeholder="nama@gmail.com" class="w-full bg-white border border-slate-200 text-xs rounded-xl px-3 py-2 text-slate-800">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 rounded-xl transition">
                    Lanjut Masuk
                </button>
            </form>
        </details>

        <div class="text-center pt-2">
            <a href="login.php" class="text-xs text-slate-400 hover:text-slate-600">Batal dan kembali ke halaman login</a>
        </div>
    </div>

</body>
</html>
