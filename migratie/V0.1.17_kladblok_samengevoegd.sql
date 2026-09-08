-- ============================================================
-- MK INTRANET - Migratie voor V0.1.17
-- Geen schemawijziging -- melding_koppelingen en melding_notities horen
-- bij mkapp en bestaan al. Dit voegt alleen de wijzigingenlog-regel toe.
--
-- Wat er verandert: op Overview, de archief-detailpagina (melding.php)
-- en voortaan ook Archief (nieuw: in-/uitklapbaar logboek per rij) wordt
-- het logboek van een melding aangevuld met de logboekregels van alle
-- (ook indirect/transitief) gekoppelde meldingen, elke regel met een
-- 🔗 MK-xxxx-label als hij niet van de melding zelf komt. Overgenomen
-- van dezelfde functie-aanpak in mkapp (V2.0.2.21).
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.17', '8 september 2026', '## Nieuw (Logboek)
- Het logboek van een melding toont nu ook de regels van gekoppelde meldingen (ook indirect, via een keten van koppelingen) -- elke regel heeft een klein 🔗 MK-xxxx-label als hij niet van de melding zelf komt. Zichtbaar op Overview, de archief-detailpagina en voortaan ook in Archief zelf (nieuw: in-/uitklapbaar logboek per rij, net als op Overview).');
