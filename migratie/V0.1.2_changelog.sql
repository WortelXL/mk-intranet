-- ============================================================
-- MK INTRANET - Migratie voor V0.1.2
-- Geen schemawijziging (subclassificaties, protocollen, logboek en
-- statusgeschiedenis bestonden al -- zelfde gedeelde database als
-- mkapp). Dit voegt alleen de wijzigingenlog-regel toe.
-- ============================================================
INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.2', '29 augustus 2026', '## Nieuw
- Archief: filter op subclassificatie toegevoegd, naast hoofdclassificatie, prioriteit en label.
- Archief: elke melding is nu aanklikbaar en opent een alleen-lezen detailpagina met logboek, gekoppelde protocollen (met subtaken), losse taken en het volledige statusverloop met doorlooptijd per status.
- PDF-export uitgebreid met dezelfde inhoud: logboek, protocollen/subtaken, losse taken en statusverloop/doorlooptijd per melding.');
