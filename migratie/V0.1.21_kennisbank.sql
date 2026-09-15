-- ============================================================
-- MK INTRANET - Migratie voor V0.1.21
-- Kennisbank: Q&A + Documenten, eigen "Event"-tab in de navigatie,
-- MK-Intranet-only (geen mkapp-kant, geen eigen weergave daar).
--
-- Wat er verandert:
-- 1. Nieuwe tabellen: kb_categorieen (hoofdstukken/categorieen, gedeeld
--    tussen Q&A en Documenten), kb_items + kb_item_links (Q&A,
--    vraag/antwoord + tot 5 links per item), kb_documenten +
--    kb_document_links (documentenarchief: titel + toelichting + tot 5
--    links per document).
-- 2. Nieuwe navigatietab "Event" met daaronder Q&A en Documenten,
--    zichtbaar voor iedere ingelogde gebruiker (zelfde als
--    Dashboard/Berichten -- geen aparte rolbeperking).
-- 3. Nieuwe beheerpagina kennisbank.php (Beheer -> Kennisbank beheren):
--    categorieen, Q&A-items en documenten beheren, inclusief de links
--    per item/document (zelfde patroon als bericht_links bij Berichten).
-- ============================================================

CREATE TABLE IF NOT EXISTS kb_categorieen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kb_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    vraag VARCHAR(255) NOT NULL,
    antwoord TEXT NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    auteur_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES kb_categorieen(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kb_item_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kb_item_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    FOREIGN KEY (kb_item_id) REFERENCES kb_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kb_documenten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    titel VARCHAR(255) NOT NULL,
    toelichting TEXT DEFAULT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    auteur_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES kb_categorieen(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kb_document_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kb_document_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    FOREIGN KEY (kb_document_id) REFERENCES kb_documenten(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.21', '15 september 2026', '## Nieuw
- Nieuwe navigatietab "Event" met daaronder Q&A en Documenten: een kennisbank, in te richten op categorie/hoofdstuk.
- Q&A: vraag/antwoord per categorie, met een zoekveld en per vraag tot 5 links naar naslag/documenten.
- Documenten: een apart archief van titel + toelichting + links per categorie, voor materiaal zonder vraag-vorm (draaiboeken, protocollen).
- Nieuwe beheerpagina Beheer -> Kennisbank beheren: categorieen, Q&A-items en documenten toevoegen/bewerken/verwijderen, inclusief het link-beheer per item.
- Zichtbaar voor iedere ingelogde gebruiker, alleen beheerbaar door beheerders. MK-Intranet-only, geen eigen weergave in mkapp.');
