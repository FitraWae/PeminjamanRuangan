<?php

class Peminjaman {
    private int $idpeminjaman;
    private int $iduser;
    private string $kodetransaksi;
    private string $statuspeminjaman;

    public function __construct(int $idpeminjaman, int $iduser, string $kodetransaksi, string $statuspeminjaman) {
        $this->idpeminjaman     = $idpeminjaman;
        $this->iduser           = $iduser;
        $this->kodetransaksi    = $kodetransaksi;
        $this->statuspeminjaman = $statuspeminjaman;
    }

    public function get_data(): array {
        return [
            'idpeminjaman'     => $this->idpeminjaman,
            'iduser'           => $this->iduser,
            'kodetransaksi'    => $this->kodetransaksi,
            'statuspeminjaman' => $this->statuspeminjaman,
        ];
    }

    public static function ajukan(int $iduser, array $detail): Respon {
        if (empty($detail)) {
            return new Respon(false, "Minimal harus ada 1 ruangan yang diajukan.");
        }

        foreach ($detail as $d) {
            $bentrok = DetailPeminjaman::cek_bentrok($d['idruangan'], $d['tglpinjam'], $d['jammulai'], $d['jamselesai']);
            if ($bentrok) {
                return new Respon(false, "Ruangan sudah dipakai peminjaman lain di jam yang sama.");
            }
        }

        $db = new DBconnection();
        $kodetransaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        try {
            $db->mulai_transaksi();

            $queryHeader = 'INSERT INTO peminjaman (iduser, kodetransaksi) VALUES ($1, $2) RETURNING idpeminjaman';
            $responHeader = $db->send_query($queryHeader, [$iduser, $kodetransaksi]);

            if (!$responHeader->status) {
                $db->rollback();
                $db->close_connection();
                return $responHeader;
            }

            $idpeminjaman = $responHeader->data[0]['idpeminjaman'];

            foreach ($detail as $d) {
                $queryDetail = 'INSERT INTO detail_peminjaman (idpeminjaman, idruangan, tglpinjam, jammulai, jamselesai, keperluan)
                                VALUES ($1, $2, $3, $4, $5, $6)';
                $responDetail = $db->send_query($queryDetail, [
                    $idpeminjaman, $d['idruangan'], $d['tglpinjam'], $d['jammulai'], $d['jamselesai'], $d['keperluan'],
                ]);

                if (!$responDetail->status) {
                    $db->rollback();
                    $db->close_connection();
                    return $responDetail;
                }
            }

            $db->commit();
            $db->close_connection();
            return new Respon(true, "Peminjaman berhasil diajukan.", ['idpeminjaman' => $idpeminjaman, 'kodetransaksi' => $kodetransaksi]);

        } catch (DatabaseException $e) {
            $db->rollback();
            $db->close_connection();
            return new Respon(false, "Gagal mengajukan peminjaman: " . $e->getMessage());
        }
    }

    public function approve(): Respon {
        if ($this->statuspeminjaman !== 'pending') {
            return new Respon(false, "Peminjaman ini sudah diproses sebelumnya.");
        }

        $db = new DBconnection();
        $query = "UPDATE peminjaman SET statuspeminjaman = 'approved' WHERE idpeminjaman = $1";
        $respon = $db->send_query($query, [$this->idpeminjaman]);
        $db->close_connection();

        if ($respon->status) {
            $this->statuspeminjaman = 'approved';
        }
        return $respon;
    }

    public function reject(): Respon {
        if ($this->statuspeminjaman !== 'pending') {
            return new Respon(false, "Peminjaman ini sudah diproses sebelumnya.");
        }

        $db = new DBconnection();
        $query = "UPDATE peminjaman SET statuspeminjaman = 'rejected' WHERE idpeminjaman = $1";
        $respon = $db->send_query($query, [$this->idpeminjaman]);
        $db->close_connection();

        if ($respon->status) {
            $this->statuspeminjaman = 'rejected';
        }
        return $respon;
    }

    public function tandai_selesai(): Respon {
        if ($this->statuspeminjaman !== 'approved') {
            return new Respon(false, "Hanya peminjaman berstatus 'approved' yang bisa diselesaikan.");
        }

        $db = new DBconnection();
        $query = "UPDATE peminjaman SET statuspeminjaman = 'completed' WHERE idpeminjaman = $1";
        $respon = $db->send_query($query, [$this->idpeminjaman]);
        $db->close_connection();

        if ($respon->status) {
            $this->statuspeminjaman = 'completed';
        }
        return $respon;
    }

    public static function get_by_user(int $iduser): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT * FROM peminjaman WHERE iduser = $1 ORDER BY tglpengajuan DESC', [$iduser]);
        $db->close_connection();
        return $respon;
    }

    public static function get_pending(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query("SELECT * FROM peminjaman WHERE statuspeminjaman = 'pending' ORDER BY tglpengajuan");
        $db->close_connection();
        return $respon;
    }

    public static function get_approved(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query("SELECT * FROM peminjaman WHERE statuspeminjaman = 'approved' ORDER BY tglpengajuan");
        $db->close_connection();
        return $respon;
    }

    public static function get_by_id(int $idpeminjaman): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT * FROM peminjaman WHERE idpeminjaman = $1', [$idpeminjaman]);
        $db->close_connection();
        return $respon;
    }
}
