-- ============================================================
-- MK INTRANET - Migratie voor V0.1.12
-- Geen schemawijziging: het Plotbord leest de al bestaande tabellen
-- `teams`, `mdt_gebruikers` en `eenheidsstatussen` (aangemaakt vanuit
-- het meldkamersysteem-project, zie voorstel_mdt_fasering.md /
-- voorstel_mdt_gebruikersbeheer.md daar -- draai die migraties eerst
-- als dat nog niet gebeurd is). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.12', '6 september 2026', '## Nieuw
- Plotbord toegevoegd aan het submenu "Meldingen": alle teams en losse MDT-gebruikers in 1 oogopslag, met hun actuele eenheidsstatus en (indien van toepassing) de melding waar ze nu aan werken. Teams staan als kaartjes, net als in het meldkamersysteem; losse MDT-gebruikers staan als een uitklapbare lijst -- klik op een naam voor de actieve melding.');
