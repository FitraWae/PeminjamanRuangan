<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$db = new DBconnection();
$sql = "SELECT p.idpeminjaman, p.kodetransaksi, p.tglpengajuan, p.statuspeminjaman, u.namauser,
               dp.tglpinjam, dp.jammulai, dp.jamselesai, dp.keperluan, r.namaruangan
        FROM peminjaman p JOIN users u ON u.iduser=p.iduser
        LEFT JOIN detail_peminjaman dp ON dp.idpeminjaman=p.idpeminjaman
        LEFT JOIN ruangan r ON r.idruangan=dp.idruangan
        ORDER BY p.tglpengajuan DESC, dp.tglpinjam DESC, dp.jammulai";
$res = $db->send_query($sql);
$db->close_connection();
$rows = $res->status ? $res->data : [];

function status_label($s)
{
    return [
        'pending'   => 'Menunggu',
        'approved'  => 'Disetujui',
        'rejected'  => 'Ditolak',
        'completed' => 'Selesai',
    ][$s] ?? $s;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Pengajuan - Admin</title>
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
                <a href="kalender.php">Kalender Ruangan</a>
                <a class="active" aria-current="page" href="riwayat.php">Riwayat Pengajuan</a>
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
                    <div class="ux-kicker">Riwayat Transaksi</div>
                    <h1>Riwayat Semua Pengajuan</h1>
                    <p>Daftar pengajuan seluruh pengguna, lengkap dengan status serta rincian ruangan dan jadwalnya.</p>
                </div>
            </section>

            <?php if (!$res->status): ?>
                <p class="message error">Gagal memuat data: <?= h($res->message) ?></p>
            <?php endif; ?>

            <div class="toolbar cols-3">
                <input id="riwayat-search" type="search" placeholder="Cari kode transaksi, nama pengaju, ruangan, atau keperluan ..." aria-label="Cari pengajuan">
                <select id="riwayat-status" aria-label="Filter status">
                    <option value="">Semua Status</option>
                    <option value="pending">Menunggu</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                    <option value="completed">Selesai</option>
                </select>
                <select id="riwayat-sort" aria-label="Urutkan pengajuan">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                </select>
            </div>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title">Daftar Riwayat Pengajuan <span class="count"><?= count($rows) ?> Total</span></div>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Transaksi</th>
                                <th>Nama Pengaju</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Status</th>
                                <th>Detail</th>
                                <th>Ruangan &amp; Jadwal</th>
                                <th>Keperluan</th>
                            </tr>
                        </thead>
                        <tbody id="riwayat-rows">
                            <?php if ($rows): foreach ($rows as $r): ?>
                                <tr class="riwayat-row"
                                    data-date="<?= h($r['tglpengajuan']) ?>"
                                    data-status="<?= h($r['statuspeminjaman']) ?>"
                                    data-search="<?= h(mb_strtolower($r['kodetransaksi'] . ' ' . $r['namauser'] . ' ' . ($r['namaruangan'] ?? '') . ' ' . ($r['keperluan'] ?? ''))) ?>">
                                    <td><strong><?= h($r['kodetransaksi']) ?></strong></td>
                                    <td><?= h($r['namauser']) ?></td>
                                    <td><?= h($r['tglpengajuan']) ?></td>
                                    <td><span class="badge <?= h($r['statuspeminjaman']) ?>"><?= h(status_label($r['statuspeminjaman'])) ?></span></td>
                                    <td>
                                        <button type="button" class="ux-icon-btn" title="Lihat detail"
                                            data-history-detail='<?= h(json_encode([
                                                'kode'      => $r['kodetransaksi'],
                                                'user'      => $r['namauser'],
                                                'tanggal'   => $r['tglpengajuan'],
                                                'ruangan'   => $r['namaruangan'],
                                                'tglpinjam' => $r['tglpinjam'],
                                                'mulai'     => $r['jammulai'],
                                                'selesai'   => $r['jamselesai'],
                                                'keperluan' => $r['keperluan'],
                                                'status'    => status_label($r['statuspeminjaman']),
                                            ])) ?>'>⌕</button>
                                    </td>
                                    <td>
                                        <?php if ($r['namaruangan']): ?>
                                            <strong><?= h($r['namaruangan']) ?></strong><br>
                                            <?= h($r['tglpinjam']) ?> · <?= h(substr($r['jammulai'], 0, 5)) ?>–<?= h(substr($r['jamselesai'], 0, 5)) ?>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td><?= h($r['keperluan']) ?></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td class="empty" colspan="7">Belum ada data pengajuan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer"><span id="riwayat-count">Menampilkan <?= count($rows) ?> pengajuan</span></div>
            </section>
        </main>

        <div class="modal-backdrop" id="history-modal" role="dialog" aria-modal="true">
            <div class="ux-modal">
                <div class="ux-modal-head">
                    <div>
                        <div class="ux-modal-title">Detail Riwayat Peminjaman</div>
                        <div class="ux-modal-sub">Ringkasan transaksi dan jadwal pemakaian ruangan.</div>
                    </div>
                    <button class="ux-close" type="button" data-close="history-modal" aria-label="Tutup">×</button>
                </div>
                <div class="ux-modal-body"><div class="ux-modal-grid" id="history-grid"></div></div>
                <div class="ux-modal-foot"><button class="ux-btn secondary" type="button" data-close="history-modal">Tutup</button></div>
            </div>
        </div>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>

    <script>
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        /* Cari, filter, urutkan */
        const searchInput = document.getElementById('riwayat-search');
        const statusSelect = document.getElementById('riwayat-status');
        const sortSelect = document.getElementById('riwayat-sort');
        const tbody = document.getElementById('riwayat-rows');
        const allRows = [...document.querySelectorAll('.riwayat-row')];

        function updateRows() {
            const query = searchInput.value.trim().toLocaleLowerCase('id');
            const status = statusSelect.value;
            const visible = allRows.filter((row) => row.dataset.search.includes(query) && (status === '' || row.dataset.status === status));
            visible.sort((a, b) => {
                const diff = new Date(a.dataset.date) - new Date(b.dataset.date);
                return sortSelect.value === 'newest' ? -diff : diff;
            });
            visible.forEach((row) => tbody.append(row));
            allRows.forEach((row) => { row.hidden = !visible.includes(row); });
            document.getElementById('riwayat-count').textContent = `Menampilkan ${visible.length} pengajuan`;
        }
        searchInput.addEventListener('input', updateRows);
        statusSelect.addEventListener('change', updateRows);
        sortSelect.addEventListener('change', updateRows);

        /* Modal detail */
        const historyModal = document.getElementById('history-modal');
        document.querySelectorAll('[data-history-detail]').forEach((b) => b.addEventListener('click', () => {
            const d = JSON.parse(b.dataset.historyDetail);
            document.getElementById('history-grid').innerHTML = [
                ['Kode Transaksi', d.kode],
                ['Pengaju', d.user],
                ['Tanggal Pengajuan', d.tanggal],
                ['Ruangan', d.ruangan || '-'],
                ['Tanggal Pinjam', d.tglpinjam || '-'],
                ['Waktu', d.mulai && d.selesai ? String(d.mulai).slice(0, 5) + '–' + String(d.selesai).slice(0, 5) : '-'],
                ['Status', d.status],
                ['Keperluan', d.keperluan || '-'],
            ].map((x) => `<div class="ux-field"><div class="ux-field-label">${esc(x[0])}</div><div class="ux-field-value">${esc(x[1])}</div></div>`).join('');
            historyModal.classList.add('open');
        }));
        document.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => document.getElementById(b.dataset.close)?.classList.remove('open')));
        historyModal.addEventListener('click', (e) => { if (e.target === historyModal) historyModal.classList.remove('open'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') historyModal.classList.remove('open'); });
    </script>
</body>
</html>