<?php

class Role {
    private int $idrole;
    private string $nama_role;
    private bool $status;

    public function __construct(int $idrole, string $nama_role, bool $status = false) {
        $this->idrole    = $idrole;
        $this->nama_role = $nama_role;
        $this->status    = $status;
    }

    public function get_status(): bool {
        return $this->status;
    }

    public function set_status(bool $status): void {
        $this->status = $status;
    }

    public function get_data(): array {
        return [
            'idrole'    => $this->idrole,
            'nama_role' => $this->nama_role,
            'status'    => $this->status,
        ];
    }

    public static function get_roles_by_user(int $iduser): array {
        $db = new DBconnection();

        $query = 'SELECT r.idrole, r.namarole, ur.status
                  FROM user_roles ur
                  JOIN roles r ON r.idrole = ur.idrole
                  WHERE ur.iduser = $1';

        $respon = $db->send_query($query, [$iduser]);
        $db->close_connection();

        if (!$respon->status) {
            return [];
        }

        $roles = [];
        foreach ($respon->data as $row) {
            $roles[] = new Role((int) $row['idrole'], $row['namarole'], $row['status'] === 't');
        }
        return $roles;
    }

    public static function get_all(): Respon {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT idrole, namarole FROM roles ORDER BY idrole');
        $db->close_connection();
        return $respon;
    }

    public static function assign_ke_user(int $iduser, int $idrole): Respon {
        $db = new DBconnection();
        $query = 'INSERT INTO user_roles (iduser, idrole, status) VALUES ($1, $2, FALSE)';
        $respon = $db->send_query($query, [$iduser, $idrole]);
        $db->close_connection();
        return $respon;
    }
}
