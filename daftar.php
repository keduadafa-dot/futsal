<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

$settings = getPengaturan();
$error = '';
$redirect = !empty($_REQUEST['redirect']) ? cleanInput($_REQUEST['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = cleanInput($_POST['nama_lengkap'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $noHp = cleanInput($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $namaTim = cleanInput($_POST['nama_tim'] ?? '');

    if (empty($namaLengkap) || empty($username) || empty($password) || empty($noHp)) {
        $error = 'Harap lengkapi semua kolom wajib!';
    } else {
        try {
            $db = getDBConnection();
            
            // Cek username kembar
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
            $stmtCheck->execute([':u' => $username]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = 'Username sudah dipakai oleh pengguna lain!';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsert = $db->prepare("INSERT INTO users 
                    (username, password, nama_lengkap, email, no_hp, nama_tim, role, status) 
                    VALUES 
                    (:u, :p, :nama, :email, :hp, :tim, 'user', 'aktif')");
                
                $stmtInsert->execute([
                    ':u'      => $username,
                    ':p'      => $hash,
                    ':nama'   => $namaLengkap,
                    ':email'  => $email,
                    ':hp'     => $noHp,
                    ':tim'    => $namaTim
                ]);

                $loc = "login.php?registered=1" . (!empty($redirect) ? "&redirect=" . urlencode($redirect) : "");
                header("Location: " . $loc);
                exit;
            }
        } catch (Exception $e) {
            $error = 'Gagal mendaftar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Akun Baru - <?= htmlspecialchars($settings['nama_arena']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: {
                        futsal: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            600: '#16a34a',
                            700: '#15803d',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 py-8">
    
    <div class="max-w-lg w-full bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-2xl">
        
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-futsal-600 text-white flex items-center justify-center text-xl mx-auto mb-2 shadow-md">
                <i class="fa-solid fa-futbol"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900">Buat Akun Member</h1>
            <p class="text-xs text-slate-500 font-semibold mt-1">Daftar akun pemain di <strong>Fun Futsal</strong> untuk sewa & booking instan</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-3.5 rounded-xl text-xs font-semibold mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- DAFTAR DENGAN GOOGLE (LENGKAP & CEPAT) -->
        <div class="mb-5 space-y-2">
            <a href="google_auth.php" class="w-full bg-white hover:bg-slate-50 border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-bold py-3 px-4 rounded-2xl text-xs sm:text-sm transition shadow-xs flex items-center justify-center gap-3 active:scale-98">
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Daftar Cepat dengan Google</span>
            </a>
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-slate-400 text-[11px] uppercase font-bold tracking-wider">atau isi formulir pendaftaran</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
        </div>

        <form action="daftar.php" method="POST" class="space-y-4">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="nama_lengkap" required placeholder="Contoh: Budi Santoso" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Username <span class="text-rose-500">*</span></label>
                    <input type="text" name="username" required placeholder="budi123" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                    <input type="tel" name="no_hp" required placeholder="08xxxxxxxxxx" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email (Opsional)</label>
                    <input type="email" name="email" placeholder="budi@gmail.com" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Tim Futsal (Opsional)</label>
                <input type="text" name="nama_tim" placeholder="Contoh: Barata Squad" class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-xs sm:text-sm rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-futsal-600 focus:outline-none">
            </div>

            <button type="submit" class="w-full bg-futsal-600 hover:bg-futsal-700 text-white font-black py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-futsal-600/30 flex items-center justify-center gap-2 mt-4">
                <i class="fa-solid fa-user-plus"></i>
                <span>Daftar Akun Baru</span>
            </button>
        </form>

        <div class="text-center mt-5 text-xs text-slate-500">
            Sudah punya akun? <a href="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" class="text-futsal-600 font-bold hover:underline">Masuk di sini</a>
        </div>
    </div>

</body>
</html>
