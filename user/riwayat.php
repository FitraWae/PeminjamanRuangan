<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('pelanggan');

$daftarPeminjaman = Peminjaman::get_by_user((int) $_SESSION['iduser']);
$peminjamanList = $daftarPeminjaman->status ? $daftarPeminjaman->data : [];

function hriwayat($value): string {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

function label_status_riwayat(string $status): string {
    return [
        'pending' => 'Diproses',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'completed' => 'Selesai',
    ][$status] ?? ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Peminjaman - Sistem Informasi Sarana dan Prasarana</title>
    <style>
        :root {
            color-scheme: light;
            --page: #eaf0ff;
            --ink: #111827;
            --muted: #737b8d;
            --blue: #326cf5;
            --blue-dark: #0756d9;
            --line: #e4e8f2;
            --field: #cdd5e8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--page); color: var(--ink); font-family: Inter, sans-serif; }
        .page { width: min(100%, 1440px); min-height: 100vh; margin: 0 auto; padding: 28px 4.2% 20px; display: flex; flex-direction: column; }
        .topbar { min-height: 48px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .brand-icon { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 6px; display: grid; place-items: center; background: #23469c; color: white; }
        .brand-icon svg { width: 18px; height: 18px; }
        .brand-name { font-size: 12px; line-height: 1.3; font-weight: 700; }
        .brand-caption { font-size: 9px; line-height: 1.35; color: #4b5563; }
        .tabs { display: flex; align-items: center; padding: 4px; gap: 2px; border-radius: 8px; background: #fff; }
        .tabs a { padding: 8px 10px; border-radius: 6px; color: #376cf0; font-size: 11px; text-decoration: none; white-space: nowrap; }
        .tabs a.active { color: white; background: var(--blue); }
        .account { justify-self: end; display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--ink); }
        .account-copy { text-align: right; font-size: 9px; line-height: 1.3; }
        .account-copy strong { display: block; max-width: 105px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .account-icon { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; background: #bd4d00; color: white; }
        .account-icon svg { width: 15px; height: 15px; }
        main { margin-top: 72px; }
        .heading { margin-bottom: 30px; }
        h1 { margin: 0 0 8px; font-size: clamp(22px, 2.2vw, 30px); line-height: 1.2; letter-spacing: 0; }
        .intro { max-width: 560px; margin: 0; color: var(--muted); font-size: 13px; line-height: 1.55; }
        .toolbar { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, 32%); gap: 8px; margin-bottom: 18px; }
        .searchbox, .selectbox { position: relative; min-width: 0; }
        .searchbox svg { position: absolute; top: 50%; right: 14px; width: 16px; height: 16px; transform: translateY(-50%); color: #4b5563; pointer-events: none; }
        .toolbar input, .toolbar select { width: 100%; height: 40px; border: 1px solid white; border-radius: 7px; background: rgba(255,255,255,.38); color: #4b5563; font: inherit; font-size: 11px; outline: none; }
        .toolbar input { padding: 0 42px 0 14px; }
        .toolbar select { padding: 0 30px 0 14px; }
        .toolbar input:focus, .toolbar select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(50,108,245,.12); }
        .table-shell { overflow: hidden; border-radius: 9px; background: white; }
        .table-heading { min-height: 48px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 16px; color: white; background: linear-gradient(105deg, #0756dc, #3970f5); }
        .table-title { display: flex; align-items: center; gap: 9px; font-size: 14px; font-weight: 500; }
        .list-icon { width: 24px; height: 24px; display: grid; place-items: center; border-radius: 6px; background: rgba(255,255,255,.2); }
        .list-icon svg { width: 15px; height: 15px; }
        .total { padding: 3px 8px; border-radius: 999px; background: rgba(255,255,255,.2); font-size: 10px; font-weight: 400; }
        .sort-control { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,.86); font-size: 10px; }
        .sort-control select { border: 1px solid rgba(255,255,255,.25); border-radius: 5px; padding: 5px 7px; background: rgba(255,255,255,.12); color: white; font: inherit; outline: none; }
        .sort-control option { color: var(--ink); background: white; }
        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { background: #cdd5e8; }
        th { height: 44px; padding: 0 16px; color: #283142; font-size: 11px; font-weight: 600; text-align: left; }
        th:first-child { width: 21%; }
        th:nth-child(2) { width: 23%; }
        th:nth-child(3) { width: 21%; }
        th:nth-child(4) { width: 35%; }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f2f7; color: #263042; font-size: 11px; line-height: 1.5; vertical-align: middle; overflow-wrap: anywhere; }
        tbody tr:last-child td { border-bottom: 0; }
        .code { font-weight: 500; }
        .detail-item + .detail-item { margin-top: 10px; }
        .detail-room { font-weight: 600; }
        .detail-meta { color: #5f6879; }
        .status { display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 999px; font-size: 9px; font-weight: 600; white-space: nowrap; }
        .status::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ''; }
        .status-pending { color: #bd5900; background: #fff0e4; border: 1px solid #f4bd92; }
        .status-approved { color: #1764d8; background: #e8f0ff; }
        .status-rejected { color: #a83e24; background: #f9dfd4; }
        .status-completed { color: #68738c; background: #e8ebf4; }
        .empty { padding: 32px 16px; color: var(--muted); text-align: center; }
        .table-footer { min-height: 44px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 7px 12px 7px 16px; background: #cdd5e8; color: #4d5668; font-size: 10px; }
        .pagination { display: flex; gap: 5px; }
        .pagination button { width: 28px; height: 28px; border: 0; border-radius: 6px; background: white; color: #455067; font: inherit; cursor: pointer; }
        .pagination button:disabled { opacity: .45; cursor: default; }
        footer { margin-top: auto; padding-top: 48px; color: #68738a; font-size: 9px; }
        @media (min-width: 1200px) { .page { padding-right: 5.6%; padding-left: 5.6%; } main { margin-top: 84px; } }
        @media (max-width: 700px) {
            .page { padding: 16px 16px 18px; }
            .topbar { grid-template-columns: 1fr auto; gap: 12px; }
            .tabs { grid-row: 2; grid-column: 1 / -1; justify-self: stretch; justify-content: center; }
            .tabs a { padding: 8px 9px; font-size: 10px; }
            main { margin-top: 44px; }
            .heading { margin-bottom: 22px; }
            .toolbar { grid-template-columns: 1fr; }
            .table-heading { align-items: flex-start; flex-direction: column; }
            .sort-control { align-self: flex-end; }
            table { min-width: 680px; }
            footer { padding-top: 34px; }
        }
        @media (prefers-reduced-motion: no-preference) {
            main { animation: rise-in .38s ease-out both; }
            @keyframes rise-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="topbar">
            <a class="brand" href="ajukan.php" aria-label="Sistem Informasi Sarana dan Prasarana">
                <span class="brand-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 4l9 6.5V20H3z"/><path d="M7 12h10M7 15h10M9 8h6"/></svg></span>
                <span><span class="brand-name">Layanan Peminjaman Ruang</span><br><span class="brand-caption">Sistem Informasi Sarana dan Prasarana</span></span>
            </a>
            <nav class="tabs" aria-label="Navigasi utama">
                <a href="ajukan.php">Formulir</a>
                <a href="kalender.php">Kalender Peminjaman</a>
                <a class="active" href="riwayat.php" aria-current="page">Riwayat Peminjaman</a>
            </nav>
            <a class="account" href="../logout.php" title="Keluar dari akun">
                <span class="account-copy"><strong><?= hriwayat($_SESSION['nama'] ?? 'Pelanggan') ?></strong>Keluar</span>
                <span class="account-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="3"/><path d="M5.5 19c.8-3 3.1-4.5 6.5-4.5s5.7 1.5 6.5 4.5"/></svg></span>
            </a>
        </header>

        <main>
            <section class="heading">
                <h1>Riwayat Peminjaman Saya</h1>
                <p class="intro">Pantau status validasi pengajuan peminjaman ruangan, unduh surat izin resmi, dan lihat aktivitas kegiatan Anda.</p>
            </section>

            <section class="toolbar" aria-label="Pencarian dan filter riwayat">
                <label class="searchbox">
                    <input id="history-search" type="search" placeholder="Cari Kode Transaksi, Nama Ruangan, atau Agenda..." aria-label="Cari riwayat peminjaman">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                </label>
                <label class="selectbox">
                    <select id="status-filter" aria-label="Filter berdasarkan status">
                        <option value="all">Semua Status (<?= count($peminjamanList) ?>)</option>
                        <option value="pending">Diproses</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                        <option value="completed">Selesai</option>
                    </select>
                </label>
            </section>

            <section class="table-shell" aria-label="Daftar pengajuan peminjaman">
                <div class="table-heading">
                    <div class="table-title">
                        <span class="list-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 6h10M9 12h10M9 18h10M5 6h.01M5 12h.01M5 18h.01"/></svg></span>
                        Daftar Pengajuan Masuk <span class="total" id="history-total"><?= count($peminjamanList) ?> Total</span>
                    </div>
                    <label class="sort-control">Urutkan:
                        <select id="sort-order" aria-label="Urutkan riwayat">
                            <option value="newest">Terbaru ↓</option>
                            <option value="oldest">Terlama ↑</option>
                        </select>
                    </label>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>Kode Transaksi</th><th>Tanggal Pengajuan</th><th>Status</th><th>Detail</th></tr></thead>
                        <tbody id="history-rows">
                            <?php if ($peminjamanList): foreach ($peminjamanList as $p):
                                $detailRespon = DetailPeminjaman::get_by_peminjaman((int) $p['idpeminjaman']);
                                $detailList = $detailRespon->status ? $detailRespon->data : [];
                                $searchText = $p['kodetransaksi'] . ' ' . $p['statuspeminjaman'];
                                foreach ($detailList as $dt) {
                                    $searchText .= ' ' . $dt['namaruangan'] . ' ' . $dt['keperluan'];
                                }
                                $statusClass = in_array($p['statuspeminjaman'], ['pending', 'approved', 'rejected', 'completed'], true) ? $p['statuspeminjaman'] : 'completed';
                            ?>
                                <tr class="history-row" data-status="<?= hriwayat($p['statuspeminjaman']) ?>" data-date="<?= hriwayat($p['tglpengajuan']) ?>" data-search="<?= hriwayat($searchText) ?>">
                                    <td class="code"><?= hriwayat($p['kodetransaksi']) ?></td>
                                    <td><?= hriwayat(date('Y-m-d', strtotime($p['tglpengajuan']))) ?><br><span class="detail-meta"><?= hriwayat(date('H:i:s', strtotime($p['tglpengajuan']))) ?></span></td>
                                    <td><span class="status status-<?= hriwayat($statusClass) ?>"><?= hriwayat(label_status_riwayat($p['statuspeminjaman'])) ?></span></td>
                                    <td>
                                        <?php if ($detailList): foreach ($detailList as $dt): ?>
                                            <div class="detail-item"><span class="detail-room"><?= hriwayat($dt['namaruangan']) ?></span><br><span class="detail-meta"><?= hriwayat($dt['tglpinjam']) ?> · <?= hriwayat(substr($dt['jammulai'], 0, 5)) ?>–<?= hriwayat(substr($dt['jamselesai'], 0, 5)) ?></span><br><?= hriwayat($dt['keperluan']) ?></div>
                                        <?php endforeach; else: ?>- <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr class="no-results"><td class="empty" colspan="4">Belum ada riwayat peminjaman.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <span id="results-count">Menampilkan 0 dari 0 riwayat peminjaman</span>
                    <div class="pagination" aria-label="Navigasi halaman">
                        <button id="previous-page" type="button" aria-label="Halaman sebelumnya" disabled>‹</button>
                        <button id="next-page" type="button" aria-label="Halaman berikutnya" disabled>›</button>
                    </div>
                </div>
            </section>
        </main>

        <footer>© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>
    <script>
        const rows = [...document.querySelectorAll('.history-row')];
        const searchInput = document.getElementById('history-search');
        const statusFilter = document.getElementById('status-filter');
        const sortOrder = document.getElementById('sort-order');
        const pageSize = 6;
        let currentPage = 0;

        function updateHistory() {
            const query = searchInput.value.trim().toLocaleLowerCase('id');
            const status = statusFilter.value;
            const filtered = rows.filter((row) => {
                const matchesSearch = row.dataset.search.toLocaleLowerCase('id').includes(query);
                return matchesSearch && (status === 'all' || row.dataset.status === status);
            });
            filtered.sort((a, b) => {
                const dateDifference = new Date(a.dataset.date) - new Date(b.dataset.date);
                return sortOrder.value === 'newest' ? -dateDifference : dateDifference;
            });

            const tableBody = document.getElementById('history-rows');
            filtered.forEach((row) => tableBody.append(row));
            const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
            currentPage = Math.min(currentPage, pageCount - 1);
            const visibleRows = new Set(filtered.slice(currentPage * pageSize, (currentPage + 1) * pageSize));
            rows.forEach((row) => { row.hidden = !visibleRows.has(row); });

            let emptyRow = document.querySelector('.no-results');
            if (!filtered.length) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'no-results';
                    emptyRow.innerHTML = '<td class="empty" colspan="4">Tidak ada riwayat yang cocok.</td>';
                    document.getElementById('history-rows').append(emptyRow);
                }
                emptyRow.hidden = false;
            } else if (emptyRow) {
                emptyRow.hidden = true;
            }

            document.getElementById('history-total').textContent = `${filtered.length} Total`;
            const shown = Math.min(pageSize, Math.max(filtered.length - currentPage * pageSize, 0));
            document.getElementById('results-count').textContent = `Menampilkan ${shown} dari ${filtered.length} riwayat peminjaman`;
            document.getElementById('previous-page').disabled = currentPage === 0;
            document.getElementById('next-page').disabled = currentPage >= pageCount - 1;
        }

        searchInput.addEventListener('input', () => { currentPage = 0; updateHistory(); });
        statusFilter.addEventListener('change', () => { currentPage = 0; updateHistory(); });
        sortOrder.addEventListener('change', updateHistory);
        document.getElementById('previous-page').addEventListener('click', () => { currentPage--; updateHistory(); });
        document.getElementById('next-page').addEventListener('click', () => { currentPage++; updateHistory(); });
        updateHistory();
    </script>
</body>
</html>
