-- ============================================================
-- MK INTRANET - Migratie voor V0.1.4.2
-- Geen schemawijziging. Verbetert de automatische verversing op de
-- Meldingen-pagina zodat die zich net zo gedraagt als in het
-- meldkamersysteem. Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.4.2', '30 augustus 2026', '## Verbeterd
- Automatisch verversen op de Meldingen-pagina werkt nu net als in het meldkamersysteem: het blijft doorlopen op het ingestelde interval (niet nog maar één keer), pauzeert vanzelf zodra het tabblad niet actief in beeld is, en de pagina springt niet meer naar boven bij het verversen.
- De keuzelijst voor het interval is gelijkgetrokken met het meldkamersysteem: Uit, 10s, 15s, 20s, 30s of 60s.');
