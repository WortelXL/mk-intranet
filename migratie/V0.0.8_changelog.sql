-- ============================================================
-- MK INTRANET - Migratie voor V0.0.8
-- Geen schemawijziging (archief leest bestaande tabellen: meldingen,
-- hoofdclassificaties, subclassificaties, statussen). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.8', '29 augustus 2026', '## Nieuw
- Archiefpagina toegevoegd: alleen-lezen overzicht van afgeronde meldingen, met filters op hoofdclassificatie en prioriteit.');
