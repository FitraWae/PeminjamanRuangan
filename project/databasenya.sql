CREATE TABLE roles (
    idRole SERIAL PRIMARY KEY,
    namaRole VARCHAR(30) UNIQUE NOT NULL
);

CREATE TABLE Users (
    idUser SERIAL PRIMARY KEY,
    namaUser VARCHAR(50) NOT NULL,
	email varchar(255) UNIQUE NOT NULL,
    passwordUser VARCHAR(255) NOT NULL,
	status boolean not null
);

CREATE TABLE User_Roles (
    idUser INT NOT NULL,
    idRole INT NOT NULL,
	status boolean not null default false,
    PRIMARY KEY (idUser, idRole),
    CONSTRAINT fk_ur_user FOREIGN KEY (idUser) REFERENCES Users(idUser) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (idRole) REFERENCES roles(idRole) ON DELETE RESTRICT
);
CREATE UNIQUE INDEX idx_satu_role_aktif
ON User_Roles (idUser)
WHERE status = TRUE;

CREATE TABLE Ruangan (
    idRuangan SERIAL PRIMARY KEY,
    namaRuangan VARCHAR(50) NOT NULL,
    kapasitas INT NOT NULL,
    fasilitas TEXT,
    statusRuangan VARCHAR(20)
);


CREATE TABLE Peminjaman (
    idPeminjaman SERIAL PRIMARY KEY,
    idUser INT NOT NULL,
    kodeTransaksi VARCHAR(20) UNIQUE NOT NULL,
    tglPengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statusPeminjaman VARCHAR(20) CHECK (statusPeminjaman IN ('pending', 'approved', 'rejected', 'completed')) DEFAULT 'pending',
    CONSTRAINT fk_peminjaman_user FOREIGN KEY (idUser) REFERENCES Users(idUser) ON DELETE CASCADE
);

CREATE TABLE Detail_Peminjaman (
    idDetail SERIAL PRIMARY KEY,
    idPeminjaman INT NOT NULL,
    idRuangan INT NOT NULL,
    tglPinjam DATE NOT NULL,
    jamMulai TIME NOT NULL,
    jamSelesai TIME NOT NULL,
    keperluan TEXT NOT NULL,
    CONSTRAINT fk_dp_peminjaman FOREIGN KEY (idPeminjaman) REFERENCES Peminjaman(idPeminjaman) ON DELETE CASCADE,
    CONSTRAINT fk_dp_ruangan FOREIGN KEY (idRuangan) REFERENCES Ruangan(idRuangan) ON DELETE RESTRICT
);
ALTER TABLE Detail_Peminjaman
ADD CONSTRAINT chk_jam_valid CHECK (jamSelesai > jamMulai);

INSERT INTO roles (namarole) VALUES ('admin'), ('pelanggan');

/*buat jadiin admin*/
UPDATE user_roles
SET idrole = 1
WHERE iduser = 1;