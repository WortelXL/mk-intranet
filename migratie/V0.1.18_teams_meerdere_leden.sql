-- ============================================================
-- MK INTRANET - Migratie voor V0.1.18
-- Geen eigen schemawijziging -- teams/team_leden/gebruikers horen bij
-- mkapp. Zorg dat mkapp's migraties/migratie_team_leden.sql daar al
-- gedraaid is (V2.0.2.22), anders bestaat de tabel team_leden nog niet
-- en werkt Beheer > Teams hier niet. Dit voegt alleen de
-- wijzigingenlog-regel toe.
--
-- Wat er verandert: een team kon tot nu toe hooguit 1 gekoppeld lid
-- hebben; dat is nu een echte many-to-many-koppeling (tabel
-- team_leden, overgenomen van mkapp V2.0.2.22) -- een team kan 0 of
-- meerdere leden hebben. Plotbord toont voortaan een rij per lid i.p.v.
-- 1 gecombineerde regel. Nieuw: Beheer > Teams (teams.php), waar je
-- leden kunt toevoegen/verwijderen -- teams zelf (aanmaken/hernoemen/
-- verwijderen) blijft een taak van het meldkamersysteem.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.18', '8 september 2026', '## Nieuw (Teams)
- Een team kan nu 0 of meerdere leden hebben (overgenomen van mkapp) -- Plotbord toont een rij per lid, elk met eigen eenheidsstatus.
- Nieuw: Beheer > Teams, waar je leden aan een team kunt toevoegen of verwijderen. Teams zelf aanmaken, hernoemen of verwijderen blijft een taak van het meldkamersysteem.');
