-- ============================================================
-- MK INTRANET - Migratie voor V0.1.35
--
-- Geen schemawijziging, alleen uiterlijk. MK Intranet heeft een nieuw
-- logo gekregen en is overgezet op dezelfde huisstijl als mkapp
-- (V2.0.2.46) en de MDT-app (V0.0.18): donkerblauw/navy met merkmagenta
-- in plaats van het oude teal-thema. Het logo staat in de kop (klein) en
-- groot op de inlogpagina, met een favicon en een icoon voor het
-- beginscherm van de telefoon.
--
-- Alleen assets/style.css, includes/header.php en login.php zijn
-- aangepast; geen enkele tabel of kolom verandert.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.35', '13 november 2026', '## Nieuw (Huisstijl)
- MK Intranet heeft een nieuw logo en een nieuwe huisstijl in dezelfde kleuren als mkapp en de MDT-app: donkerblauw/navy met merkmagenta in plaats van het teal-thema. Het logo staat in de kop en groot op de inlogpagina, en er is een favicon en een icoon voor het beginscherm van de telefoon.
- Geen schemawijziging, alleen uiterlijk (assets/style.css, de kop en de inlogpagina).');
