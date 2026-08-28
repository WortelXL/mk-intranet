-- ============================================================
-- MK INTRANET - Migratie voor V0.0.5
-- Voer dit één keer uit tegen de bestaande (live) database, vóórdat je
-- V0.0.5 in gebruik neemt. Beide tabellen zijn nieuw en alleen voor
-- MK Intranet -- mkapp gebruikt ze niet en wordt hier niet door geraakt.
-- ============================================================

-- Mededelingen die beheerders plaatsen op het intranet-dashboard
-- (los van meldingen -- geen logboek, gewoon een aankondiging).
CREATE TABLE IF NOT EXISTS berichten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(150) NOT NULL,
    inhoud TEXT NOT NULL,
    auteur_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eigen wijzigingenlog voor MK Intranet, los van de 'versies'-tabel van
-- mkapp (die blijft alleen over mkapp gaan). Zelfde opzet: een regel die
-- begint met "## " is een groepskop, een regel met "- " is een bullet.
CREATE TABLE IF NOT EXISTS intranet_versies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    versienummer VARCHAR(20) NOT NULL,
    datum VARCHAR(50) NOT NULL,
    wijzigingen TEXT NOT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY versienummer_uniek (versienummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.1', '28 augustus 2026', '## Eerste versie
- Live overzicht van lopende meldingen (alleen-lezen) en crewbeheer (toevoegen/bewerken/verwijderen) op één dashboardpagina.
- Login met bestaande accounts van het meldkamersysteem (gedeelde gebruikers-tabel).
- Docker-opzet die aansluit op het Docker-netwerk van de meldkamerstack, zodat de database rechtstreeks bereikbaar is.'),
('V0.0.2', '28 augustus 2026', '## Database
- Verbinding met de database loopt nu via een vast IP-adres (testopstelling), in plaats van via het gedeelde Docker-netwerk.'),
('V0.0.3', '28 augustus 2026', '## Infrastructuur
- Draait voortaan in een eigen Proxmox-container; hostpoort aangepast van 8081 naar 80.'),
('V0.0.4', '28 augustus 2026', '## Beveiliging
- Databasewachtwoord staat niet meer in docker-compose.yml, maar in een lokaal .env-bestand dat buiten git-versiebeheer blijft.'),
('V0.0.5', '28 augustus 2026', '## Nieuw
- Crew heeft een eigen pagina gekregen, los van het dashboard.
- Nieuwe Berichten-pagina: beheerders kunnen mededelingen plaatsen, zichtbaar voor iedereen onder de lopende meldingen op het dashboard.
- Versiebeheer/wijzigingenlog onderaan de pagina, net als in het meldkamersysteem, zodat te zien is wat er per versie veranderd of toegevoegd is.');
