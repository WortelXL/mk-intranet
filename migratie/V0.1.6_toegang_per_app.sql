-- ============================================================
-- MK INTRANET - Migratie voor V0.1.6
-- Geen schemawijziging vanuit dit project: de kolommen
-- mag_inloggen_mkapp en mag_inloggen_mkintranet zijn al toegevoegd aan de
-- gedeelde `gebruikers`-tabel via het meldkamersysteem-project. MK
-- Intranet leest/schrijft hier alleen dezelfde kolomnamen. Dit voegt
-- alleen de wijzigingenlog-regel toe.
--
-- Belangrijk: draai dit pas NADAT de migratie die deze kolommen aanmaakt
-- (vanuit het meldkamersysteem-project) al is uitgevoerd op deze
-- database -- anders bestaan de kolommen nog niet en breekt inloggen op
-- MK Intranet.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.6', '31 augustus 2026', '## Nieuw
- Toegang per applicatie: een account kan nu apart toegang hebben tot het meldkamersysteem en/of MK Intranet, in te stellen bij Beheer -> Gebruikers ("Toegang"-kolom, ook bij een nieuwe gebruiker aanmaken).
- Inloggen op MK Intranet wordt geweigerd met een duidelijke melding als een account daar geen toegang toe heeft.');
