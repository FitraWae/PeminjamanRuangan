<?php
require_once __DIR__ . '/../cek_session.php';
proteksi_role('pelanggan');

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $detail = [[
        'idruangan'  => (int) $_POST['idruangan'],
        'tglpinjam'  => $_POST['tglpinjam'],
        'jammulai'   => $_POST['jammulai'],
        'jamselesai' => $_POST['jamselesai'],
        'keperluan'  => trim($_POST['keperluan']),
    ]];

    $respon = Peminjaman::ajukan((int) $_SESSION['iduser'], $detail);
    $pesan = $respon->message;
}
$daftarRuangan = Ruangan::get_tersedia();
$ruanganList = $daftarRuangan->status ? $daftarRuangan->data : [];
$kapasitasList = array_values(array_unique(array_map(static fn($ruangan) => (int)$ruangan['kapasitas'], $ruanganList)));
sort($kapasitasList);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulir Peminjaman - Sistem Informasi Sarana dan Prasarana</title>
    <style>
        :root { --page: #eaf0ff; --ink: #111827; --muted: #737b8d; --blue: #326cf5; --field: #cdd5e8; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--page); color: var(--ink); font-family: Inter, sans-serif; }
        .page { width: min(100%, 1440px); min-height: 100vh; margin: 0 auto; padding: 28px 4.2% 20px; display: flex; flex-direction: column; }
        .topbar { min-height: 48px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 9px; min-width: 0; color: var(--ink); text-decoration: none; }
        .brand-icon { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 6px; display: grid; place-items: center; background: #23469c; color: white; }
        .brand-icon svg { width: 18px; height: 18px; }
        .brand-name { font-size: 12px; line-height: 1.3; font-weight: 700; }
        .brand-caption { font-size: 9px; line-height: 1.35; color: #4b5563; }
        .tabs { display: flex; align-items: center; padding: 4px; gap: 2px; border-radius: 8px; background: white; }
        .tabs a { padding: 8px 10px; border-radius: 6px; color: #376cf0; font-size: 11px; text-decoration: none; white-space: nowrap; }
        .tabs a.active { color: white; background: var(--blue); }
        .account { justify-self: end; display: flex; align-items: center; gap: 8px; color: var(--ink); text-decoration: none; }
        .account-copy { text-align: right; font-size: 9px; line-height: 1.3; }
        .account-copy strong { display: block; max-width: 105px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .account-icon { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; background: #bd4d00; color: white; }
        .account-icon svg { width: 15px; height: 15px; }
        main { margin-top: 76px; }
        .heading { margin-bottom: 26px; }
        h1 { margin: 0 0 8px; font-size: clamp(22px, 2.2vw, 30px); line-height: 1.2; letter-spacing: 0; }
        .intro { max-width: 560px; margin: 0; color: var(--muted); font-size: 13px; line-height: 1.55; }
        .message { margin: 0 0 20px; padding: 12px 14px; border: 1px solid #bfcee9; border-radius: 7px; background: rgba(255,255,255,.66); color: #243b69; font-size: 12px; }
        .message:empty { display: none; }
        .room-section { margin-bottom: 18px; }
        .field-label { display: block; margin: 0 0 7px; font-size: 12px; font-weight: 500; }
        .room-tools { display: grid; grid-template-columns: minmax(0, 1fr) minmax(170px, 22%); gap: 6px; }
        .room-search { position: relative; }
        .room-search input, .room-tools select, .room-choice, .form-card input, .form-card select, .form-card textarea { width: 100%; border: 1px solid white; border-radius: 7px; font: inherit; outline: none; }
        .room-search input, .room-tools select { height: 40px; background: rgba(255,255,255,.42); color: #4b5563; font-size: 11px; }
        .room-search input { padding: 0 42px 0 14px; }
        .room-search svg { position: absolute; top: 50%; right: 14px; width: 16px; height: 16px; transform: translateY(-50%); color: #4b5563; pointer-events: none; }
        .room-tools select { padding: 0 12px; }
        .room-choice { display: block; height: 40px; margin-top: 7px; padding: 0 12px; background: var(--field); color: #424b5e; font-size: 11px; }
        .room-choice:focus, .room-search input:focus, .room-tools select:focus, .form-card input:focus, .form-card select:focus, .form-card textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(50,108,245,.12); }
        .form-card { padding: 20px 22px; border-radius: 9px; background: white; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px; }
        .form-group { min-width: 0; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group > label { display: block; margin-bottom: 7px; font-size: 12px; font-weight: 500; }
        .date-fields, .time-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px; }
        .time-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .form-card input, .form-card select { height: 40px; padding: 0 12px; background: var(--field); color: #465066; font-size: 11px; }
        .form-card input[type="date"] { min-width: 0; }
        .form-card textarea { min-height: 42px; padding: 12px; resize: vertical; background: var(--field); color: #465066; font-size: 11px; }
        .form-card input::placeholder, .form-card textarea::placeholder, .room-search input::placeholder { color: #778196; opacity: 1; }
        .actions { display: flex; justify-content: flex-end; margin-top: 20px; }
        .submit { min-width: 130px; height: 42px; border: 0; border-radius: 7px; background: var(--blue); color: white; font: inherit; font-size: 12px; cursor: pointer; transition: background .15s ease, transform .15s ease; }
        .submit:hover { background: #2459d8; }
        .submit:active { transform: translateY(1px); }
        footer { margin-top: auto; padding-top: 58px; color: #68738a; font-size: 9px; }
        @media (min-width: 1200px) { .page { padding-right: 5.6%; padding-left: 5.6%; } main { margin-top: 84px; } }
        @media (max-width: 700px) {
            .page { padding: 16px 16px 18px; }
            .topbar { grid-template-columns: 1fr auto; gap: 12px; }
            .tabs { grid-row: 2; grid-column: 1 / -1; justify-content: center; }
            .tabs a { padding: 8px 9px; font-size: 10px; }
            main { margin-top: 44px; }
            .room-tools { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; gap: 16px; }
            .form-group.full { grid-column: auto; }
            .form-card { padding: 17px 14px; }
            .date-fields { gap: 3px; }
            .form-card input, .form-card select { padding-right: 7px; padding-left: 8px; font-size: 10px; }
            .submit { width: 100%; }
            footer { padding-top: 38px; }
        }
        @media (prefers-reduced-motion: no-preference) { main { animation: rise-in .38s ease-out both; } @keyframes rise-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } } }
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
                <a class="active" href="ajukan.php" aria-current="page">Formulir</a>
                <a href="kalender.php">Kalender Peminjaman</a>
                <a href="riwayat.php">Riwayat Peminjaman</a>
            </nav>
            <a class="account" href="../logout.php" title="Keluar dari akun">
                <span class="account-copy"><strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan', ENT_QUOTES, 'UTF-8') ?></strong>Keluar</span>
                <span class="account-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="3"/><path d="M5.5 19c.8-3 3.1-4.5 6.5-4.5s5.7 1.5 6.5 4.5"/></svg></span>
            </a>
        </header>

        <main>
            <section class="heading">
                <h1>Formulir Pengajuan Peminjaman</h1>
                <p class="intro">Pelayanan peminjaman sarana dan prasarana kampus<br>Isi formulir di bawah ini untuk melanjutkan tindakan pengajuan peminjaman!</p>
            </section>

            <?php if ($pesan !== ''): ?>
                <p class="message"><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" action="ajukan.php">
                <section class="room-section">
                    <label class="field-label" for="room-search">Pilih Ruangan</label>
                    <div class="room-tools">
                        <div class="room-search">
                            <input id="room-search" type="search" placeholder="Cari nama ruang ..." aria-label="Cari nama ruangan">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                        </div>
                        <select id="capacity-filter" aria-label="Filter kapasitas">
                            <option value="">Pilih rentang kapasitas ...</option>
                            <?php foreach ($kapasitasList as $kapasitas): ?>
                                <option value="<?= $kapasitas ?>"><?= $kapasitas ?> orang</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <select class="room-choice" id="room-select" name="idruangan" required aria-label="Pilih ruangan">
                        <?php if ($ruanganList): foreach ($ruanganList as $r): ?>
                            <option value="<?= (int)$r['idruangan'] ?>" data-name="<?= htmlspecialchars(mb_strtolower($r['namaruangan']), ENT_QUOTES, 'UTF-8') ?>" data-capacity="<?= (int)$r['kapasitas'] ?>">
                                <?= htmlspecialchars($r['namaruangan'], ENT_QUOTES, 'UTF-8') ?> (kapasitas <?= (int)$r['kapasitas'] ?>)
                            </option>
                        <?php endforeach; else: ?>
                            <option value="" disabled selected>Tidak ada ruangan tersedia</option>
                        <?php endif; ?>
                    </select>
                </section>

                <section class="form-card">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="tglpinjam">Tanggal Peminjaman</label>
                            <div class="date-fields">
                                <input type="date" id="tglpinjam" name="tglpinjam" required aria-label="Tanggal peminjaman">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="jammulai">Pukul Mulai</label>
                            <div class="time-fields"><input type="time" id="jammulai" name="jammulai" required aria-label="Jam mulai"></div>
                        </div>
                        <div class="form-group">
                            <label for="jamselesai">Pukul Selesai</label>
                            <div class="time-fields"><input type="time" id="jamselesai" name="jamselesai" required aria-label="Jam selesai"></div>
                        </div>
                        <div class="form-group full">
                            <label for="keperluan">Keperluan Peminjaman</label>
                            <textarea id="keperluan" name="keperluan" rows="1" placeholder="Isi keperluan atau peruntukan peminjaman sarana dan prasarana ini ..." required></textarea>
                        </div>
                    </div>
                </section>
                <div class="actions"><button class="submit" type="submit">Kirim</button></div>
            </form>
        </main>

        <footer>© 2026 Sistem Informasi Sarana dan Prasarana. Hak Cipta Dilindungi.</footer>
    </div>
    <script>
        const roomSearch = document.getElementById('room-search');
        const capacityFilter = document.getElementById('capacity-filter');
        const roomSelect = document.getElementById('room-select');
        const roomOptions = [...roomSelect.options].filter((option) => option.value !== '');

        function filterRooms() {
            const query = roomSearch.value.trim().toLocaleLowerCase('id');
            const capacity = capacityFilter.value;
            roomOptions.forEach((option) => {
                option.hidden = !option.dataset.name.includes(query) || (capacity !== '' && option.dataset.capacity !== capacity);
            });
            const currentOption = roomSelect.selectedOptions[0];
            if (currentOption && currentOption.hidden) {
                const firstVisible = roomOptions.find((option) => !option.hidden);
                if (firstVisible) roomSelect.value = firstVisible.value;
            }
        }

        roomSearch.addEventListener('input', filterRooms);
        capacityFilter.addEventListener('change', filterRooms);
    </script>
</body>
</html>
