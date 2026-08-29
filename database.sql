-- ============================================================
-- MELDKAMER SYSTEEM - Database schema
-- Importeer dit script in je (externe) MySQL/MariaDB database
-- voordat je de applicatie voor het eerst gebruikt.
-- ============================================================

-- Algemene, via het beheerpaneel aanpasbare instellingen (evenementnaam,
-- startdatum, aantal dagen). Ontbreekt een sleutel hier, dan valt de
-- applicatie terug op de waarde uit config.php.
CREATE TABLE IF NOT EXISTS instellingen (
    sleutel VARCHAR(50) PRIMARY KEY,
    waarde VARCHAR(255) NOT NULL,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Versiebeheer / wijzigingenlog, getoond via de knop in de footer en
-- beheerbaar via Beheer -> Instellingen. "wijzigingen" is vrije tekst:
-- een regel die begint met "## " is een groepskop, een regel met "- "
-- is een bullet-item.
CREATE TABLE IF NOT EXISTS versies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    versienummer VARCHAR(20) NOT NULL,
    datum VARCHAR(50) NOT NULL,
    wijzigingen TEXT NOT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY versienummer_uniek (versienummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO versies (versienummer, datum, wijzigingen) VALUES
('V1.3.5', '12 augustus 2026', '## Meldingen & werkproces
- Meldingen aanmaken met automatisch gegenereerd meld-ID (bv. MK-D2-014), classificatie, prioriteit en locatie.
- Titel wordt automatisch samengesteld uit de classificatie.
- Logboek per melding, met notities, tijdstip en auteur.
- Subtaken bij protocollen en losse taken per melding, beide aan te vinken met tijdstip/wie.
- Toegewezen aan (crew) en toegewezen centralist (gebruiker), beide met doorzoekbare dropdown.
- Labels om meldingen te markeren voor latere opvolging, ook in het archief.
- Statusgeschiedenis met doorlooptijden per status.
- Snelle statuswijziging direct vanaf het dashboard.
- Automatisch verversen (instelbaar) met optioneel geluidssignaal bij nieuwe meldingen.
## Classificatie, protocollen en locaties
- Tweeledige classificatie: hoofd- en subclassificatie, met eigen kleur.
- Protocollen met subtaken en tot 5 hyperlinks naar naslag/documenten.
- Protocollen automatisch koppelen aan een melding bij een matchende subclassificatie.
- Vooraf ingestelde locaties, op te roepen met ;locatienaam in tekstvelden.
- Eigen statussen aanmaken/aanpassen naast de 4 ingebouwde.
## Gebruikers en rechten
- Rollen: beheerder, medewerker en viewer (alleen Overview-toegang).
- Crew-lijst (contactpersonen zonder login) voor Toegewezen aan.
- Persoonlijke instellingen: verversingstijd en geluid.
## Overzicht en archief
- Dashboard, Overview (passief scherm) en Archief met filters op categorie/prioriteit/label.
- Export naar CSV en PDF, inclusief logboek, protocollen, subtaken, losse taken en doorlooptijden.
## Koppelingen
- API-endpoint voor het aanmaken van meldingen (bv. vanaf een Stream Deck), met token per beheerder-account.'),
('V1.3.6', '12 augustus 2026', '## Nieuw
- Filteren op prioriteit en hoofdclassificatie, ook op de Overview-pagina (was alleen dashboard/archief).
- Sorteren op prioriteit (standaard) of nieuwste melding bovenaan, op dashboard en Overview.
- Versiebeheer verplaatst naar de database, beheerbaar via Beheer -> Instellingen (in plaats van een los bestand).
## Reparaties
- Databasecontainer draaide op UTC in plaats van Europe/Amsterdam, waardoor tijdstippen 1-2 uur konden afwijken.
- Archief-tellingen en -leegmaken gebruikten nog een vaste statuslijst van vóór de eigen-statussen-functie, werkten daardoor niet correct met een zelf toegevoegde afgeronde status.
- SQL-fout op de Overview-pagina door een ontbrekende tabel-alias.'),
('V1.3.7', '12 augustus 2026', '## Nieuw
- Attentiesignaal: knop op de melddetailpagina om nadrukkelijk de aandacht te vragen voor een melding.
- Bij een attentiesignaal verschijnt ⚠️ voor het meld-ID op dashboard, Overview en archief, tot iemand het weer uitzet.
- Eigen belgeluid voor een attentiesignaal (dezelfde toon, twee keer), duidelijk anders dan het geluid bij een nieuwe melding.'),
('V1.3.8', '12 augustus 2026', '## Nieuw
- Backup & Restore onder Beheer: crew, classificaties, statussen, protocollen, locaties en labels exporteren als .json-bestand, met aanvinkvakjes voor wat je wel/niet meeneemt.
- Bestand weer importeren om dezelfde configuratie snel bij een nieuw evenement te herstellen, zonder alles opnieuw in te vullen.
- Classificaties, statussen, locaties en labels worden bij import herkend op naam en overgeslagen als ze al bestaan (dus veilig om dezelfde backup meerdere keren te importeren). Protocollen en crew worden altijd als nieuw toegevoegd.'),
('V1.3.9', '12 augustus 2026', '## Nieuw
- Connectiviteit onder Beheer: uitgaande webhooks naar externe applicaties (bv. Slack, Teams, een eigen systeem).
- Webhook afgaan op zelf gekozen gebeurtenissen: nieuwe melding aangemaakt, status gewijzigd, en/of attentiesignaal gegeven.
- Testknop per webhook om de koppeling meteen te controleren, met status en laatste foutmelding zichtbaar in het overzicht.'),
('V1.4.0', '13 augustus 2026', '## Nieuw
- Gekoppelde meldingen: meldingen aan elkaar koppelen (bv. een EHBO-inzet die een AMBU-inzet oplevert), meerdere tegelijk mogelijk.
- Koppeling met type: "is vervolg van" of "is gerelateerd aan" — op de andere melding zie je automatisch het passende omgekeerde label.
- Sneltoets "+ Vervolgmelding aanmaken": opent het aanmaakformulier met locatie al ingevuld en koppelt automatisch terug naar de melding waar je vandaan kwam.
- 🔗-icoon voor het meld-ID op dashboard, Overview en archief zodra een melding ergens aan gekoppeld is.
- Gekoppelde meldingen blijven zelfstandig (eigen status, protocol, logboek, doorlooptijden) — koppelen beïnvloedt elkaars status niet.
- Nieuwe pagina "Samengevoegd" (naast Dashboard) met alle koppelingen waar minstens 1 melding nog actief is, met direct kunnen loskoppelen.'),
('V1.4.2', '14 augustus 2026', '## Nieuw
- Discord-ondersteuning voor webhooks: platformkeuze (Generiek/Discord) bij het aanmaken van een webhook.
- Bij Discord wordt automatisch een nette kaart (embed) opgebouwd — titel, beschrijving en velden per gebeurtenis, met een kleur die past bij het type bericht — in plaats van de ruwe generieke JSON.
- Testknop werkt ook voor Discord: stuurt een simpel tekstbericht ter controle.'),
('V1.4.3.1', '15 augustus 2026', '## Verbeterd
- "Wat is er nieuw"-pop-up: de nieuwste versie blijft uitgeklapt zichtbaar, oudere versies staan nu standaard ingeklapt met een schakelaartje, zodat de pop-up overzichtelijker blijft naarmate het wijzigingenlog groeit.
- Zelfde inklapbare weergave ook toegepast op Beheer > Instellingen > Versiebeheer, met meteen een voorbeeld van de inhoud per versie zonder eerst op Bewerken te hoeven klikken.'),
('V1.4.4', '25 augustus 2026', '## Nieuw
- Dashboard: filteren op meldingen die aan een specifieke centralist zijn toegewezen (of "Aan mij toegewezen" als sneltoets).
- Nieuwe tegel "Aan mij toegewezen" in het statistiekenoverzicht, klikbaar om direct te filteren.
## Verbeterd
- Statistieken- en filterblok op het dashboard lijnen nu netjes gelijk uit qua hoogte.'),
('V1.4.5', '25 augustus 2026', '## Verbeterd
- Statistieken- en filterblok op het dashboard compacter gemaakt: minder padding, kleinere tekst/getallen, minder ruimte tussen de filtervelden.'),
('V1.4.6', '25 augustus 2026', '## Verbeterd
- Dashboard-lay-out overgenomen van de Overview-pagina: een volledige-breedte tegelbalk met statistieken, en een horizontale filterbalk eronder — merkbaar compacter dan de vorige verticale lijst.
## Reparaties
- Stylesheet (assets/style.css) kreeg geen cache-buster mee, waardoor stijlupdates soms niet zichtbaar werden zonder de browsercache handmatig te legen. Vanaf nu automatisch gekoppeld aan het versienummer.'),
('V1.4.7', '25 augustus 2026', '## Verbeterd
- Filters op het dashboard zijn nu inklapbaar via een schakelaartje ("Filters"), voor nog minder ruimtegebruik.
- Standaard ingeklapt, tenzij er al een filter actief staat — dan blijft meteen zichtbaar wat er precies gefilterd wordt, met een "Actieve filters"-label erbij.
- Onthoudt de open/dicht-stand net als andere schakelaartjes in de app, tot je ''m zelf weer omzet.'),
('V1.4.8', '25 augustus 2026', '## Nieuw
- Nieuwe beheerpagina "Geplande meldingen": meldingen inplannen voor een toekomstig tijdstip, met classificatie, subclassificatie, locatie, prioriteit en omschrijving.
- Optioneel automatisch een attentiesignaal (belgeluid + ⚠️) zodra de geplande melding verschijnt.
- Werkt zonder achtergrondproces/cron: het systeem checkt bij elke paginabezoek van een ingelogde gebruiker of er geplande meldingen zijn waarvan de tijd verstreken is.
- Overzicht van wachtende, verwerkte en geannuleerde geplande meldingen, met directe link naar de uiteindelijke melding.'),
('V1.4.8.1', '25 augustus 2026', '## Reparaties
- Niet-geescapete apostrof in de wijzigingenlogtekst van V1.4.7 brak de SQL-syntax van migratie_versies.sql (foutmelding ERROR 1064 bij het importeren). Alle apostroffen in de wijzigingenlogteksten zijn nu correct geescaped (verdubbeld), zodat het bestand weer foutloos draait.'),
('V1.4.8.2', '25 augustus 2026', '## Nieuw
- Geplande meldingen kunnen nu herhaald worden: dagelijks op hetzelfde tijdstip, of elke X uur of minuten, tot een zelf ingestelde einddatum.
- Elke herhaling maakt een nieuwe, aparte melding aan met een eigen meld-ID en logboek (geen doorlopende melding).
- Overzicht toont per geplande melding hoe vaak deze al is verschenen.
- Meerdere losse tijdstippen tegelijk inplannen bij het aanmaken (bv. 13:00, 15:30 en 18:00): elk tijdstip wordt een eigen, onafhankelijke geplande melding met dezelfde inhoud.'),
('V1.4.9', '25 augustus 2026', '## Nieuw
- Eigen -commando-aliassen per subclassificatie, naast de naam zelf (bv. -hartstilstand naast -reanimatie), meerdere per subclassificatie mogelijk.
- Beheerbaar via Beheer > Classificaties, direct bij elke subclassificatie.
- Werken overal waar het bestaande -commando al gebruikt werd: het zoekveld op dashboard en archief, en het classificatie-veld van de Stream Deck-API.');

-- Statussen van een melding. De 4 ingebouwde (open/in_behandeling/
-- afgehandeld/geannuleerd) zijn niet verwijderbaar (anders raken bestaande
-- meldingen/logica zonder geldige status), maar wel aan te passen (naam,
-- kleur, categorie). Eigen, extra statussen kunnen gewoon toegevoegd en
-- verwijderd worden. "categorie" bepaalt of een status meetelt als actief
-- (zichtbaar op dashboard/Overview) of afgerond (zichtbaar in archief).
CREATE TABLE IF NOT EXISTS statussen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sleutel VARCHAR(50) NOT NULL UNIQUE,
    naam VARCHAR(100) NOT NULL,
    kleur VARCHAR(7) NOT NULL DEFAULT '#6b7280',
    categorie ENUM('actief','afgerond') NOT NULL DEFAULT 'actief',
    ingebouwd TINYINT(1) NOT NULL DEFAULT 0,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO statussen (sleutel, naam, kleur, categorie, ingebouwd, volgorde) VALUES
    ('open', 'Open', '#ef4444', 'actief', 1, 1),
    ('in_behandeling', 'In behandeling', '#f5a524', 'actief', 1, 2),
    ('afgehandeld', 'Afgehandeld', '#22c55e', 'afgerond', 1, 3),
    ('geannuleerd', 'Geannuleerd', '#6b7280', 'afgerond', 1, 4)
ON DUPLICATE KEY UPDATE sleutel = sleutel;

CREATE TABLE IF NOT EXISTS gebruikers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruikersnaam VARCHAR(50) NOT NULL UNIQUE,
    wachtwoord_hash VARCHAR(255) NOT NULL,
    naam VARCHAR(100) NOT NULL,
    rol ENUM('beheerder','medewerker','view') NOT NULL DEFAULT 'medewerker',
    functie VARCHAR(100) DEFAULT NULL,
    actief TINYINT(1) NOT NULL DEFAULT 1,
    api_token VARCHAR(64) DEFAULT NULL UNIQUE,
    auto_refresh_seconden INT NOT NULL DEFAULT 20,
    geluid_nieuwe_melding TINYINT(1) NOT NULL DEFAULT 1,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uitgaande webhooks: stuurt een JSON-melding (POST) naar een externe URL
-- bij gekozen gebeurtenissen (nieuwe melding, statuswijziging,
-- attentiesignaal). "events" is een JSON-array met de gekozen
-- gebeurtenis-sleutels, bv. ["melding_aangemaakt","attentie"].
-- Geplande meldingen: worden pas een echte melding op het ingestelde
-- tijdstip. Er is geen achtergrondproces (cron) -- het systeem checkt
-- bij elke paginabezoek van een ingelogde gebruiker (zie
-- verwerk_geplande_meldingen() in functions.php, aangeroepen vanuit
-- header.php) of er geplande meldingen zijn waarvan de tijd verstreken
-- is, en zet die dan om in een echte melding.
CREATE TABLE IF NOT EXISTS geplande_meldingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoofdclassificatie_id INT DEFAULT NULL,
    subclassificatie_id INT DEFAULT NULL,
    locatie VARCHAR(150) DEFAULT NULL,
    prioriteit ENUM('laag','normaal','hoog','kritiek') NOT NULL DEFAULT 'normaal',
    gemeld_door VARCHAR(100) DEFAULT NULL,
    omschrijving TEXT,
    attentie TINYINT(1) NOT NULL DEFAULT 1,
    geplande_tijd DATETIME NOT NULL,
    herhaling_type ENUM('geen','dagelijks','interval_uren','interval_minuten') NOT NULL DEFAULT 'geen',
    herhaling_interval INT DEFAULT NULL,
    herhaling_tot DATETIME DEFAULT NULL,
    keer_verwerkt INT NOT NULL DEFAULT 0,
    status ENUM('wachtend','verwerkt','geannuleerd') NOT NULL DEFAULT 'wachtend',
    verwerkte_melding_id INT DEFAULT NULL,
    aangemaakt_door_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hoofdclassificatie_id) REFERENCES hoofdclassificaties(id) ON DELETE SET NULL,
    FOREIGN KEY (subclassificatie_id) REFERENCES subclassificaties(id) ON DELETE SET NULL,
    FOREIGN KEY (verwerkte_melding_id) REFERENCES meldingen(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    platform VARCHAR(20) NOT NULL DEFAULT 'generiek',
    events TEXT NOT NULL,
    actief TINYINT(1) NOT NULL DEFAULT 1,
    laatst_verzonden_op DATETIME DEFAULT NULL,
    laatste_status VARCHAR(20) DEFAULT NULL,
    laatste_foutmelding VARCHAR(255) DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crew: contactpersonen (naam, functie, telefoonnummer) die geen account
-- of login hebben, maar wel aan een melding toegewezen kunnen worden
-- (via "Toegewezen aan"). Een soort telefoonlijst, geen gebruikers.
CREATE TABLE IF NOT EXISTS crew (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    functie VARCHAR(100) DEFAULT NULL,
    telefoonnummer VARCHAR(30) DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vooraf ingestelde locaties. Kunnen in de omschrijving (bij een nieuwe
-- melding) of in een notitie opgeroepen worden met ";naam", waarna het
-- locatieveld van de melding automatisch wordt bijgewerkt bij een match.
CREATE TABLE IF NOT EXISTS locaties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(150) NOT NULL UNIQUE,
    beschrijving VARCHAR(255) DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoofdclassificatie, bv. "Medisch", "Beveiliging", "Techniek"
-- Labels om meldingen te markeren voor latere opvolging, onafhankelijk van
-- de status (werkt dus zowel op actieve als afgesloten/gearchiveerde
-- meldingen). Zie melding_labels hieronder voor de koppeling.
CREATE TABLE IF NOT EXISTS labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL UNIQUE,
    kleur VARCHAR(7) NOT NULL DEFAULT '#f5a524',
    beschrijving VARCHAR(255) DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hoofdclassificaties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    kleur VARCHAR(7) NOT NULL DEFAULT '#f5a524',
    beschrijving VARCHAR(255) DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subclassificatie, hangt altijd onder precies 1 hoofdclassificatie,
-- bv. hoofdclassificatie "Medisch" -> subclassificatie "Reanimatie".
-- standaard_prioriteit wordt als voorstel getoond bij het aanmaken van
-- een nieuwe melding met deze subclassificatie (blijft aanpasbaar).
CREATE TABLE IF NOT EXISTS subclassificaties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoofdclassificatie_id INT NOT NULL,
    naam VARCHAR(100) NOT NULL,
    beschrijving VARCHAR(255) DEFAULT NULL,
    standaard_prioriteit ENUM('laag','normaal','hoog','kritiek') DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hoofdclassificatie_id) REFERENCES hoofdclassificaties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eigen "-commando"-aliassen per subclassificatie (naast de naam zelf),
-- bv. subclassificatie "Reanimatie" met commando's "reanimatie" en
-- "hartstilstand". Gebruikt bij het zoekcommando -commando op dashboard/
-- archief en bij het classificatie-veld van de Stream Deck-API.
CREATE TABLE IF NOT EXISTS subclassificatie_commandos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subclassificatie_id INT NOT NULL,
    commando VARCHAR(50) NOT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subclassificatie_id) REFERENCES subclassificaties(id) ON DELETE CASCADE,
    UNIQUE KEY commando_uniek (commando)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS protocollen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(150) NOT NULL,
    subclassificatie_id INT DEFAULT NULL,
    inhoud TEXT NOT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subclassificatie_id) REFERENCES subclassificaties(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subtaken binnen een protocol, bv. protocol "Reanimatie" -> subtaak
-- "AED gehaald", "112 gebeld". Worden per melding afzonderlijk afgevinkt
-- (zie melding_subtaak_status hieronder).
CREATE TABLE IF NOT EXISTS protocol_subtaken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol_id INT NOT NULL,
    omschrijving VARCHAR(255) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (protocol_id) REFERENCES protocollen(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Externe verwijzingen (hyperlinks) bij een protocol, bv. naar een
-- draaiboek, plattegrond of ander naslagdocument. Knoptekst is per protocol
-- vrij te kiezen. Maximaal 5 per protocol (afgedwongen in de beheer-UI,
-- niet in de database).
CREATE TABLE IF NOT EXISTS protocol_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocol_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (protocol_id) REFERENCES protocollen(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS meldingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meld_id VARCHAR(20) NOT NULL UNIQUE,
    titel VARCHAR(150) NOT NULL,
    omschrijving TEXT,
    hoofdclassificatie_id INT DEFAULT NULL,
    subclassificatie_id INT DEFAULT NULL,
    locatie VARCHAR(150) DEFAULT NULL,
    prioriteit ENUM('laag','normaal','hoog','kritiek') NOT NULL DEFAULT 'normaal',
    status VARCHAR(50) NOT NULL DEFAULT 'open',
    gemeld_door VARCHAR(100) DEFAULT NULL,
    toegewezen_aan VARCHAR(100) DEFAULT NULL,
    toegewezen_aan_gebruiker_id INT DEFAULT NULL,
    toegewezen_aan_crew_id INT DEFAULT NULL,
    toegewezen_centralist_id INT DEFAULT NULL,
    attentie TINYINT(1) NOT NULL DEFAULT 0,
    attentie_door_id INT DEFAULT NULL,
    attentie_op DATETIME DEFAULT NULL,
    aangemaakt_door_id INT DEFAULT NULL,
    bijgewerkt_door_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hoofdclassificatie_id) REFERENCES hoofdclassificaties(id) ON DELETE SET NULL,
    FOREIGN KEY (subclassificatie_id) REFERENCES subclassificaties(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    FOREIGN KEY (bijgewerkt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    FOREIGN KEY (toegewezen_aan_gebruiker_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    FOREIGN KEY (toegewezen_aan_crew_id) REFERENCES crew(id) ON DELETE SET NULL,
    FOREIGN KEY (toegewezen_centralist_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    FOREIGN KEY (attentie_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS melding_protocollen (
    melding_id INT NOT NULL,
    protocol_id INT NOT NULL,
    gekoppeld_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (melding_id, protocol_id),
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (protocol_id) REFERENCES protocollen(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS melding_labels (
    melding_id INT NOT NULL,
    label_id INT NOT NULL,
    gekoppeld_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (melding_id, label_id),
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (label_id) REFERENCES labels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS melding_notities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    melding_id INT NOT NULL,
    notitie TEXT NOT NULL,
    auteur VARCHAR(100) DEFAULT NULL,
    gebruiker_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Afvinkstatus van een protocol-subtaak, per melding (dezelfde subtaak kan
-- op meerdere meldingen los van elkaar worden afgevinkt)
CREATE TABLE IF NOT EXISTS melding_subtaak_status (
    melding_id INT NOT NULL,
    subtaak_id INT NOT NULL,
    afgevinkt TINYINT(1) NOT NULL DEFAULT 0,
    afgevinkt_door_id INT DEFAULT NULL,
    afgevinkt_op DATETIME DEFAULT NULL,
    PRIMARY KEY (melding_id, subtaak_id),
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (subtaak_id) REFERENCES protocol_subtaken(id) ON DELETE CASCADE,
    FOREIGN KEY (afgevinkt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Logt elke status die een melding ooit heeft gehad (incl. de status bij
-- aanmaken), met tijdstip. Zo is te herleiden hoe lang een melding in elke
-- status heeft gestaan (bv. in het PDF-/CSV-exportrapport).
CREATE TABLE IF NOT EXISTS melding_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    melding_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    gebruiker_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Koppelt meldingen aan elkaar (bv. een EHBO-inzet die een AMBU-inzet
-- oplevert). Blijven allebei zelfstandige meldingen (eigen status,
-- protocol, logboek) -- dit is puur een zichtbare, getypeerde relatie.
-- "type" is gericht: melding_id [type] gekoppelde_melding_id, bv.
-- "melding_id is vervolg van gekoppelde_melding_id". Bij weergave op de
-- andere melding wordt het omgekeerde label getoond (zie
-- koppeling_types() in functions.php).
CREATE TABLE IF NOT EXISTS melding_koppelingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    melding_id INT NOT NULL,
    gekoppelde_melding_id INT NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'gerelateerd',
    aangemaakt_door_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (gekoppelde_melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (aangemaakt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    UNIQUE KEY unieke_koppeling (melding_id, gekoppelde_melding_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Losse taken: een eenvoudig to-do-lijstje per melding, los van elk
-- protocol. Handig voor iets dat uniek is voor deze ene melding, in
-- tegenstelling tot protocol-subtaken die bij het protocol zelf horen.
CREATE TABLE IF NOT EXISTS melding_taken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    melding_id INT NOT NULL,
    omschrijving VARCHAR(255) NOT NULL,
    afgevinkt TINYINT(1) NOT NULL DEFAULT 0,
    afgevinkt_door_id INT DEFAULT NULL,
    afgevinkt_op DATETIME DEFAULT NULL,
    aangemaakt_door_id INT DEFAULT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (melding_id) REFERENCES meldingen(id) ON DELETE CASCADE,
    FOREIGN KEY (afgevinkt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enkele voorbeeld hoofd- en subclassificaties
-- (mag je aanpassen/verwijderen via het beheerpaneel)
INSERT INTO hoofdclassificaties (naam, kleur, beschrijving) VALUES
    ('Medisch', '#ef4444', 'EHBO- en medische incidenten'),
    ('Beveiliging', '#f5a524', 'Overlast, agressie, diefstal'),
    ('Techniek', '#3b82f6', 'Stroom, geluid, licht, constructies'),
    ('Logistiek', '#22c55e', 'Bevoorrading, verkeer, parkeren')
ON DUPLICATE KEY UPDATE naam = naam;

INSERT INTO subclassificaties (hoofdclassificatie_id, naam, beschrijving)
SELECT id, sub.naam, sub.beschrijving FROM hoofdclassificaties
JOIN (
    SELECT 'Medisch' AS hoofd, 'Reanimatie' AS naam, NULL AS beschrijving
    UNION ALL SELECT 'Medisch', 'EHBO klein letsel', NULL
    UNION ALL SELECT 'Medisch', 'Uitval / onwel', NULL
    UNION ALL SELECT 'Beveiliging', 'Agressie', NULL
    UNION ALL SELECT 'Beveiliging', 'Diefstal', NULL
    UNION ALL SELECT 'Beveiliging', 'Vermist persoon', NULL
    UNION ALL SELECT 'Techniek', 'Stroomuitval', NULL
    UNION ALL SELECT 'Techniek', 'Geluid / licht', NULL
    UNION ALL SELECT 'Logistiek', 'Bevoorrading', NULL
    UNION ALL SELECT 'Logistiek', 'Verkeer / parkeren', NULL
) AS sub ON sub.hoofd = hoofdclassificaties.naam;

-- ============================================================
-- MK INTRANET - eigen tabellen (niet gebruikt door mkapp)
-- ============================================================

-- Mededelingen die beheerders plaatsen op het intranet-dashboard.
CREATE TABLE IF NOT EXISTS berichten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(150) NOT NULL,
    inhoud TEXT NOT NULL,
    url VARCHAR(500) DEFAULT NULL,
    auteur_id INT DEFAULT NULL,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES gebruikers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eigen wijzigingenlog voor MK Intranet, los van de 'versies'-tabel van mkapp.
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
- Versiebeheer/wijzigingenlog onderaan de pagina, net als in het meldkamersysteem, zodat te zien is wat er per versie veranderd of toegevoegd is.'),
('V0.0.6', '28 augustus 2026', '## Rechten
- Medewerkers (centralisten) en viewers kunnen de crewlijst alleen nog bekijken, niet meer bewerken.
- Toevoegen, bewerken en verwijderen van crew is voortaan voorbehouden aan beheerders, net als in het meldkamersysteem.'),
('V0.0.7', '28 augustus 2026', '## Opgeruimd
- Migratiescripts verplaatst naar een eigen map (migratie/) voor overzicht, in plaats van losse bestanden in de hoofdmap.'),
('V0.0.8', '29 augustus 2026', '## Nieuw
- Archiefpagina toegevoegd: alleen-lezen overzicht van afgeronde meldingen, met filters op hoofdclassificatie en prioriteit.'),
('V0.0.9', '29 augustus 2026', '## Nieuw
- Archief: filter op label toegevoegd, naast hoofdclassificatie en prioriteit. Labels worden ook per melding getoond.
- Archief: exporteren naar PDF, met dezelfde filters als op het scherm ingesteld.'),
('V0.0.9.1', '29 augustus 2026', '## Nieuw
- Archief: meldingen kunnen nu individueel aangevinkt worden om alleen die selectie naar PDF te exporteren, naast de bestaande "exporteer alles binnen de huidige filters".'),
('V0.1.0', '29 augustus 2026', '## Nieuw
- Dashboard: bij elke actieve melding een vinkje "Laat log zien" om het logboek (de notities) van die melding in te klappen, alleen-lezen.'),
('V0.1.1', '29 augustus 2026', '## Nieuw
- Beheer-knop toegevoegd in de navigatie: verzamelt Berichten beheren en het nieuwe Gebruikers beheren op één plek.
- Gebruikersbeheer: accounts aanmaken, rol/functie/wachtwoord wijzigen, activeren/deactiveren. Zelfde gedeelde inlogtabel als het meldkamersysteem, met dezelfde beveiligingen (o.a. altijd minstens één actieve beheerder, geen eigen account verwijderen).
- Berichten kunnen nu een optionele URL bevatten (bv. een link naar een draaiboek of externe pagina), zichtbaar op het dashboard en in Berichten beheren.');
