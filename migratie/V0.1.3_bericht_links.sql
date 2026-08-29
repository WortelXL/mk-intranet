-- ============================================================
-- MK INTRANET - Migratie voor V0.1.3
-- Vervangt het enkele url-veld van een bericht (V0.1.1) door een eigen
-- links-tabel, net als protocol_links bij een protocol in het
-- meldkamersysteem: max. 5 links per bericht, elk met een eigen
-- knoptekst (label) en URL.
--
-- Volgorde is belangrijk: eerst de nieuwe tabel aanmaken, dan bestaande
-- url-waarden overzetten (zodat berichten die al een link hadden die niet
-- kwijtraken), en pas daarna de oude kolom verwijderen.
-- ============================================================
CREATE TABLE IF NOT EXISTS bericht_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bericht_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    volgorde INT NOT NULL DEFAULT 0,
    aangemaakt_op DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bericht_id) REFERENCES berichten(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bericht_links (bericht_id, label, url, volgorde)
SELECT id, 'Meer informatie', url, 1 FROM berichten WHERE url IS NOT NULL AND url <> '';

ALTER TABLE berichten DROP COLUMN url;

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.3', '29 augustus 2026', '## Gewijzigd
- Berichten: het ene URL-veld is vervangen door een eigen links-lijst (max. 5 per bericht), elk met een eigen knoptekst -- net als bij een protocol in het meldkamersysteem. Bestaande links zijn automatisch overgezet.');
