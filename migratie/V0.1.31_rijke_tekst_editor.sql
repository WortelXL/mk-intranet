-- MK INTRANET - Migratie voor V0.1.31
-- Geen schemawijziging: kb_items.antwoord, kb_documenten.toelichting en
-- berichten.inhoud waren en blijven TEXT-kolommen, alleen de inhoud
-- ervan is nu (opgeslagen) HTML i.p.v. platte tekst. Deze insert dient
-- enkel om de versie in het overzicht (intranet_versies) te laten
-- verschijnen en de asset-cache (o.a. style.css) te forceren te
-- verversen (header.php voegt ?v=<APP_VERSION> toe aan de links).

INSERT IGNORE INTO intranet_versies (versienummer, datum, wijzigingen) VALUES
('V0.1.31', '27 september 2026', '## Nieuw
- Kennisbank (Q&A-antwoord en document-toelichting) en Berichten
  hebben nu een echte tekst-editor: vet/cursief/onderstreept/
  doorstreept, kopjes, genummerde en ongenummerde lijstjes, quotes en
  links -- in plaats van platte tekst.
- Bestaande platte tekst blijft gewoon werken en zichtbaar zoals eerst.

## Techniek
- Opgeslagen opmaak wordt bij het opslaan altijd eerst gefilterd op een
  vaste lijst toegestane tags/links (server-side, niet alleen in de
  browser) voordat die in de database komt.');
