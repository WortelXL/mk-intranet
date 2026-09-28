-- MK INTRANET - Migratie voor V0.1.34
--
-- Geen schemawijziging. Reparatie op V0.1.33 (eigen menu-links): als de
-- tabel intranet_menu_items om welke reden dan ook ontbreekt of
-- onbereikbaar is, viel de hele site uit met een fatal error, omdat
-- includes/header.php -- dat op elke pagina wordt geladen -- de query
-- niet afving. Nu met try/catch: bij een database-fout worden er
-- gewoon geen eigen links getoond, in plaats van de site plat te
-- leggen.
--
-- Idempotent: veilig om dit bestand meerdere keren te draaien.

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.34', '28 september 2026', '## Reparatie
- Beheer > Menu (eigen hyperlinks, V0.1.33): een ontbrekende of
  onbereikbare tabel voor eigen links liet voorheen de hele site
  uitvallen met een fatal error, omdat elke pagina header.php laadt.
  Nu vangt de site dit af en worden er dan gewoon geen eigen links
  getoond.');
