-- MK INTRANET - Migratie voor V0.1.32
-- Alleen een CSS-reparatie, geen schemawijziging. Deze insert dient
-- enkel om de versie in het overzicht (intranet_versies) te laten
-- verschijnen en de asset-cache van style.css te forceren te verversen
-- (header.php voegt ?v=<APP_VERSION> toe aan de stylesheet-link).

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.32', '27 september 2026', '## Reparatie (rijke-tekst-editor)
- De opslaan-knop onder het antwoord-/toelichting-/berichttekstveld
  (Kennisbank en Berichten beheren) kwam half achter de tekst-editor te
  zitten. De editor-box rekende zijn eigen hoogte verkeerd door binnen
  het formulier-grid, waardoor de knop erna overlapte i.p.v. eronder te
  staan.');
