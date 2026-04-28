-- =============================================
-- EduSchedule Pro - Script Base de Données
-- Date : 2026
-- =============================================

CREATE DATABASE IF NOT EXISTS eduschedule_pro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE eduschedule_pro;

-- ---------------------------------------------
-- Table : classes
-- ---------------------------------------------
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    annee_academique VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Table : matieres
-- ---------------------------------------------
CREATE TABLE matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    volume_horaire_total INT NOT NULL,
    coefficient DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Table : enseignants
-- ---------------------------------------------
CREATE TABLE enseignants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(30) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    specialite VARCHAR(100),
    statut ENUM('vacataire', 'permanent') NOT NULL DEFAULT 'vacataire',
    taux_horaire DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Table : salles
-- ---------------------------------------------
CREATE TABLE salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    capacite INT NOT NULL,
    equipements TEXT,
    batiment VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Table : utilisateurs
-- ---------------------------------------------
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'enseignant', 'delegue', 'surveillant', 'comptable', 'etudiant') NOT NULL,
    id_lien INT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    token_reset VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------
-- Table : emploi_temps
-- ---------------------------------------------
CREATE TABLE emploi_temps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_classe INT NOT NULL,
    semaine_debut DATE NOT NULL,
    statut_publication ENUM('brouillon', 'publie') DEFAULT 'brouillon',
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_classe) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id)
);

-- ---------------------------------------------
-- Table : creneaux
-- ---------------------------------------------
CREATE TABLE creneaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_emploi_temps INT NOT NULL,
    id_matiere INT NOT NULL,
    id_enseignant INT NOT NULL,
    id_salle INT NOT NULL,
    jour ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    qr_token VARCHAR(255) DEFAULT NULL,
    qr_expire DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_emploi_temps) REFERENCES emploi_temps(id) ON DELETE CASCADE,
    FOREIGN KEY (id_matiere) REFERENCES matieres(id),
    FOREIGN KEY (id_enseignant) REFERENCES enseignants(id),
    FOREIGN KEY (id_salle) REFERENCES salles(id)
);

-- ---------------------------------------------
-- Table : pointages
-- ---------------------------------------------
CREATE TABLE pointages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_creneau INT NOT NULL,
    heure_pointage_reelle DATETIME NOT NULL,
    ip_source VARCHAR(50),
    token_utilise VARCHAR(255),
    statut ENUM('valide', 'retard', 'echoue') DEFAULT 'valide',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id) ON DELETE CASCADE
);

-- ---------------------------------------------
-- Table : cahiers_texte
-- ---------------------------------------------
CREATE TABLE cahiers_texte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_creneau INT NOT NULL,
    id_delegue INT NOT NULL,
    titre_cours VARCHAR(255),
    contenu_json TEXT,
    heure_fin_reelle TIME DEFAULT NULL,
    statut ENUM('brouillon', 'signe_delegue', 'cloture') DEFAULT 'brouillon',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id) ON DELETE CASCADE,
    FOREIGN KEY (id_delegue) REFERENCES utilisateurs(id)
);

-- ---------------------------------------------
-- Table : signatures
-- ---------------------------------------------
CREATE TABLE signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cahier INT NOT NULL,
    type_signataire ENUM('delegue', 'enseignant') NOT NULL,
    id_utilisateur INT NOT NULL,
    signature_base64 LONGTEXT NOT NULL,
    horodatage DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cahier) REFERENCES cahiers_texte(id) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);

-- ---------------------------------------------
-- Table : travaux_demandes
-- ---------------------------------------------
CREATE TABLE travaux_demandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cahier INT NOT NULL,
    description TEXT NOT NULL,
    date_limite DATE,
    type ENUM('devoir', 'exercice', 'projet', 'autre') DEFAULT 'devoir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cahier) REFERENCES cahiers_texte(id) ON DELETE CASCADE
);

-- ---------------------------------------------
-- Table : vacations
-- ---------------------------------------------
CREATE TABLE vacations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_enseignant INT NOT NULL,
    mois INT NOT NULL,
    annee INT NOT NULL,
    montant_brut DECIMAL(12,2) DEFAULT 0.00,
    montant_net DECIMAL(12,2) DEFAULT 0.00,
    statut ENUM('generee', 'signee_enseignant', 'validee_surveillant', 'approuvee_comptable') DEFAULT 'generee',
    date_generation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_enseignant) REFERENCES enseignants(id)
);

