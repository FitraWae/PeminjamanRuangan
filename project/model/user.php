<?php

class User {
    private int $iduser;
    private string $nama;
    private string $email;
    private array $role = [];

    public function __construct(int $iduser, string $nama, string $email) {
        $this->iduser = $iduser;
        $this->nama   = $nama;
        $this->email  = $email;
    }

    public function get_iduser(): int {
        return $this->iduser;
    }

    public function get_nama(): string {
        return $this->nama;
    }

    public function get_email(): string {
        return $this->email;
    }

    public function get_user(): array {
        $role_aktif = $this->get_role_aktif();
        return [
            'iduser' => $this->iduser,
            'nama'   => $this->nama,
            'email'  => $this->email,
            'role'   => $role_aktif === null ? '-' : $role_aktif->get_data()['nama_role'],
        ];
    }

    public function set_role(Role $role): void {
        if ($role->get_status() === true) {
            foreach ($this->role as $r) {
                $r->set_status(false);
            }
        }
        $this->role[] = $role;
    }

    public function get_role_aktif(): ?Role {
        foreach ($this->role as $r) {
            if ($r->get_status() === true) {
                return $r;
            }
        }
        return null;
    }

    public function hapus_role(int $idrole): Respon {
        $db = new DBconnection();

        $query = 'DELETE FROM user_roles WHERE iduser = $1 AND idrole = $2';
        $respon = $db->send_query($query, [$this->iduser, $idrole]);

        $db->close_connection();
        return $respon;
    }

    public function set_role_aktif(int $idrole): Respon {
        $db = new DBconnection();

        try {
            $db->mulai_transaksi();
            $queryReset = 'UPDATE user_roles SET status = FALSE WHERE iduser = $1';
            $responReset = $db->send_query($queryReset, [$this->iduser]);

            if (!$responReset->status) {
                $db->rollback();
                $db->close_connection();
                return $responReset;
            }

            $queryAktif = 'UPDATE user_roles SET status = TRUE WHERE iduser = $1 AND idrole = $2';
            $responAktif = $db->send_query($queryAktif, [$this->iduser, $idrole]);

            if ($responAktif->status) {
                $db->commit();
            } else {
                $db->rollback();
            }

            $db->close_connection();
            return $responAktif;

        } catch (DatabaseException $e) {
            $db->rollback();
            $db->close_connection();
            return new Respon(false, "Gagal mengubah role aktif: " . $e->getMessage());
        }
    }

    public static function daftar(string $nama, string $email, string $password): Respon {
        $db = new DBconnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $email = strtolower(trim($email));

        try {
            $db->mulai_transaksi();

            $queryUser = 'INSERT INTO users (namauser, email, passworduser, status) VALUES ($1, $2, $3, TRUE) RETURNING iduser';
            $responUser = $db->send_query($queryUser, [$nama, $email, $hash]);

            if (!$responUser->status) {
                $db->rollback();
                $db->close_connection();

                if (str_contains($responUser->message, 'users_email_unique') || str_contains($responUser->message, 'duplicate key')) {
                    return new Respon(false, "Email sudah terdaftar, gunakan email lain.");
                }
                return $responUser;
            }

            $iduser = $responUser->data[0]['iduser'];

            $queryRole = "SELECT idrole FROM roles WHERE namarole = 'pelanggan'";
            $responRole = $db->send_query($queryRole);

            if (!$responRole->status || empty($responRole->data)) {
                $db->rollback();
                $db->close_connection();
                return new Respon(false, "Role 'pelanggan' belum ada di tabel roles.");
            }

            $idrole = $responRole->data[0]['idrole'];

            $queryUR = 'INSERT INTO user_roles (iduser, idrole, status) VALUES ($1, $2, TRUE)';
            $responUR = $db->send_query($queryUR, [$iduser, $idrole]);

            if (!$responUR->status) {
                $db->rollback();
                $db->close_connection();
                return $responUR;
            }

            $db->commit();
            $db->close_connection();
            return new Respon(true, "Registrasi berhasil.", ['iduser' => $iduser]);

        } catch (DatabaseException $e) {
            $db->rollback();
            $db->close_connection();
            return new Respon(false, "Gagal registrasi: " . $e->getMessage());
        }
    }

    public static function dari_login(string $email, string $password): ?User {
        $email = strtolower(trim($email));

        $db = new DBconnection();
        $query = 'SELECT iduser, namauser, email, passworduser, status FROM users WHERE email = $1';
        $respon = $db->send_query($query, [$email]);
        $db->close_connection();

        if (!$respon->status || empty($respon->data)) {
            return null;
        }

        $row = $respon->data[0];

        if ($row['status'] === 'f') {
            return null;
        }

        if (!password_verify($password, $row['passworduser'])) {
            return null;
        }

        $user = new User((int) $row['iduser'], $row['namauser'], $row['email']);
        foreach (Role::get_roles_by_user((int) $row['iduser']) as $role) {
            $user->role[] = $role;
        }

        return $user;
    }

    public static function dari_id(int $iduser): ?User {
        $db = new DBconnection();
        $respon = $db->send_query('SELECT iduser, namauser, email FROM users WHERE iduser = $1', [$iduser]);
        $db->close_connection();

        if (!$respon->status || empty($respon->data)) {
            return null;
        }

        $row = $respon->data[0];
        $user = new User((int) $row['iduser'], $row['namauser'], $row['email']);
        foreach (Role::get_roles_by_user((int) $row['iduser']) as $role) {
            $user->role[] = $role;
        }

        return $user;
    }

    public function __toString(): string {
        return $this->nama;
    }
}
