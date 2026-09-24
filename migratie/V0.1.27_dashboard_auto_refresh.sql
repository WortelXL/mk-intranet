-- ============================================================
-- MK INTRANET - Migratie voor V0.1.27
-- Geen schemawijziging -- alleen de wijzigingenlog-regel.
--
-- Wat er verandert:
-- 1. Het Dashboard (index.php) ververst voortaan ook automatisch, op
--    hetzelfde interval als ingesteld bij Mijn instellingen. Dit stond
--    al op Meldingen/Plotbord/Plattegrond, maar ontbrak nog op het
--    Dashboard zelf -- vandaar dat het daar leek alsof automatisch
--    verversen niet werkte.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.27', '24 september 2026', '## Reparatie
- Het Dashboard ververste niet automatisch, ook niet met "Automatisch verversen" aan bij Mijn instellingen -- die pagina had de ververslogica simpelweg nog nooit gekregen (wel al aanwezig op Meldingen, Plotbord en Plattegrond). Werkt nu ook op het Dashboard, met hetzelfde interval en dezelfde pauze zodra het tabblad niet actief in beeld is.');
