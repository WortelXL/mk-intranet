-- ============================================================
-- MK INTRANET - Migratie voor V0.1.13
-- Geen schemawijziging: puur CSS (kleinere teamkaartjes op het
-- Plotbord). Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.13', '6 september 2026', '## Gewijzigd
- Teamkaartjes op het Plotbord zijn compacter: kleinere kaartjes en kleinere tekst, zodat er meer teams naast elkaar passen op 1 rij (6 in plaats van 5 op een volle breedte).');
