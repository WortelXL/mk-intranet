-- ============================================================
-- MK INTRANET - Migratie voor V0.0.7
-- Geen schemawijziging (alleen migratiescripts verplaatst naar migratie/).
-- Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.7', '28 augustus 2026', '## Opgeruimd
- Migratiescripts verplaatst naar een eigen map (migratie/) voor overzicht, in plaats van losse bestanden in de hoofdmap.');
