-- ============================================================
-- MK INTRANET - Migratie voor V0.1.19
-- Geen eigen schemawijziging -- teams/mdt_gebruikers horen bij mkapp.
-- Zorg dat mkapp's migraties/migratie_zichtbaar_op_plotbord.sql daar
-- al gedraaid is (V2.0.2.23), anders bestaat de kolom
-- zichtbaar_op_plotbord nog niet en werkt het vinkje hier niet. Dit
-- voegt alleen de wijzigingenlog-regel toe.
--
-- Wat er verandert: elk team (Beheer > Teams) en elke MDT-gebruiker
-- (Crew) heeft nu een vinkje "Zichtbaar op plotbord". Staat een team
-- of persoon uit, dan verdwijnt de kaart van het Plotbord hier -- zelfde
-- kolom, dus meteen in beide apps zichtbaar.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.19', '8 september 2026', '## Nieuw (Plotbord)
- Elk team (Beheer > Teams) en elke MDT-gebruiker (Crew) heeft nu een vinkje "Zichtbaar op plotbord" (overgenomen van mkapp). Staat een team of persoon uit, dan verdwijnt de kaart van het Plotbord -- handig om het overzicht schoon te houden zonder een team te verwijderen of iemands MDT-toegang in te trekken. Staat standaard aan voor iedereen/elk team dat er al stond.');
