-- ============================================================
-- MK INTRANET - Migratie voor V0.1.11
-- Geen schemawijziging: leest nog steeds alleen de bestaande tabel
-- melding_koppelingen (zie V0.1.10) -- dit is puur een duidelijkere
-- weergave ervan op Overview (kleuraccent + klikbare chips i.p.v. het
-- 🔗-icoon met de lijst achter "Laat details zien"). Dit voegt alleen de
-- wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.11', '4 september 2026', '## Gewijzigd
- Gekoppelde meldingen op Overview zijn nu duidelijker: naast het 🔗-icoon (nu met een telling) krijgt de rij een gekleurde rand, en staan de gekoppelde meldingen als klikbare chips direct in de rij zelf -- klik je erop, dan springt de pagina naar de gekoppelde melding (als die ook in de huidige lijst staat) en licht die rij even op. Beide kanten van dezelfde koppeling delen altijd dezelfde kleur.');
