-- ============================================
-- La Neuro-Odyssée — Base de données
-- ============================================

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL UNIQUE,
    nom         VARCHAR(255) DEFAULT '',
    pack        VARCHAR(50)  DEFAULT '',
    montant     DECIMAL(10,2) DEFAULT 0,
    source      ENUM('formulaire','stripe','admin') DEFAULT 'formulaire',
    actif       TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_actif (actif),
    INDEX idx_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletters (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sujet       VARCHAR(500) NOT NULL,
    contenu     TEXT NOT NULL,
    nb_envois   INT DEFAULT 0,
    envoye_le   DATETIME DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
