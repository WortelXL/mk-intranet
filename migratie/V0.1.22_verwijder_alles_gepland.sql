-- ============================================================
-- MK INTRANET - Migratie voor V0.1.22
-- Geen schemawijziging -- alleen de wijzigingenlog-regel.
--
-- Wat er verandert:
-- 1. Beheer > Geplande meldingen heeft bij "Verwerkt / geannuleerd" nu
--    een "Verwijder alles"-knop (overgenomen van mkapp V2.0.2.30) om in
--    1 keer alle afgehandelde geplande meldingen (verwerkt +
--    geannuleerd) op te ruimen, i.p.v. 1 voor 1. Heeft geen invloed op
--    de eventueel al aangemaakte meldingen zelf.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.22', '15 september 2026', '## Nieuw
- Beheer > Geplande meldingen: bij "Verwerkt / geannuleerd" staat nu een "Verwijder alles"-knop (overgenomen van mkapp) om in 1 keer alle afgehandelde geplande meldingen op te ruimen, i.p.v. 1 voor 1. Heeft geen invloed op de eventueel al aangemaakte meldingen zelf.');
