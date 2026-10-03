<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('pelanggan');

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan) || !checkdate((int)substr($bulan, 5, 2), 1, (int)substr($bulan, 0, 4))) {
    $bulan = date('Y-m');
}

$awal = $bulan . '-01';
$akhir = date('Y-m-d', strtotime($awal . ' +1 month'));

$db = new DBconnection();
$sql = "SELECT dp.tglpinjam, dp.jammulai, dp.jamselesai, r.namaruangan
        FROM detail_peminjaman dp
        JOIN peminjaman p ON p.idpeminjaman = dp.idpeminjaman
        JOIN ruangan r ON r.idruangan = dp.idruangan
        WHERE dp.tglpinjam >= $1
          AND dp.tglpinjam < $2
          AND p.statuspeminjaman IN ('approved', 'completed')
        ORDER BY dp.tglpinjam, dp.jammulai";
$res = $db->send_query($sql, [$awal, $akhir]);
$db->close_connection();
$events = $res->status ? $res->data : [];

$group = [];
foreach ($events as $event) {
    $group[$event['tglpinjam']][] = $event;
}

function hcal($value): string {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}

$start = (int)date('w', strtotime($awal));
$days = (int)date('t', strtotime($awal));
$prev = date('Y-m', strtotime($awal . ' -1 month'));
$next = date('Y-m', strtotime($awal . ' +1 month'));
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$judulBulan = $namaBulan[(int)substr($bulan, 5, 2)] . ' ' . substr($bulan, 0, 4);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender Peminjaman - Sistem Informasi Sarana dan Prasarana</title>
    <style>
        :root{--page:#eaf0ff;--ink:#111827;--muted:#737b8d;--blue:#326cf5;--line:#e4e8f2}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;background:var(--page);color:var(--ink);font-family:Inter,sans-serif}
        .page{width:min(100%,1440px);min-height:100vh;margin:0 auto;padding:28px 4.2% 20px;display:flex;flex-direction:column}
        .topbar{min-height:48px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:20px}
        .brand{display:flex;align-items:center;gap:9px;min-width:0;color:var(--ink);text-decoration:none}
        .brand-icon{width:28px;height:28px;flex:0 0 28px;border-radius:6px;display:grid;place-items:center;background:#23469c;color:white}
        .brand-icon svg{width:18px;height:18px}
        .brand-name{font-size:12px;line-height:1.3;font-weight:700}
        .brand-caption{font-size:9px;line-height:1.35;color:#4b5563}
        .tabs{display:flex;align-items:center;padding:4px;gap:2px;border-radius:8px;background:white}
        .tabs a{padding:8px 10px;border-radius:6px;color:#376cf0;font-size:11px;text-decoration:none;white-space:nowrap}
        .tabs a.active{color:white;background:var(--blue)}
        .account{justify-self:end;display:flex;align-items:center;gap:8px;color:var(--ink);text-decoration:none}
        .account-copy{text-align:right;font-size:9px;line-height:1.3}
        .account-copy strong{display:block;max-width:105px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .account-icon{width:28px;height:28px;display:grid;place-items:center;border-radius:50%;background:#bd4d00;color:white}
        .account-icon svg{width:15px;height:15px}
        main{margin-top:72px}
        .heading{margin-bottom:28px}
        h1{margin:0 0 8px;font-size:clamp(22px,2.2vw,30px);line-height:1.2;letter-spacing:0}
        .intro{max-width:560px;margin:0;color:var(--muted);font-size:13px;line-height:1.55}
        .calendar-toolbar{min-height:48px;margin-bottom:18px;padding:6px 10px;display:flex;align-items:center;gap:12px;border-radius:8px;background:white}
        .month-link{width:30px;height:30px;display:grid;place-items:center;border-radius:6px;color:#4d5b76;text-decoration:none;font-size:18px}
        .month-link:hover{background:#edf2ff;color:var(--blue)}
        .month-label{min-width:125px;font-size:12px;font-weight:600}
        .month-select{height:30px;max-width:150px;padding:0 8px;border:1px solid var(--line);border-radius:5px;background:white;color:#4d5668;font:inherit;font-size:10px}
        .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .calendar-total{margin-left:auto;display:flex;align-items:center;gap:8px;color:#48536a;font-size:10px;font-weight:600}
        .calendar-total svg{width:15px;height:15px;color:var(--blue)}
        .calendar-viewport{overflow-x:auto;padding-bottom:2px}
        .calendar-grid{min-width:720px;display:grid;grid-template-columns:repeat(7,minmax(0,1fr));grid-template-rows:30px repeat(6,minmax(122px,auto));gap:6px}
        .weekday{display:grid;place-items:center;border-radius:5px;background:#f4f5fc;color:#495268;font-size:10px;font-weight:700;text-transform:uppercase}
        .weekday:first-child{color:#d34242}
        .weekday:last-child{color:#2b5eb8}
        .day-cell{min-width:0;min-height:122px;padding:9px;border:1px solid transparent;border-radius:8px;background:rgba(255,255,255,.78)}
        .day-cell.outside{background:rgba(255,255,255,.35);color:#a4aabd}
        .day-cell.today{border-color:#8aa8ff;box-shadow:0 0 0 2px rgba(50,108,245,.08)}
        .day-number{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;font-size:11px;font-weight:600}
        .outside .day-number{font-weight:400}
        .today-mark{width:6px;height:6px;border-radius:50%;background:var(--blue)}
        .event-card{margin-top:6px;padding:7px;border:1px solid #d9e4ff;border-radius:6px;background:#edf3ff;color:#23345d;font-size:9px;line-height:1.4;overflow-wrap:anywhere}
        .event-time{display:flex;justify-content:space-between;gap:3px;margin-bottom:4px;color:#245bc9;font-size:8px;font-weight:700}
        .event-room{font-weight:600}
        .event-card:nth-of-type(3n){border-color:#f1dfcf;background:#fff4e9;color:#80501f}
        .event-card:nth-of-type(3n) .event-time{color:#ba5a16}
        .empty-month{grid-column:1/-1;padding:12px 0;color:var(--muted);font-size:11px;text-align:center}
        footer{margin-top:auto;padding-top:48px;color:#68738a;font-size:9px}
        @media(min-width:1200px){.page{padding-right:5.6%;padding-left:5.6%}main{margin-top:84px}}
        @media(max-width:700px){
            .page{padding:16px 16px 18px}
            .topbar{grid-template-columns:1fr auto;gap:12px}
            .tabs{grid-row:2;grid-column:1/-1;justify-content:center}
            .tabs a{padding:8px 9px;font-size:10px}
            main{margin-top:44px}
            .calendar-toolbar{gap:6px;padding:6px}
            .month-label{min-width:0;flex:1;font-size:10px}
            .month-select{max-width:112px}
            .calendar-total{font-size:0}
            .calendar-total svg{width:17px;height:17px}
            .calendar-grid{grid-template-rows:28px repeat(6,minmax(108px,auto));gap:4px}
            .day-cell{min-height:108px;padding:6px}
            footer{padding-top:34px}
        }
        @media(prefers-reduced-motion:no-preference){main{animation:rise-in .38s ease-out both}@keyframes rise-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}}
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
                <a class="active" href="kalender.php" aria-current="page">Kalender Peminjaman</a>
                <a href="riwayat.php">Riwayat Peminjaman</a>
            </nav>
            <a class="account" href="../logout.php" title="Keluar dari akun">
                <span class="account-copy"><strong><?= hcal($_SESSION['nama'] ?? 'Pelanggan') ?></strong>Keluar</span>
                <span class="account-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="3"/><path d="M5.5 19c.8-3 3.1-4.5 6.5-4.5s5.7 1.5 6.5 4.5"/></svg></span>
            </a>
        </header>

        <main>
            <section class="heading">
                <h1>Kalender Peminjaman Saya</h1>
                <p class="intro">Pantau seluruh jadwal peminjaman ruangan Anda.</p>
            </section>

            <section class="calendar-toolbar" aria-label="Navigasi kalender">
                <a class="month-link" href="?bulan=<?= hcal($prev) ?>" aria-label="Bulan sebelumnya">‹</a>
                <strong class="month-label"><?= hcal($judulBulan) ?></strong>
                <a class="month-link" href="?bulan=<?= hcal($next) ?>" aria-label="Bulan berikutnya">›</a>
                <form method="get">
                    <label class="sr-only" for="month-picker">Pilih bulan</label>
                    <input class="month-select" id="month-picker" type="month" name="bulan" value="<?= hcal($bulan) ?>" onchange="this.form.submit()">
                </form>
                <span class="calendar-total"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h2M14 14h2"/></svg><?= count($events) ?> jadwal bulan ini</span>
            </section>

            <div class="calendar-viewport">
                <section class="calendar-grid" aria-label="Kalender bulan <?= hcal($judulBulan) ?>">
                    <?php foreach (['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $name): ?>
                        <div class="weekday"><?= hcal($name) ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < 42; $i++):
                        $offset = $i - $start;
                        $cellDate = date('Y-m-d', strtotime($awal . ' ' . ($offset >= 0 ? '+' : '') . $offset . ' days'));
                        $inMonth = substr($cellDate, 0, 7) === $bulan;
                        $cellEvents = $inMonth ? ($group[$cellDate] ?? []) : [];
                        $isToday = $cellDate === date('Y-m-d');
                    ?>
                        <div class="day-cell<?= $inMonth ? '' : ' outside' ?><?= $isToday ? ' today' : '' ?>">
                            <div class="day-number"><span><?= (int)date('j', strtotime($cellDate)) ?></span><?php if ($isToday): ?><span class="today-mark" aria-label="Hari ini"></span><?php endif; ?></div>
                            <?php foreach ($cellEvents as $event): ?>
                                <article class="event-card">
                                    <div class="event-time"><span><?= hcal(substr($event['jammulai'], 0, 5)) ?>–<?= hcal(substr($event['jamselesai'], 0, 5)) ?></span></div>
                                    <div class="event-room"><?= hcal($event['namaruangan']) ?></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </section>
            </div>
        </main>

        <footer>© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>
</body>
</html>