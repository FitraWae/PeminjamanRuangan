<?php

class Ruangan {
    private int $idruangan;
    private string $namaruangan;
    private int $kapasitas;
    private ?string $fasilitas;
    private string $statusruangan;

    public function __construct(int $idruangan, string $namaruangan, int $kapasitas, ?string $fasilitas, string $statusruangan) {
        $this->idruangan     = $idruangan;
        $this->namaruangan   = $namaruangan;
        $this->kapasitas     = $kapasitas;
        $this->fasilitas     = $fasilitas;
        $this->statusruangan = $statusruangan;
    }

    public function get_data(): array {
        return [
            'idruangan'     => $this->idruangan,
            'namaruangan'   => $this->namaruangan,
            'kapasitas'     => $this->kapasitas,
            'fasilitas'     => $this->fasilitas,
            'statusruangan' => $this->statusruangan,
        ];
    }

    public static function tambah(string $nama, int $kapasitas, ?string $fasilitas): Respon {
        $db = new DBconnection();
        $query = 'INSERT INTO ruangan (namaruangan, kapasitas, fasilitas)
                  VALUES ($1, $2, $3) RETURNING idruangan';
        $respon = $db->send_query($query, [$nama, $kapasitas, $fasilitas]);
        $db->close_connection();
        return $respon;
    }

    public function update(string $nama, int $kapasitas, ?string $fasilitas): Respon {
        $db = new DBconnection();
        $query = 'UPDATE ruangan SET namaruangan = $1, kapasitas = $2, fasilitas = $3
                  WHERE idruangan = $4';
        $respon = $db->send_query($query, [$nama, $kapasitas, $fasilitas, $this->idruangan]);
        $db->close_connection();
        return $respon;
    }

    public function hapus(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('DELETE FROM ruangan WHERE idruangan = $1', [$this->idruangan]);
        $db->close_connection();
        return $respon;
    }

    public function set_status(string $status): Respon {
        $status = strtolower(trim($status));

        if (!in_array($status, ['tersedia', 'tidak tersedia'], true)) {
            return new Respon(false, "Status ruangan tidak valid.");
        }
        $db = new DBconnection();
        $query = 'UPDATE ruangan SET statusruangan = $1 WHERE idruangan = $2';
        $respon = $db->send_query($query, [$status, $this->idruangan]);
        $db->close_connection();
        return $respon;
    }
    public static function get_all(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT * FROM ruangan ORDER BY idruangan');
        $db->close_connection();
        return $respon;
    }

    public static function get_by_id(int $idruangan): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT * FROM ruangan WHERE idruangan = $1', [$idruangan]);
        $db->close_connection();
        return $respon;
    }
    public static function get_tersedia(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query("SELECT * FROM ruangan WHERE statusruangan = 'tersedia' ORDER BY idruangan");
        $db->close_connection();
        return $respon;
    }
}
