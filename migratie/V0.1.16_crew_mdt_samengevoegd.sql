-- ============================================================
-- MK INTRANET - Migratie voor V0.1.16
-- Geen eigen schemawijziging (crew/mdt_gebruikers/gebruikers horen bij
-- mkapp -- zorg dat migraties/migratie_zichtbaar_in_mdt.sql daar al
-- gedraaid is, anders mist de "Zichtbaar in MDT"-kolom nog). Dit voegt
-- alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.16', '8 september 2026', '## Gewijzigd (Crew)
- Crew overgenomen uit mkapp (V2.0.2.17/V2.0.2.20): Crew-contacten en MDT-gebruikers staan nu samen in 1 lijst, met een vinkje "Zichtbaar in MDT" per persoon.
- Vanuit Crew kun je nu ook een MDT-login aanmaken of wijzigen (gebruikersnaam/wachtwoord) -- niet meer alleen kale contactpersonen.');
