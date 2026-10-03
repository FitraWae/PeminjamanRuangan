<?php
require_once __DIR__ . '/boothstrap.php';

$pesan_error = '';

if (isset($_SESSION['iduser'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? '/project/admin/ruangan.php' : '/project/user/ajukan.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $pesan_error = 'Email dan password wajib diisi.';
    } else {
        $user = User::dari_login($email, $password);

        if ($user === null) {
            $pesan_error = 'Email atau password salah.';
        } else {
            $role_aktif = $user->get_role_aktif();

            if ($role_aktif === null) {
                $pesan_error = 'Akun ini belum punya role aktif. Hubungi admin.';
            } else {
                $_SESSION['iduser'] = $user->get_iduser();
                $_SESSION['nama']   = $user->get_nama();
                $_SESSION['role']   = $role_aktif->get_data()['nama_role'];

                header('Location: ' . ($_SESSION['role'] === 'admin' ? '/project/admin/ruangan.php' : '/project/user/ajukan.php'));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Peminjaman Ruangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #EBF1FF;
            color: #0f172a;
            min-height: 100vh;
        }

        .halaman {
            min-height: 100vh;
            padding: 48px 32px 32px;
            display: flex;
            flex-direction: column;
        }

        .kartu {
            width: 100%;
            max-width: 460px;
            margin: 0;
            flex: 1;
        }

        /* Brand */
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 96px;
        }
        .brand-ikon {
            width: 28px;
            height: 28px;
            background: #1e40af;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .brand-ikon svg { width: 16px; height: 16px; fill: none; stroke: #fff; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .brand-judul { font-size: 13px; font-weight: 600; line-height: 1.2; }
        .brand-sub   { font-size: 10px; color: #334155; line-height: 1.2; }

        /* Heading */
        h1 { font-size: 32px; font-weight: 500; margin-bottom: 6px; }
        .subjudul { font-size: 13px; color: #64748b; margin-bottom: 28px; }

        /* Form */
        .grup { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px; }

        .input-wrap { position: relative; }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            height: 40px;
            padding: 0 40px 0 14px;
            background: #fff;
            border: 1px solid transparent;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            color: #0f172a;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input::placeholder { color: #94a3b8; }
        input:focus {
            border-color: #3b6ef5;
            box-shadow: 0 0 0 3px rgba(59, 110, 245, .15);
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            display: flex;
            color: #334155;
        }
        .toggle-pw svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

        .btn {
            width: 100%;
            height: 40px;
            margin-top: 8px;
            background: #3b6ef5;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s;
        }
        .btn:hover { background: #2f5ce0; }

        .link-daftar { text-align: center; font-size: 10px; color: #475569; margin-top: 24px; }
        .link-daftar a { color: #3b6ef5; font-weight: 600; text-decoration: none; }
        .link-daftar a:hover { text-decoration: underline; }

        .footer { font-size: 8px; color: #64748b; line-height: 1.6; margin-top: 48px; }

        /* Notifikasi */
        .notif {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 20px;
            background: #fee2e2;
            color: #b91c1c;
        }
    </style>
</head>
<body>
<div class="halaman">
    <div class="kartu">

        <div class="brand">
            <div class="brand-ikon">
                <svg viewBox="0 0 24 24"><path d="M3 10l9-6 9 6"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 21h18"/></svg>
            </div>
            <div>
                <div class="brand-judul">Layanan Peminjaman Ruang</div>
                <div class="brand-sub">Sistem Informasi Sarana dan Prasarana Kampus</div>
            </div>
        </div>

        <h1>Masuk</h1>
        <p class="subjudul">Masukkan alamat email dan kata sandi untuk masuk ke akun Anda.</p>

        <?php if ($pesan_error !== ''): ?>
            <div class="notif"><?= htmlspecialchars($pesan_error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'akses_ditolak'): ?>
            <div class="notif">Kamu tidak punya akses ke halaman tersebut.</div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="grup">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <input type="email" id="email" name="email" placeholder="Masukkan email@institusi.ac.id Anda ..."
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="grup">
                <label for="password">Kata Sandi</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi Anda ..." required>
                    <button type="button" class="toggle-pw" data-target="password" aria-label="Tampilkan kata sandi">
                        <svg viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn">Masuk</button>

            <p class="link-daftar">Belum punya akun? <a href="daftar.php">Daftar di sini</a></p>
        </form>
    </div>

    <p class="footer">
        Enkripsi End-to-End<br>
        © <?= date('Y') ?> Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.
    </p>
</div>

<script>
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
</script>
</body>
</html>