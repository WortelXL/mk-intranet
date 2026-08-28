-- ============================================================
-- MK INTRANET - Migratie voor V0.0.6
-- Geen schemawijziging nodig (alleen een rechtenwijziging in de PHP-code).
-- Dit voegt alleen de wijzigingenlog-regel toe zodat de versieknop
-- onderaan de pagina 'm laat zien.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.0.6', '28 augustus 2026', '## Rechten
- Medewerkers (centralisten) en viewers kunnen de crewlijst alleen nog bekijken, niet meer bewerken.
- Toevoegen, bewerken en verwijderen van crew is voortaan voorbehouden aan beheerders, net als in het meldkamersysteem.');
