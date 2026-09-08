-- ============================================================
-- MK INTRANET - Migratie voor V0.1.15
-- Geen schemawijziging: puur JS (auto-refresh op de Plattegrond-pagina,
-- zelfde persoonlijke instelling als Overview/Plotbord). Dit voegt
-- alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.15', '8 september 2026', '## Gewijzigd (Plattegrond)
- De Plattegrond-pagina ververst nu automatisch, met dezelfde persoonlijke instelling als Overview en Plotbord (standaard elke 20 seconden, alleen als het tabblad in beeld is).');
