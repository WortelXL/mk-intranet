-- ============================================================
-- MK INTRANET - Migratie voor V0.1.0
-- Geen schemawijziging (leest de bestaande tabel melding_notities, die
-- ook door het meldkamersysteem al gevuld wordt). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.0', '29 augustus 2026', '## Nieuw
- Dashboard: bij elke actieve melding een vinkje "Laat log zien" om het logboek (de notities) van die melding in te klappen, alleen-lezen.');
