<?php
// Pengembalian peminjaman ruangan
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idpeminjaman = (int) $_POST['idpeminjaman'];
    $dataRespon = Peminjaman::get_by_id($idpeminjaman);

    if ($dataRespon->status && !empty($dataRespon->data)) {
        $d = $dataRespon->data[0];
        $peminjaman = new Peminjaman((int) $d['idpeminjaman'], (int) $d['iduser'], $d['kodetransaksi'], $d['statuspeminjaman']);
        $respon = $peminjaman->tandai_selesai();
        $pesan = $respon->message;
    }
}

$daftarApproved = Peminjaman::get_approved();
$rows = $daftarApproved->status ? $daftarApproved->data : [];
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tandai Pengembalian - Admin</title>
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
                <a href="ruangan.php">Kelola Ruang</a>
                <a href="approval.php">Persetujuan Pinjam</a>
                <a class="active" aria-current="page" href="selesai.php">Tandai Kembali</a>
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
                    <div class="ux-kicker">Operational Return</div>
                    <h1>Pengembalian Peminjaman Ruangan</h1>
                    <p>Pantau peminjaman yang sedang berjalan dan tandai ruangan sebagai selesai setelah digunakan.</p>
                </div>
            </section>

            <?php if ($pesan !== ''): ?>
                <p class="notice"><?= h($pesan) ?></p>
            <?php endif; ?>

            <div class="toolbar">
                <input id="selesai-search" type="search" placeholder="Cari kode transaksi atau ID pengguna ..." aria-label="Cari peminjaman">
                <select id="selesai-sort" aria-label="Urutkan peminjaman">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                </select>
            </div>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title">Peminjaman Sedang Berjalan <span class="count"><?= $total ?> Total</span></div>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Kode Transaksi</th><th>ID User</th><th>Tanggal Pengajuan</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="selesai-rows">
                            <?php if ($rows): foreach ($rows as $p): ?>
                                <tr class="selesai-row"
                                    data-date="<?= h($p['tglpengajuan']) ?>"
                                    data-search="<?= h(mb_strtolower($p['kodetransaksi'] . ' ' . $p['iduser'])) ?>">
                                    <td><strong><?= h($p['kodetransaksi']) ?></strong></td>
                                    <td><?= h($p['iduser']) ?></td>
                                    <td><?= h($p['tglpengajuan']) ?></td>
                                    <td>
                                        <form class="inline" method="post" action="selesai.php" onsubmit="return confirm('Tandai peminjaman ini sudah dikembalikan?');">
                                            <input type="hidden" name="idpeminjaman" value="<?= (int) $p['idpeminjaman'] ?>">
                                            <button class="button" type="submit">✓ Tandai Selesai</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td class="empty" colspan="4">Tidak ada peminjaman yang sedang berjalan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer"><span id="selesai-count">Menampilkan <?= $total ?> peminjaman</span></div>
            </section>
        </main>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>

    <script>
        const searchInput = document.getElementById('selesai-search');
        const sortSelect = document.getElementById('selesai-sort');
        const tbody = document.getElementById('selesai-rows');
        const allRows = [...document.querySelectorAll('.selesai-row')];

        function updateRows() {
            const query = searchInput.value.trim().toLocaleLowerCase('id');
            const visible = allRows.filter((row) => row.dataset.search.includes(query));
            visible.sort((a, b) => {
                const diff = new Date(a.dataset.date) - new Date(b.dataset.date);
                return sortSelect.value === 'newest' ? -diff : diff;
            });
            visible.forEach((row) => tbody.append(row));
            allRows.forEach((row) => { row.hidden = !visible.includes(row); });
            document.getElementById('selesai-count').textContent = `Menampilkan ${visible.length} peminjaman`;
        }
        searchInput.addEventListener('input', updateRows);
        sortSelect.addEventListener('change', updateRows);
    </script>
</body>
</html>