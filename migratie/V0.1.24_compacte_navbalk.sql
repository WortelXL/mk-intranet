-- ============================================================
-- MK INTRANET - Migratie voor V0.1.24
-- Geen schemawijziging -- alleen de wijzigingenlog-regel.
--
-- Wat er verandert:
-- 1. De navigatiebalk is compacter gemaakt (kleinere padding/lettergrootte
--    op de navlinks, user-chip en rol-badge/-wisselaar) zodat alles weer
--    op 1 regel past. "Uitloggen" is een klein rond icoon geworden i.p.v.
--    tekst (met tooltip), dat scheelde het meest.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.24', '15 september 2026', '## Gewijzigd
- Navigatiebalk compacter gemaakt zodat alles weer op 1 regel past: kleinere padding/lettergrootte op de navlinks, de naam-link en de rol-badge/-wisselaar (die laatste ook nooit meer breder dan een vaste maximumbreedte). "Uitloggen" is nu een klein rond icoon met tooltip in plaats van tekst.');
