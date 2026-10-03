<?php
require_once __DIR__ . '/boothstrap.php';

if (!isset($_SESSION['iduser'])) {
    header('Location: /login.php');
    exit;
}

// Panggil di halaman yang cuma boleh diakses role tertentu, misal:
// proteksi_role('admin');  atau  proteksi_role('pelanggan');
function proteksi_role(string $role_dibutuhkan): void {
    if (($_SESSION['role'] ?? null) !== $role_dibutuhkan) {
        header('Location: /project/login.php?error=akses_ditolak');
        exit;
    }
}
