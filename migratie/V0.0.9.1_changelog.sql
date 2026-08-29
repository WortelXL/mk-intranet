-- ============================================================
-- MK INTRANET - Migratie voor V0.0.9.1
-- Geen schemawijziging. Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.9.1', '29 augustus 2026', '## Nieuw
- Archief: meldingen kunnen nu individueel aangevinkt worden om alleen die selectie naar PDF te exporteren, naast de bestaande "exporteer alles binnen de huidige filters".');
