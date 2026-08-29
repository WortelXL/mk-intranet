-- ============================================================
-- MK INTRANET - Migratie voor V0.0.9
-- Geen schemawijziging (labels/melding_labels bestaan al -- zelfde
-- gedeelde database als mkapp). Dit voegt alleen de wijzigingenlog-regel
-- toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.9', '29 augustus 2026', '## Nieuw
- Archief: filter op label toegevoegd, naast hoofdclassificatie en prioriteit. Labels worden ook per melding getoond.
- Archief: exporteren naar PDF, met dezelfde filters als op het scherm ingesteld.');
