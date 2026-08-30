-- ============================================================
-- MK INTRANET - Migratie voor V0.1.4
-- Geen schemawijziging (auto_refresh_seconden bestond al op gebruikers,
-- zelfde kolom als het meldkamersysteem gebruikt). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.4', '30 augustus 2026', '## Nieuw
- Automatisch verversen is nu zichtbaar en instelbaar onder je eigen naam rechtsboven, per gebruiker (dezelfde instelling als in het meldkamersysteem).

## Gewijzigd
- Lopende meldingen zijn verplaatst van het dashboard naar een eigen pagina "Meldingen" in de navigatie. Het dashboard toont voortaan alleen nog de berichten.
- Een geopend logboek bij een melding blijft open staan als de pagina ververst (handmatig of automatisch), tot je het zelf weer dichtklikt.');
