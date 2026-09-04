-- ============================================================
-- MK INTRANET - Migratie voor V0.1.10
-- Geen schemawijziging: leest alleen de bestaande tabel
-- melding_koppelingen (al aanwezig, gebruikt door het meldkamersysteem
-- voor koppelingen tussen meldingen). Archief verplaatst naar het
-- submenu "Meldingen" is puur een navigatiewijziging. Dit voegt alleen
-- de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.10', '4 september 2026', '## Nieuw
- Gekoppelde meldingen zijn nu zichtbaar op Overview: een 🔗-icoon bij het meld-ID, en de gekoppelde melding(en) zelf onder "Laat details zien" (samen met het logboek).

## Gewijzigd
- Archief is verplaatst naar het submenu "Meldingen" in de navigatie, naast Overview en Statistieken.');
