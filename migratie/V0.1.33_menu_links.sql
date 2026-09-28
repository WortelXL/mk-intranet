-- MK INTRANET - Migratie voor V0.1.33
--
-- Eigen hyperlinks in de navigatiebalk (Beheer > Menu), zelfde idee als
-- "Beheer > Menu" in mkapp -- maar met een eigen tabel (intranet_menu_
-- items/intranet_menu_item_rollen) i.p.v. mkapp's menu_items/
-- menu_item_rollen. Bewust gescheiden: dit is dezelfde gedeelde
-- database, en een link die hier wordt toegevoegd hoort alleen in de
-- navigatie van MK Intranet te verschijnen, niet (ongewild) ook in
-- mkapp of andersom. Zelfde 1-app-beheert-het-zelf-principe als
-- Kennisbank/Berichten/Rollen/Teams.
--
-- Alleen "eigen" links (intern of extern, optioneel genest onder
-- Meldingen of Event, optioneel gekoppeld aan 1 of meer rollen -- geen
-- koppeling = zichtbaar voor iedereen die al bij het hoofdmenu mag). De
-- ingebouwde onderdelen (Dashboard, Meldingen, Crew, Event, Beheer, ...)
-- blijven gewoon vaste code in includes/header.php.
--
-- Idempotent: veilig om dit bestand meerdere keren te draaien.

CREATE TABLE IF NOT EXISTS intranet_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_sleutel VARCHAR(50) DEFAULT NULL,
    naam VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    nieuw_tab TINYINT(1) NOT NULL DEFAULT 0,
    zichtbaar TINYINT(1) NOT NULL DEFAULT 1,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intranet_menu_item_rollen (
    menu_item_id INT NOT NULL,
    rol_id INT NOT NULL,
    toegewezen_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (menu_item_id, rol_id),
    FOREIGN KEY (menu_item_id) REFERENCES intranet_menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (rol_id) REFERENCES rollen(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.33', '28 september 2026', '## Nieuw
- Beheer > Menu: eigen hyperlinks toevoegen aan de navigatiebalk (intern
  of extern), los in het hoofdmenu of genest onder Meldingen/Event.
  Optioneel per link instellen voor welke rol(len) die zichtbaar is --
  geen koppeling = zichtbaar voor iedereen. Zelfde mogelijkheid als
  Beheer > Menu in mkapp.');
