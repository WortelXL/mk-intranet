-- ============================================================
-- MK INTRANET - Migratie voor V0.1.23
-- Beginscherm: statuschips + snelkoppelingen + "nieuw in de kennisbank",
-- en 2 nieuwe kolommen op berichten voor "belangrijk" (vastpinnen) en
-- "geldig tot" (automatisch verlopen).
-- ============================================================

ALTER TABLE berichten
    ADD COLUMN belangrijk TINYINT(1) NOT NULL DEFAULT 0 AFTER inhoud,
    ADD COLUMN geldig_tot DATETIME DEFAULT NULL AFTER belangrijk;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.23', '15 september 2026', '## Nieuw
- Beginscherm: compacte statuschips bovenaan (actieve meldingen, attentie/kritiek, gepland vandaag, afgerond vandaag), "Dag X van Y", en een rij snelkoppelingen (Plotbord, Crew, Q&A).
- Berichten kunnen nu als "Belangrijk" gemarkeerd worden (verschijnt vastgepind bovenaan) en een "geldig tot"-datum krijgen, waarna ze vanzelf van het beginscherm verdwijnen (blijven zichtbaar in Beheer om te verlengen of op te ruimen).
- Nieuw blokje "Nieuw in de kennisbank" op het beginscherm: de 3 meest recent toegevoegde Q&A- en documentitems.');
