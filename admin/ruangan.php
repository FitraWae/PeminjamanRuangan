<?php
// Kelola data ruangan
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $respon = Ruangan::tambah(
            trim($_POST['nama']),
            (int) $_POST['kapasitas'],
            trim($_POST['fasilitas']) ?: null
        );
        $pesan = $respon->message;

    } elseif ($aksi === 'hapus') {
        $idruangan = (int) $_POST['idruangan'];
        $dataRespon = Ruangan::get_by_id($idruangan);

        if ($dataRespon->status && !empty($dataRespon->data)) {
            $d = $dataRespon->data[0];
            $ruangan = new Ruangan((int) $d['idruangan'], $d['namaruangan'], (int) $d['kapasitas'], $d['fasilitas'], $d['statusruangan']);
            $respon = $ruangan->hapus();
            $pesan = $respon->message;
        }

    } elseif ($aksi === 'ubah_status') {
        $idruangan = (int) $_POST['idruangan'];
        $dataRespon = Ruangan::get_by_id($idruangan);

        if ($dataRespon->status && !empty($dataRespon->data)) {
            $d = $dataRespon->data[0];
            $ruangan = new Ruangan((int) $d['idruangan'], $d['namaruangan'], (int) $d['kapasitas'], $d['fasilitas'], $d['statusruangan']);
            $respon = $ruangan->set_status($_POST['status_baru']);
            $pesan = $respon->message;
        }
    }
}

$daftarRuangan = Ruangan::get_all();
$ruanganList = $daftarRuangan->status ? $daftarRuangan->data : [];

$kapasitasList = array_values(array_unique(array_map(static fn($r) => (int) $r['kapasitas'], $ruanganList)));
sort($kapasitasList);

