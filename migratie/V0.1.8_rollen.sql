-- ============================================================
-- MK INTRANET - Migratie voor V0.1.8
-- Geen schemawijziging vanuit dit project: de tabellen `rollen` en
-- `gebruiker_rollen` zijn al aangemaakt op de gedeelde database via het
-- meldkamersysteem-project (rollensysteem, incl. de 3 kernrollen Admin/
-- Centralist/Viewer). MK Intranet leest/schrijft hier alleen dezelfde
-- tabellen -- zelfde opzet als de mag_inloggen_mkapp/mag_inloggen_mkintranet-
-- koppeling in V0.1.6.
--
-- Belangrijk: draai dit pas NADAT de migratie die deze tabellen aanmaakt
-- (vanuit het meldkamersysteem-project) al is uitgevoerd op deze
-- database -- anders bestaan de tabellen nog niet en breken de
-- rollenpagina, de rol-wisselaar en de gefilterde weergave.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.8', '4 september 2026', '## Nieuw
- Rollen overgenomen van het meldkamersysteem: onder Beheer -> Rollen kunnen benoemde rollen aangemaakt worden, elk met een niveau (Beheerder/Medewerker/Viewer) en optioneel een gekoppelde hoofdclassificatie.
- Heeft een account 2 of meer rollen toegewezen gekregen, dan verschijnt rechtsboven in de navigatie een keuzelijst om de actieve rol te wisselen -- die bepaalt de rechten, en bij een gekoppelde hoofdclassificatie een eigen gefilterde weergave (alleen die classificatie op Overview, verder geen toegang tot Dashboard, Statistieken, Archief, Crew of Beheer).
- Beheer -> Gebruikers heeft een kolom "Rollen" gekregen om deze (nieuwe) rollen per account toe te wijzen, naast de bestaande klassieke rol.');
