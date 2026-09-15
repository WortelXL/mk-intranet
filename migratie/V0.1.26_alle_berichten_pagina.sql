-- ============================================================
-- MK INTRANET - Migratie voor V0.1.26
-- Geen schemawijziging -- alleen de wijzigingenlog-regel.
--
-- Wat er verandert:
-- 1. Beginscherm toont voortaan alleen de 3 meest recente berichten
--    (was: tot 20).
-- 2. Nieuwe pagina "Berichten" (alle_berichten.php), bereikbaar via
--    Event in de navigatie, toont alle berichten inclusief eerder
--    geplaatste/verlopen. Alleen-lezen, voor iedereen die ingelogd
--    is. Beheren blijft via berichten.php (beheerder-only).
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.26', '15 september 2026', '## Nieuw
- Nieuwe pagina "Berichten" (via Event in de navigatie) toont alle berichten, inclusief eerder geplaatste en verlopen berichten.

## Gewijzigd
- Het beginscherm toont voortaan alleen de 3 meest recente berichten, met een link "Alle berichten" naar de nieuwe overzichtspagina.');
