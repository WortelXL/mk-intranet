-- ============================================================
-- MK INTRANET - Migratie voor V0.1.7
-- Geen schemawijziging (leest alleen bestaande tabellen/instellingen,
-- o.a. event_start_datum en event_aantal_dagen die het meldkamersysteem
-- al gebruikt voor de dagnummering in het meld-ID). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.7', '4 september 2026', '## Nieuw
- Nieuwe pagina Statistieken, te vinden onder Meldingen in de navigatie: aantal meldingen per (sub)classificatie, verdeling per prioriteit en status, meldingen per evenementdag en gemiddelde doorlooptijd -- filterbaar op classificatie en evenementdag.

## Gewijzigd
- "Meldingen" in de navigatie is nu een submenu: Overview (de bestaande pagina, ongewijzigd) en Statistieken (nieuw).');
