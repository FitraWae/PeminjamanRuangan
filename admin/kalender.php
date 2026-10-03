<?php
// Kalender peminjaman ruangan
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan) || !checkdate((int) substr($bulan, 5, 2), 1, (int) substr($bulan, 0, 4))) {
    $bulan = date('Y-m');
}
$awal  = $bulan . '-01';
$akhir = date('Y-m-d', strtotime($awal . ' +1 month'));

$db  = new DBconnection();
$sql = "SELECT dp.tglpinjam, dp.jammulai, dp.jamselesai, dp.keperluan, r.namaruangan, u.namauser, p.kodetransaksi, p.statuspeminjaman
FROM detail_peminjaman dp
JOIN peminjaman p ON p.idpeminjaman=dp.idpeminjaman
JOIN users u ON u.iduser=p.iduser
JOIN ruangan r ON r.idruangan=dp.idruangan
WHERE dp.tglpinjam >= $1 AND dp.tglpinjam < $2 AND p.statuspeminjaman IN ('approved','completed')
ORDER BY dp.tglpinjam, dp.jammulai";
$res = $db->send_query($sql, [$awal, $akhir]);
$db->close_connection();
$events = $res->status ? $res->data : [];

$group = [];
foreach ($events as $e) {
    $group[$e['tglpinjam']][] = $e;
}

$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$namaHari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

$startDow = (int) date('w', strtotime($awal));   // 0 = Minggu
$days     = (int) date('t', strtotime($awal));
$prev     = date('Y-m', strtotime($awal . ' -1 month'));
$next     = date('Y-m', strtotime($awal . ' +1 month'));
$today    = date('Y-m-d');

$prevDays    = (int) date('t', strtotime($awal . ' -1 month'));
$prevNamaBln = $namaBulan[(int) date('n', strtotime($awal . ' -1 month'))];
$nextNamaBln = $namaBulan[(int) date('n', strtotime($awal . ' +1 month'))];
$trailing    = (7 - (($startDow + $days) % 7)) % 7;
$labelBulan  = $namaBulan[(int) substr($bulan, 5, 2)] . ' ' . substr($bulan, 0, 4);
$totalEvent  = count($events);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender Ruangan - Admin</title>
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
                <a href="selesai.php">Tandai Kembali</a>
                <a class="active" aria-current="page" href="kalender.php">Kalender Ruangan</a>
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
                    <div class="ux-kicker">Schedule Monitoring</div>
                    <h1>Kalender Peminjaman Ruangan</h1>
                    <p>Pantau seluruh jadwal peminjaman ruangan yang sudah disetujui atau selesai dalam satu tampilan.</p>
                </div>
            </section>

            <div class="cal-toolbar">
                <a class="arrow" href="?bulan=<?= h($prev) ?>" title="Bulan sebelumnya">‹</a>
                <form method="get" style="margin:0">
                    <input type="month" name="bulan" value="<?= h($bulan) ?>" onchange="this.form.submit()" title="<?= h($labelBulan) ?>">
                </form>
                <a class="arrow" href="?bulan=<?= h($next) ?>" title="Bulan berikutnya">›</a>
                <span class="cal-total">TOTAL PEMINJAMAN <b><?= $totalEvent ?></b>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                </span>
            </div>

            <div class="cal-shell">
                <div class="cal-weekdays">
                    <?php foreach ($namaHari as $i => $n): ?>
                        <div class="<?= $i === 0 ? 'sun' : ($i === 6 ? 'sat' : '') ?>"><?= h($n) ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="cal-grid">
                    <?php for ($i = $startDow - 1; $i >= 0; $i--): $dd = $prevDays - $i; ?>
                        <div class="cal-cell other"><span class="cal-num"><?= $dd ?></span><span class="cal-bln"><?= h($prevNamaBln) ?></span></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $days; $d++):
                        $date  = sprintf('%s-%02d', $bulan, $d);
                        $dow   = ($startDow + $d - 1) % 7;
                        $items = $group[$date] ?? [];
                        $cls   = 'cal-cell';
                        if ($dow === 0) $cls .= ' sun';
                        if ($dow === 6) $cls .= ' sat';
                        if ($items) $cls .= ' has';
                        if ($date === $today) $cls .= ' today';
                    ?>
                        <div class="<?= $cls ?>">
                            <?php if ($items): ?>
                                <div class="cal-head">
                                    <span class="cal-dnum"><?= $d ?></span>
                                    <span class="cal-dname"><?= h($namaHari[$dow]) ?></span>
                                    <span class="cal-count"><?= count($items) ?> Sesi</span>
                                </div>
                                <?php foreach ($items as $e): $done = $e['statuspeminjaman'] === 'completed'; ?>
                                    <div class="cal-event <?= $done ? 'completed' : '' ?>">
                                        <div class="cal-row">
                                            <span>◷ <?= h($e['jammulai'] ? substr($e['jammulai'], 0, 5) : '') ?> - <?= h(substr($e['jamselesai'], 0, 5)) ?></span>
                                            <span class="cal-badge"><?= $done ? 'SELESAI' : 'DISETUJUI' ?></span>
                                        </div>
                                        <span class="cal-room"><?= h($e['namaruangan']) ?></span>
                                        <span class="cal-who"><?= h($e['namauser']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="cal-num"><?= $d ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>

                    <?php for ($i = 1; $i <= $trailing; $i++): ?>
                        <div class="cal-cell other"><span class="cal-num"><?= $i ?></span><span class="cal-bln"><?= h($nextNamaBln) ?></span></div>
                    <?php endfor; ?>
                </div>
            </div>

            <section class="card cal-detail">
                <h3>Rincian Jadwal Bulan Ini</h3>
                <?php if (!$events): ?>
                    <p style="font-size:12px;color:var(--muted)">Belum ada peminjaman yang disetujui pada bulan ini.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($events as $e): ?>
                            <li>
                                <b><?= h($e['tglpinjam']) ?>, <?= h(substr($e['jammulai'], 0, 5)) ?>–<?= h(substr($e['jamselesai'], 0, 5)) ?></b>
                                — <?= h($e['namaruangan']) ?> · Pengaju: <?= h($e['namauser']) ?> · <?= h($e['kodetransaksi']) ?> (<?= h($e['statuspeminjaman']) ?>)<br>
                                <?= h($e['keperluan']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </main>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>
</body>
</html>