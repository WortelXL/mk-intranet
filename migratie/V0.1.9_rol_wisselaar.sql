-- ============================================================
-- MK INTRANET - Migratie voor V0.1.9
-- Geen schemawijziging: alleen de weergave van de rol-wisselaar in de
-- navigatie is aangepast (CSS/HTML). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.9', '4 september 2026', '## Gewijzigd
- Rol-wisselaar in de navigatie: de rol-badge naast je naam is nu zelf de wisselaar (bij 2 of meer toegewezen rollen), in plaats van een badge met daarnaast nog een aparte keuzelijst.');
