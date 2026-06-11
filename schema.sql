CREATE TABLE IF NOT EXISTS Utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    hash_mdp VARCHAR(255) NOT NULL
);


CREATE TABLE IF NOT EXISTS Voyages (
    id_voyage INT AUTO_INCREMENT PRIMARY KEY,
    titre_destination VARCHAR(100) NOT NULL,
    date_debut DATE NOT NULL,
    duree_jours INT NOT NULL
);


CREATE TABLE IF NOT EXISTS Participants (
    id_utilisateur INT NOT NULL,
    id_voyage INT NOT NULL,
    PRIMARY KEY (id_utilisateur, id_voyage),
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_voyage) REFERENCES Voyages(id_voyage) ON DELETE CASCADE
);



CREATE TABLE IF NOT EXISTS Etapes (
    id_etape INT AUTO_INCREMENT PRIMARY KEY,
    id_voyage INT NOT NULL,
    nom_etape VARCHAR(150) NOT NULL,
    num_jour INT NOT NULL, /* On devra faire attention a ce que ce soit <=  durée_jour de voyage*/
    prix DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_voyage) REFERENCES Voyages(id_voyage) ON DELETE CASCADE
);
