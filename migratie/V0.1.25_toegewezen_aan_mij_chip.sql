-- ============================================================
-- MK INTRANET - Migratie voor V0.1.25
-- Geen schemawijziging -- toegewezen_centralist_id bestond al op
-- meldingen. Alleen de wijzigingenlog-regel.
--
-- Wat er verandert:
-- 1. Beginscherm: nieuwe statuschip "Toegewezen aan mij" (voor iedereen
--    zichtbaar), telt actieve meldingen waar jij als centralist op
--    staat. Linkt naar Overview met het nieuwe filter "Alleen aan mij
--    toegewezen".
-- 2. De "Gepland vandaag"-chip is voortaan alleen nog zichtbaar voor
--    beheerders (die pagina is toch al beheerder-only, dus voor een
--    centralist was het sowieso een doodlopende link).
-- 3. Overview (meldingen.php) heeft een nieuw filter "Alleen aan mij
--    toegewezen", naast het bestaande hoofdclassificatie-filter.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.25', '15 september 2026', '## Nieuw
- Beginscherm: nieuwe statuschip "Toegewezen aan mij" (voor iedereen), telt je eigen actieve toegewezen meldingen en linkt naar een alvast gefilterde Overview.
- Overview heeft een nieuw filter "Alleen aan mij toegewezen", naast het bestaande classificatiefilter.

## Gewijzigd
- De "Gepland vandaag"-chip op het beginscherm is nu alleen nog zichtbaar voor beheerders (Geplande meldingen is toch al een beheerder-only pagina).');
