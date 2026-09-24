-- MK INTRANET - Migratie voor V0.1.29
-- Alleen een layout-reparatie (CSS), geen schemawijziging. Deze insert
-- dient enkel om de versie in het overzicht (intranet_versies) te laten
-- verschijnen, en om de asset-cache van style.css te forceren te verversen
-- (header.php voegt ?v=<APP_VERSION> toe aan de stylesheet-link).

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.29', '24 september 2026', '## Reparatie (Plotbord)
- De statuspil/-dropdown bij teamleden liep bij een lange statusnaam
  (bv. "Beschikbaar (BS)") buiten het teamkaartje. Naam en status staan
  nu onder elkaar i.p.v. naast elkaar, en de pil kan nooit breder worden
  dan het kaartje zelf.');
