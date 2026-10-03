<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$idruangan = (int) ($_GET['id'] ?? 0);
$dataRespon = Ruangan::get_by_id($idruangan);

if (!$dataRespon->status || empty($dataRespon->data)) {
    echo "Ruangan tidak ditemukan. <a href='ruangan.php'>Kembali</a>";
    exit;
}

$d = $dataRespon->data[0];
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruangan = new Ruangan((int) $d['idruangan'], $d['namaruangan'], (int) $d['kapasitas'], $d['fasilitas'], $d['statusruangan']);
    $respon = $ruangan->update(trim($_POST['nama']), (int) $_POST['kapasitas'], trim($_POST['fasilitas']) ?: null);

    if ($respon->status) {
        header('Location: ruangan.php');
        exit;
    }
    $pesan = $respon->message;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Ruangan - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="page">
        <header class="topbar">
            <a class="brand" href="ruangan.php" aria-label="Sistem Informasi Sarana dan Prasarana">
                <span class="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5 12 4l9 6.5V20H3z"/>
                        <path d="M7 12h10M7 15h10M9 8h6"/>
                    </svg>
                </span>
                <span>
                    <span class="brand-name">Layanan Peminjaman Ruang</span>
                    <span class="brand-caption">Sistem Informasi Sarana dan Prasarana</span>
                </span>
            </a>

            <nav class="tabs" aria-label="Navigasi utama">
                <a class="active" aria-current="page" href="ruangan.php">Kelola Ruang</a>
                <a href="approval.php">Persetujuan Pinjam</a>
                <a href="selesai.php">Tandai Kembali</a>
                <a href="kalender.php">Kalender Ruangan</a>
                <a href="riwayat.php">Riwayat Pengajuan</a>
            </nav>

            <a class="account" href="../logout.php" title="Keluar dari akun">
                <span class="account-copy">
                    <strong><?= h($_SESSION['nama'] ?? 'Admin') ?></strong>
                    Keluar
                </span>
                <span class="account-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                        <circle cx="12" cy="8" r="3"/>
                        <path d="M5.5 19c.8-3 3.1-4.5 6.5-4.5s5.7 1.5 6.5 4.5"/>
                    </svg>
                </span>
            </a>
        </header>

        <main>
            <section class="ux-hero">
                <div>
                    <div class="ux-kicker">Room Master Data</div>
                    <h1>Edit Data Ruangan</h1>
                    <p>Perbarui informasi dasar ruangan dan pastikan data kapasitas serta fasilitas tetap akurat.</p>
                </div>
            </section>

            <?php if ($pesan !== ''): ?>
                <p class="message error"><?= h($pesan) ?></p>
            <?php endif; ?>

            <form method="post" action="edit_ruangan.php?id=<?= $idruangan ?>">
                <section class="card">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" id="nama" name="nama" value="<?= h($d['namaruangan']) ?>" placeholder="Nama ruangan ..." required>
                    </div>
                    <div class="form-group">
                        <label for="kapasitas">Kapasitas</label>
                        <input type="number" id="kapasitas" name="kapasitas" value="<?= h($d['kapasitas']) ?>" min="1" placeholder="Jumlah orang" required>
                    </div>
                    <div class="form-group">
                        <label for="fasilitas">Fasilitas</label>
                        <input type="text" id="fasilitas" name="fasilitas" value="<?= h($d['fasilitas'] ?? '') ?>" placeholder="Proyektor, AC, dll (opsional)">
                    </div>
                </section>

                <div class="form-actions">
                    <a class="cancel" href="ruangan.php">Batal</a>
                    <button class="submit" type="submit">Simpan</button>
                </div>
            </form>
        </main>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>
</body>
</html>