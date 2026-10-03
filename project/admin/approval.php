<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('admin');

function h($v)
{
    return htmlspecialchars((string) ($v ?? '-'), ENT_QUOTES, 'UTF-8');
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idpeminjaman = (int) $_POST['idpeminjaman'];
    $aksi = $_POST['aksi'] ?? '';

    $dataRespon = Peminjaman::get_by_id($idpeminjaman);
    if ($dataRespon->status && !empty($dataRespon->data)) {
        $d = $dataRespon->data[0];
        $peminjaman = new Peminjaman((int) $d['idpeminjaman'], (int) $d['iduser'], $d['kodetransaksi'], $d['statuspeminjaman']);

        if ($aksi === 'approve') {
            $respon = $peminjaman->approve();
            $pesan = $respon->message;
        } elseif ($aksi === 'reject') {
            $respon = $peminjaman->reject();
            $pesan = $respon->message;
        }
    }
}

$daftarPending = Peminjaman::get_pending();
$total = $daftarPending->status ? count($daftarPending->data) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Persetujuan Peminjaman Ruangan - Admin</title>
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
                <a class="active" aria-current="page" href="approval.php">Persetujuan Pinjam</a>
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
                    <div class="ux-kicker">Workflow Approval</div>
                    <h1>Persetujuan Peminjaman Ruangan</h1>
                    <p>Tinjau detail pengajuan sebelum menentukan persetujuan atau penolakan. Gunakan pencarian untuk menemukan transaksi tertentu.</p>
                </div>
            </section>

            <?php if ($pesan !== ''): ?><p class="notice"><?= h($pesan) ?></p><?php endif; ?>

            <div class="toolbar">
                <input id="approval-search" type="search" placeholder="Cari kode transaksi, ID pengguna, nama ruangan, atau agenda..." aria-label="Cari pengajuan">
                <select id="approval-sort" aria-label="Urutkan pengajuan">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                </select>
            </div>

            <section class="panel">
                <div class="panel-head">
                    <div class="panel-title">Daftar Pengajuan Masuk <span class="count"><?= $total ?> Total</span></div>
                </div>

                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Kode Transaksi</th><th>ID User</th><th>Tanggal Pengajuan</th><th>Detail</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="approval-rows">
                            <?php if ($daftarPending->status && $daftarPending->data): foreach ($daftarPending->data as $p):
                                $detailRespon = DetailPeminjaman::get_by_peminjaman((int) $p['idpeminjaman']);
                                $details = $detailRespon->status ? $detailRespon->data : [];
                                $searchText = $p['kodetransaksi'] . ' ' . $p['iduser'];
                                foreach ($details as $dt) {
                                    $searchText .= ' ' . $dt['namaruangan'] . ' ' . $dt['keperluan'];
                                }
                            ?>
                                <tr class="approval-row" data-date="<?= h($p['tglpengajuan']) ?>" data-search="<?= h(mb_strtolower($searchText)) ?>">
                                    <td><strong><?= h($p['kodetransaksi']) ?></strong></td>
                                    <td><?= (int) $p['iduser'] ?></td>
                                    <td><?= h($p['tglpengajuan']) ?></td>
                                    <td>
                                        <?php if ($details): foreach ($details as $dt): ?>
                                            <div>
                                                <strong><?= h($dt['namaruangan']) ?></strong><br>
                                                <?= h($dt['tglpinjam']) ?> · <?= h(substr($dt['jammulai'], 0, 5)) ?>–<?= h(substr($dt['jamselesai'], 0, 5)) ?><br>
                                                <?= h($dt['keperluan']) ?>
                                            </div>
                                        <?php endforeach; else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="ux-icon-btn" title="Lihat detail"
                                                data-approval-detail='<?= h(json_encode($details)) ?>'
                                                data-code='<?= h($p['kodetransaksi']) ?>'
                                                data-user='<?= (int) $p['iduser'] ?>'
                                                data-date='<?= h($p['tglpengajuan']) ?>'>⌕</button>
                                            <form class="inline" method="post" action="approval.php">
                                                <input type="hidden" name="idpeminjaman" value="<?= (int) $p['idpeminjaman'] ?>">
                                                <input type="hidden" name="aksi" value="approve">
                                                <button class="button" type="submit">Setujui</button>
                                            </form>
                                            <form class="inline" method="post" action="approval.php">
                                                <input type="hidden" name="idpeminjaman" value="<?= (int) $p['idpeminjaman'] ?>">
                                                <input type="hidden" name="aksi" value="reject">
                                                <button class="button reject" type="submit">Tolak</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td class="empty" colspan="5">Tidak ada pengajuan yang menunggu persetujuan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer"><span id="approval-count">Menampilkan <?= $total ?> pengajuan</span></div>
            </section>
        </main>

        <div class="modal-backdrop" id="approval-modal" role="dialog" aria-modal="true">
            <div class="ux-modal">
                <div class="ux-modal-head">
                    <div>
                        <div class="ux-modal-title">Detail Pengajuan Peminjaman</div>
                        <div class="ux-modal-sub" id="approval-modal-sub">Informasi lengkap transaksi.</div>
                    </div>
                    <button class="ux-close" type="button" data-close="approval-modal" aria-label="Tutup">×</button>
                </div>
                <div class="ux-modal-body">
                    <div class="ux-modal-grid" id="approval-summary"></div>
                    <div class="ux-detail-list" id="approval-details"></div>
                </div>
                <div class="ux-modal-foot"><button class="ux-btn secondary" type="button" data-close="approval-modal">Tutup</button></div>
            </div>
        </div>

        <footer class="footer">© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>

    <script>
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        /* Cari & urutkan */
        const approvalSearch = document.getElementById('approval-search');
        const approvalSort = document.getElementById('approval-sort');
        const approvalBody = document.getElementById('approval-rows');
        const approvalRows = [...document.querySelectorAll('.approval-row')];

        function updateApprovals() {
            const query = approvalSearch.value.trim().toLocaleLowerCase('id');
            const visible = approvalRows.filter((row) => row.dataset.search.includes(query));
            visible.sort((a, b) => {
                const diff = new Date(a.dataset.date) - new Date(b.dataset.date);
                return approvalSort.value === 'newest' ? -diff : diff;
            });
            visible.forEach((row) => approvalBody.append(row));
            approvalRows.forEach((row) => { row.hidden = !visible.includes(row); });
            document.getElementById('approval-count').textContent = `Menampilkan ${visible.length} pengajuan`;
        }
        approvalSearch.addEventListener('input', updateApprovals);
        approvalSort.addEventListener('change', updateApprovals);

        /* Modal detail */
        const approvalModal = document.getElementById('approval-modal');
        document.querySelectorAll('[data-approval-detail]').forEach((btn) => btn.addEventListener('click', () => {
            const details = JSON.parse(btn.dataset.approvalDetail || '[]');
            document.getElementById('approval-modal-sub').textContent = `${btn.dataset.code} · Pengguna #${btn.dataset.user} · ${btn.dataset.date}`;
            document.getElementById('approval-summary').innerHTML =
                `<div class="ux-field"><div class="ux-field-label">Kode Transaksi</div><div class="ux-field-value">${esc(btn.dataset.code)}</div></div>` +
                `<div class="ux-field"><div class="ux-field-label">ID Pengguna</div><div class="ux-field-value">#${esc(btn.dataset.user)}</div></div>`;
            document.getElementById('approval-details').innerHTML = details.length
                ? details.map((d) => `<div class="ux-detail-item"><strong>${esc(d.namaruangan)}</strong><small>${esc(d.tglpinjam)} · ${esc(String(d.jammulai).slice(0,5))}–${esc(String(d.jamselesai).slice(0,5))}</small><small>${esc(d.keperluan)}</small></div>`).join('')
                : '<div class="ux-detail-item">Tidak ada detail ruangan.</div>';
            approvalModal.classList.add('open');
        }));
        document.querySelectorAll('[data-close]').forEach((btn) => btn.addEventListener('click', () => document.getElementById(btn.dataset.close)?.classList.remove('open')));
        approvalModal.addEventListener('click', (e) => { if (e.target === approvalModal) approvalModal.classList.remove('open'); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') approvalModal.classList.remove('open'); });
    </script>
</body>
</html>