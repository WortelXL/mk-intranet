-- MK INTRANET - Migratie voor V0.1.30
-- Alleen een layout-wijziging (kennisbank.php + style.css), geen
-- schemawijziging. Deze insert dient enkel om de versie in het
-- overzicht (intranet_versies) te laten verschijnen en de asset-cache
-- van style.css te forceren te verversen (header.php voegt
-- ?v=<APP_VERSION> toe aan de stylesheet-link).

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.30', '24 september 2026', '## Verbetering (Kennisbank beheren)
- Q&A-items en documenten staan op de beheerpagina nu standaard
  ingeklapt: 1 regel per item (vraag/titel + categorie + aantal links),
  klik erop voor het antwoord, linkbeheer en bewerken/verwijderen.
- Voorkomt dat "Kennisbank beheren" een hele lange pagina wordt zodra
  er veel items bijkomen.');
