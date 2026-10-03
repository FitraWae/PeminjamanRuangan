<?php

class DetailPeminjaman {
    private int $iddetail;
    private int $idpeminjaman;
    private int $idruangan;
    private string $tglpinjam;
    private string $jammulai;
    private string $jamselesai;
    private string $keperluan;

    public function __construct(int $iddetail, int $idpeminjaman, int $idruangan, string $tglpinjam, string $jammulai, string $jamselesai, string $keperluan) {
        $this->iddetail     = $iddetail;
        $this->idpeminjaman = $idpeminjaman;
        $this->idruangan    = $idruangan;
        $this->tglpinjam    = $tglpinjam;
        $this->jammulai     = $jammulai;
        $this->jamselesai   = $jamselesai;
        $this->keperluan    = $keperluan;
    }

    public function get_data(): array {
        return [
            'iddetail'     => $this->iddetail,
            'idpeminjaman' => $this->idpeminjaman,
            'idruangan'    => $this->idruangan,
            'tglpinjam'    => $this->tglpinjam,
            'jammulai'     => $this->jammulai,
            'jamselesai'   => $this->jamselesai,
            'keperluan'    => $this->keperluan,
        ];
    }

    public static function cek_bentrok(int $idruangan, string $tglpinjam, string $jammulai, string $jamselesai, ?int $kecuali_idpeminjaman = null): bool {
        $db = new DBconnection();

        $query = "SELECT dp.iddetail
                  FROM detail_peminjaman dp
                  JOIN peminjaman p ON p.idpeminjaman = dp.idpeminjaman
                  WHERE dp.idruangan = $1
                    AND dp.tglpinjam = $2
                    AND p.statuspeminjaman IN ('pending', 'approved')
                    AND dp.jammulai < $4
                    AND dp.jamselesai > $3";

        $params = [$idruangan, $tglpinjam, $jammulai, $jamselesai];

        if ($kecuali_idpeminjaman !== null) {
            $query .= ' AND dp.idpeminjaman <> $5';
            $params[] = $kecuali_idpeminjaman;
        }

        $respon = $db->send_query($query, $params);
        $db->close_connection();

        if (!$respon->status) {
            return true;
        }

        return count($respon->data) > 0;
    }

    public static function tambah(int $idpeminjaman, int $idruangan, string $tglpinjam, string $jammulai, string $jamselesai, string $keperluan): Respon {
        $db = new DBconnection();
        $query = 'INSERT INTO detail_peminjaman (idpeminjaman, idruangan, tglpinjam, jammulai, jamselesai, keperluan)
                  VALUES ($1, $2, $3, $4, $5, $6) RETURNING iddetail';
        $respon = $db->send_query($query, [$idpeminjaman, $idruangan, $tglpinjam, $jammulai, $jamselesai, $keperluan]);
        $db->close_connection();
        return $respon;
    }

    public static function get_by_peminjaman(int $idpeminjaman): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT dp.*, r.namaruangan
                                    FROM detail_peminjaman dp
                                    JOIN ruangan r ON r.idruangan = dp.idruangan
                                    WHERE dp.idpeminjaman = $1', [$idpeminjaman]);
        $db->close_connection();
        return $respon;
    }
}