-- ---------------------------------------------
-- Table : vacation_lignes
-- ---------------------------------------------
CREATE TABLE vacation_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_vacation INT NOT NULL,
    id_creneau INT NOT NULL,
    duree_heures DECIMAL(5,2) NOT NULL,
    taux DECIMAL(10,2) NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_vacation) REFERENCES vacations(id) ON DELETE CASCADE,
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id)
);

-- ---------------------------------------------
-- Table : validations
-- ---------------------------------------------
CREATE TABLE validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_vacation INT NOT NULL,
    id_validateur INT NOT NULL,
    role_validateur ENUM('enseignant', 'surveillant', 'comptable') NOT NULL,
    visa_base64 LONGTEXT,
    date_validation DATETIME DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT,
    FOREIGN KEY (id_vacation) REFERENCES vacations(id) ON DELETE CASCADE,
    FOREIGN KEY (id_validateur) REFERENCES utilisateurs(id)
);

-- ---------------------------------------------
-- Table : logs_activite
-- ---------------------------------------------
CREATE TABLE logs_activite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details_json TEXT,
    ip VARCHAR(50),
    date_heure DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- =============================================
-- DONNÉES DE DÉMONSTRATION
-- =============================================

-- Classes
INSERT INTO classes (code, libelle, niveau, annee_academique) VALUES
('RST-L1-A', 'Licence 1 RST Groupe A', 'Licence 1', '2025-2026'),
('RST-L2-A', 'Licence 2 RST Groupe A', 'Licence 2', '2025-2026'),
('RST-L3-A', 'Licence 3 RST Groupe A', 'Licence 3', '2025-2026'),
('RST-M1-A', 'Master 1 RST Groupe A', 'Master 1', '2025-2026');

-- Matières
INSERT INTO matieres (code, libelle, volume_horaire_total, coefficient) VALUES
('DEV-WEB', 'Développement Web', 60, 3.00),
('RESEAU', 'Réseaux Informatiques', 45, 2.50),
('BD', 'Bases de Données', 45, 2.50),
('ALGO', 'Algorithmique', 30, 2.00),
('SYS-EXP', 'Systèmes d Exploitation', 30, 2.00);

-- Enseignants
INSERT INTO enseignants (matricule, nom, prenom, email, specialite, statut, taux_horaire) VALUES
('ENS001', 'BERE', 'Cédric', 'bere.cedric@isge.bf', 'Développement Web', 'permanent', 5000.00),
('ENS002', 'OUEDRAOGO', 'Moussa', 'ouedraogo.moussa@isge.bf', 'Réseaux', 'vacataire', 4500.00),
('ENS003', 'KABORE', 'Aïcha', 'kabore.aicha@isge.bf', 'Bases de Données', 'vacataire', 4500.00),
('ENS004', 'TRAORE', 'Ibrahim', 'traore.ibrahim@isge.bf', 'Algorithmique', 'permanent', 5000.00),
('ENS005', 'SOME', 'Clarisse', 'some.clarisse@isge.bf', 'Systèmes', 'vacataire', 4000.00);

-- Salles
INSERT INTO salles (code, capacite, equipements, batiment) VALUES
('SALLE-A1', 50, 'Tableau, Vidéoprojecteur, Climatisation', 'Bâtiment A'),
('SALLE-A2', 40, 'Tableau, Vidéoprojecteur', 'Bâtiment A'),
('SALLE-B1', 60, 'Tableau, Vidéoprojecteur, Climatisation', 'Bâtiment B'),
('LABO-INFO', 30, 'Ordinateurs, Tableau, Vidéoprojecteur', 'Bâtiment C');

-- Utilisateurs (mots de passe : "password123" hashé en bcrypt)
INSERT INTO utilisateurs (email, mot_de_passe_hash, role, id_lien) VALUES
('admin@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
('bere.cedric@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant', 1),
('ouedraogo.moussa@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant', 2),
('delegue.l1@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delegue', NULL),
('surveillant@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surveillant', NULL),
('comptable@isge.bf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'comptable', NULL);