$total    = count($ruanganList);
$tersedia = count(array_filter($ruanganList, static fn($r) => strtolower((string) $r['statusruangan']) === 'tersedia'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Ruang - Admin</title>
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
                    <div class="ux-kicker">Inventaris Sarpras</div>
                    <h1>Kelola Ruangan &amp; Fasilitas</h1>
                    <p>Kelola data master ruangan, kapasitas, fasilitas, dan status operasional secara terpusat.</p>
                </div>
                <div class="ux-hero-actions">
                    <button class="ux-outline" type="button" onclick="document.getElementById('add-room-modal').classList.add('open')">＋ Tambah Ruangan</button>
                </div>
            </section>

            <div class="ux-stats">
                <div class="ux-stat"><span class="ux-stat-icon">▦</span><div class="ux-stat-label">TOTAL MASTER RUANGAN</div><div class="ux-stat-value"><?= $total ?></div><div class="ux-stat-meta">Data inventaris terdaftar</div></div>
                <div class="ux-stat"><span class="ux-stat-icon">✓</span><div class="ux-stat-label">TERSEDIA</div><div class="ux-stat-value"><?= $tersedia ?></div><div class="ux-stat-meta">Siap digunakan untuk reservasi</div></div>
                <div class="ux-stat"><span class="ux-stat-icon">!</span><div class="ux-stat-label">TIDAK TERSEDIA</div><div class="ux-stat-value"><?= $total - $tersedia ?></div><div class="ux-stat-meta">Perlu diperiksa / ditutup</div></div>
                <div class="ux-stat"><span class="ux-stat-icon">◉</span><div class="ux-stat-label">KAPASITAS</div><div class="ux-stat-value"><?= array_sum(array_map(static fn($r) => (int) $r['kapasitas'], $ruanganList)) ?></div><div class="ux-stat-meta">Total daya tampung orang</div></div>
            </div>

            <?php if ($pesan !== ''): ?>
                <p class="message"><?= h($pesan) ?></p>
            <?php endif; ?>

            <div class="toolbar cols-reset">
                <input id="room-search" type="search" placeholder="Cari nama sarana dan prasarana ..." aria-label="Cari ruangan">
                <select id="capacity-filter" aria-label="Filter kapasitas">
                    <option value="">Semua kapasitas</option>
                    <?php foreach ($kapasitasList as $kapasitas): ?>
                        <option value="<?= $kapasitas ?>"><?= $kapasitas ?> orang</option>
                    <?php endforeach; ?>
                </select>
                <button class="ux-reset" type="button" id="room-reset">↺ Reset Filter</button>
            </div>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title">Daftar Ruangan <span class="count"><?= $total ?> Total</span></div>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Ruangan</th><th>Kapasitas</th><th>Fasilitas</th><th>Status Operasional</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="room-rows">
                            <?php if ($ruanganList): foreach ($ruanganList as $r):
                                $facilities = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string) ($r['fasilitas'] ?? '')))));
                                $isAvailable = strtolower((string) $r['statusruangan']) === 'tersedia';
                            ?>
                                <tr class="room-row"
                                    data-search="<?= h(mb_strtolower($r['namaruangan'] . ' ' . ($r['fasilitas'] ?? ''))) ?>"
                                    data-capacity="<?= (int) $r['kapasitas'] ?>">
                                    <td><span class="ux-room-name"><?= h($r['namaruangan']) ?></span><span class="ux-room-sub">ID Ruangan #<?= (int) $r['idruangan'] ?></span></td>
                                    <td><strong><?= (int) $r['kapasitas'] ?></strong> orang</td>
                                    <td>
                                        <div class="ux-facilities">
                                            <?php if ($facilities): foreach ($facilities as $fac): ?>
                                                <span class="ux-facility"><?= h($fac) ?></span>
                                            <?php endforeach; else: ?>
                                                <span class="ux-facility">Belum diatur</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span class="status-pill <?= $isAvailable ? 'available' : 'unavailable' ?>">● <?= h(ucfirst($r['statusruangan'])) ?></span></td>
                                    <td>
                                        <div class="ux-actions">
                                            <a class="ux-icon-btn" href="edit_ruangan.php?id=<?= (int) $r['idruangan'] ?>" title="Edit ruangan">✎</a>

                                            <form class="inline" method="post" action="ruangan.php">
                                                <input type="hidden" name="aksi" value="ubah_status">
                                                <input type="hidden" name="idruangan" value="<?= (int) $r['idruangan'] ?>">
                                                <input type="hidden" name="status_baru" value="<?= $r['statusruangan'] === 'tersedia' ? 'tidak tersedia' : 'tersedia' ?>">
                                                <button class="ux-icon-btn warn" type="submit" title="Ubah status">↻</button>
                                            </form>

                                            <form class="inline" method="post" action="ruangan.php" onsubmit="return confirm('Yakin hapus ruangan ini?');">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="idruangan" value="<?= (int) $r['idruangan'] ?>">
                                                <button class="ux-icon-btn danger" type="submit" title="Hapus">⌫</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td class="empty" colspan="5">Belum ada data ruangan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer"><span id="room-count">Menampilkan <?= $total ?> ruangan</span></div>
            </section>

            <div class="modal-backdrop" id="add-room-modal" role="dialog" aria-modal="true" aria-labelledby="add-room-title">
                <div class="ux-modal">
                    <div class="ux-modal-head">
                        <div>
                            <div class="ux-modal-title" id="add-room-title">Tambah Ruangan Baru</div>
                            <div class="ux-modal-sub">Lengkapi informasi dasar ruangan sebelum dibuka untuk proses reservasi.</div>
                        </div>
                        <button class="ux-close" type="button" data-close="add-room-modal" aria-label="Tutup">×</button>
                    </div>
                    <form method="post" action="ruangan.php">
                        <input type="hidden" name="aksi" value="tambah">
                        <div class="ux-modal-body">
                            <div class="form-grid-ux">
                                <div class="form-group"><label for="nama">Nama Ruangan Akademik *</label><input type="text" id="nama" name="nama" placeholder="Contoh: Lab Multimedia" required></div>
                                <div class="form-group"><label for="kapasitas">Kapasitas Maksimal (Orang) *</label><input type="number" id="kapasitas" name="kapasitas" min="1" placeholder="Jumlah orang" required></div>
                                <div class="form-group full"><label for="fasilitas">Fasilitas Ruangan</label><input type="text" id="fasilitas" name="fasilitas" placeholder="Contoh: AC, Proyektor, Wi-Fi, Sound System"><div class="form-help">Pisahkan beberapa fasilitas dengan koma.</div></div>
                            </div>
                        </div>
                        <div class="ux-modal-foot">
                            <button class="ux-btn secondary" type="button" data-close="add-room-modal">Batal</button>
                            <button class="ux-btn primary" type="submit">Simpan Ruangan</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>

    <script>
        const roomSearch = document.getElementById('room-search');
        const capacityFilter = document.getElementById('capacity-filter');
        const roomRows = [...document.querySelectorAll('.room-row')];

        function filterRooms() {
            const query = roomSearch.value.trim().toLocaleLowerCase('id');
            const capacity = capacityFilter.value;
            let shown = 0;
            roomRows.forEach((row) => {
                const match = row.dataset.search.includes(query) && (capacity === '' || row.dataset.capacity === capacity);
                row.hidden = !match;
                if (match) shown++;
            });
            document.getElementById('room-count').textContent = `Menampilkan ${shown} ruangan`;
        }
        roomSearch.addEventListener('input', filterRooms);
        capacityFilter.addEventListener('change', filterRooms);
        document.getElementById('room-reset').addEventListener('click', () => { roomSearch.value = ''; capacityFilter.value = ''; filterRooms(); });

        const addModal = document.getElementById('add-room-modal');
        document.querySelectorAll('[data-close]').forEach((btn) => btn.addEventListener('click', () => document.getElementById(btn.dataset.close)?.classList.remove('open')));
        addModal.addEventListener('click', (e) => { if (e.target === addModal) addModal.classList.remove('open'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') addModal.classList.remove('open'); });
    </script>
</body>
</html>