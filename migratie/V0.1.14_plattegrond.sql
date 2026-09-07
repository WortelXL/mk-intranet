-- ============================================================
-- MK INTRANET - Migratie voor V0.1.14
-- Geen eigen schemawijziging (locaties + hun plattegrond-positie horen
-- bij mkapp, zie migraties/migratie_plattegrond.sql daar -- draai die
-- migratie ook, anders blijft de nieuwe Plattegrond-pagina hier leeg).
-- Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.14', '7 september 2026', '## Nieuw (Plattegrond)
- Nieuwe pagina "Plattegrond" onder Meldingen: actieve meldingen als pins op de plattegrond van het plein, op basis van de locatie die aan de melding gekoppeld is.
- Een pin toont bij hover/tik de meld-ID''s en titels op die locatie; meerdere actieve meldingen op 1 locatie krijgen 1 pin met een aantal erop.
- Vereist de nieuwe migratie in mkapp (locaties krijgen daar een positie op de kaart, via Beheer > Locaties).');
