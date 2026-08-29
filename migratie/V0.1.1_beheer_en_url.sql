-- ============================================================
-- MK INTRANET - Migratie voor V0.1.1
-- Schemawijziging: berichten krijgt een optioneel url-veld (net als een
-- link bij een protocol in het meldkamersysteem). Beheer-hub en
-- gebruikersbeheer zelf zijn PHP-only, geen nieuwe tabellen nodig
-- (gebruikers-tabel bestaat en wordt al gedeeld met mkapp).
-- ============================================================
ALTER TABLE berichten ADD COLUMN url VARCHAR(500) DEFAULT NULL AFTER inhoud;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.1', '29 augustus 2026', '## Nieuw
- Beheer-knop toegevoegd in de navigatie: verzamelt Berichten beheren en het nieuwe Gebruikers beheren op één plek.
- Gebruikersbeheer: accounts aanmaken, rol/functie/wachtwoord wijzigen, activeren/deactiveren. Zelfde gedeelde inlogtabel als het meldkamersysteem, met dezelfde beveiligingen (o.a. altijd minstens één actieve beheerder, geen eigen account verwijderen).
- Berichten kunnen nu een optionele URL bevatten (bv. een link naar een draaiboek of externe pagina), zichtbaar op het dashboard en in Berichten beheren.');
