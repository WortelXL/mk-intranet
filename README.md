# MK Intranet

Een kleine, losse PHP-applicatie naast het meldkamersysteem (`mkapp`) voor
het intranet: een live, alleen-lezen overzicht van lopende meldingen, een
crewlijst en mededelingen van beheerders.

Belangrijk: dit is **geen kopie** van de data. MK Intranet praat via een
live databaseverbinding met dezelfde MariaDB-database als `mkapp` — een
crewlid dat je hier toevoegt, zie je dus meteen terug in het
meldkamersysteem zelf (en andersom). Inloggen gebeurt met dezelfde
`gebruikers`-tabel: elk bestaand account (beheerder, medewerker of viewer)
werkt hier ook.

`database.sql` staat er bewust wel bij, als kopie van het schema — handig
als naslag of om lokaal een losstaande testdatabase op te zetten, maar in
productie draait deze app dus tegen de bestaande, echte database.

## Pagina's

- **Dashboard** (`index.php`) — de laatste mededelingen (incl. eventuele
  links).
- **Meldingen** — submenu in de navigatie (sinds V0.1.7) met twee pagina's:
  - **Overview** (`meldingen.php`) — lopende meldingen (alleen-lezen).
    Per melding kan het logboek (de notities) in-/uitgeklapt worden via
    het vinkje "Laat log zien"; die staat blijft open na een ververste
    pagina (handmatig of automatisch), tot je 'm zelf weer dichtklikt.
    Ververst automatisch als de gebruiker dat zo heeft ingesteld (zie
    hieronder).
  - **Statistieken** (`statistieken.php`) — cijfers over alle meldingen
    (actief + afgerond): aantal per (sub)classificatie, verdeling per
    prioriteit en status, aantal per evenementdag, en gemiddelde
    doorlooptijd (alleen over afgeronde meldingen). Filterbaar op
    hoofd-/subclassificatie en evenementdag. Evenementdag wordt bepaald
    via dezelfde `instellingen`-sleutels (`event_start_datum`,
    `event_aantal_dagen`) als het meldkamersysteem gebruikt voor de
    dagnummering in het meld-ID.
- **Crew** (`crew.php`) — crewlijst bekijken, toevoegen, bewerken,
  verwijderen. Toegankelijk voor elke ingelogde gebruiker.
- **Archief** (`archief.php`) — afgeronde meldingen bekijken (alleen-lezen),
  met filters op hoofdclassificatie, subclassificatie, prioriteit en label.
  Elke melding is aanklikbaar en opent een detailpagina (`melding.php`) met
  logboek, gekoppelde protocollen/subtaken, losse taken en het volledige
  statusverloop met doorlooptijd. Exporteren naar PDF (`export.php`) kan
  op twee manieren: alles binnen de huidige filters, of een handmatige
  selectie via de aanvinkvakjes per melding — de PDF bevat dezelfde
  uitgebreide inhoud als de detailpagina. Toegankelijk voor elke
  ingelogde gebruiker.
- **Beheer** (`beheer.php`) — hub met twee kaarten, alleen zichtbaar/
  toegankelijk voor gebruikers met de rol `beheerder`:
  - **Berichten beheren** (`berichten.php`) — mededelingen aanmaken,
    bewerken en verwijderen, met tot 5 eigen links (knoptekst + URL)
    per bericht, net als bij een protocol in het meldkamersysteem.
  - **Gebruikers beheren** (`gebruikers.php`) — accounts aanmaken, rol/
    functie/wachtwoord wijzigen, activeren/deactiveren, en per app (MK /
    MK Intranet) toegang aan- of uitzetten. Werkt op dezelfde gedeelde
    `gebruikers`-tabel als het meldkamersysteem zelf.
- **Mijn instellingen** (`instellingen.php`) — persoonlijke instellingen,
  te openen via je naam rechtsboven, net als in het meldkamersysteem. Zie
  hieronder.

## Mijn instellingen

Klik rechtsboven op je eigen naam om "Mijn instellingen" te openen — net
als in het meldkamersysteem (daar heet die pagina `profiel.php`). Dit is
geen aparte instellingenset: het zijn dezelfde kolommen op de gedeelde
`gebruikers`-tabel, dus alles hier is synchroon met het meldkamersysteem —
wijzig je iets hier, dan verandert het ook daar (en andersom).

- **Automatisch verversen** — Uit, 10s, 15s, 20s, 30s of 60s, dezelfde
  intervallen als in het meldkamersysteem
  (`gebruikers.auto_refresh_seconden`). Alleen de **Meldingen**-pagina
  ververst automatisch; de andere pagina's (Dashboard, Crew, Archief,
  Beheer) niet, om te voorkomen dat je halverwege het invullen van een
  formulier onderuitgehaald wordt door een automatische ververs. Het
  verversen zelf werkt net als het dashboard van het meldkamersysteem: het
  blijft doorlopen op het ingestelde interval (niet nog maar één keer),
  pauzeert vanzelf zodra dit tabblad niet actief in beeld is, en de
  scrollpositie wordt onthouden zodat de pagina niet telkens naar boven
  springt.
- **Geluid bij een nieuwe melding** (`gebruikers.geluid_nieuwe_melding`) —
  speelt een kort geluidje op de Meldingen-pagina zodra er een nieuwe
  melding bijkomt, en een ander (dubbel) geluidje bij een nieuwe
  attentie-melding. Puur clientside (Web Audio API), werkt alleen zolang
  die pagina open staat in de browser; sommige browsers blokkeren geluid
  totdat er ergens op de pagina geklikt is.
- **Wachtwoord wijzigen** — vraagt eerst je huidige wachtwoord, dan een
  nieuw wachtwoord van minimaal 8 tekens (tweemaal, ter controle). Werkt
  op dezelfde `wachtwoord_hash`-kolom als het meldkamersysteem, dus een
  wijziging hier werkt ook meteen daar.

## Starten met Docker

Deze app verbindt met de database van het meldkamersysteem via een vast
IP-adres op het netwerk (huidige testopstelling: `192.168.60.199`), niet
via een gedeelde Docker-container. Dat betekent dat deze app op een andere
machine kan draaien dan de meldkamerstack zelf, zolang er netwerktoegang
is tot dat IP-adres op poort 3306.

1. Controleer dat de database op `192.168.60.199:3306` vanaf deze machine
   bereikbaar is, bijvoorbeeld met:
   ```bash
   nc -vz 192.168.60.199 3306
   ```
2. Zorg dat de MariaDB/MySQL-server daar externe verbindingen toestaat
   (bind-address niet beperkt tot `127.0.0.1`) en dat het `phpserver`-account
   toegang heeft vanaf dit IP-adres/subnet (niet alleen `localhost`):
   ```sql
   SELECT user, host FROM mysql.user WHERE user = 'phpserver';
   -- zo nodig: GRANT ALL ON mkapp.* TO 'phpserver'@'%';
   ```
3. Bouw en start:
   ```bash
   docker compose up -d --build
   ```
4. Ga naar **http://localhost** (poort 80) en log in met een bestaand account.

**Ander IP-adres of terug naar hetzelfde Docker-netwerk?** Pas `DB_HOST`
(en eventueel `DB_PORT`) aan onder `environment:` bij de `app`-service in
`docker-compose.yml`. Wil je weer op hetzelfde Docker-netwerk als de
meldkamerstack aansluiten (in plaats van een IP-adres), kijk dan naar de
`V0.0.1`-versie van dit bestand in de git-historie voor die opzet.

**Wachtwoord.** `DB_USER`/`DB_PASS` staan niet in `docker-compose.yml`
zelf, maar in een lokaal `.env`-bestand (zelfde map), dat in `.gitignore`
staat en dus nooit meegaat naar git/GitHub. Eerste keer opzetten:
```bash
cp .env.example .env
# open .env en vul de echte gebruikersnaam/wachtwoord in
```
`docker compose` leest `.env` automatisch bij het starten. Wijzig je het
wachtwoord (aanbevolen, zie de opmerking hieronder), dan pas je alleen
`.env` aan -- daar hoef je nooit iets voor te committen.

**Dit wachtwoord stond eerder wél in git.** De eerste versies (V0.0.1 en
V0.0.2) van dit project hadden het wachtwoord nog gewoon leesbaar in
`docker-compose.yml` staan, en zijn al naar een publieke GitHub-repo
gepusht. Beschouw dat wachtwoord als gecompromitteerd: wijzig het
`phpserver`-account op de database zelf (`ALTER USER 'phpserver'@'...'
IDENTIFIED BY '...';` of het equivalent via je hostingpaneel) en zet het
nieuwe wachtwoord alleen in je lokale `.env`. Gebruikt `mkapp` hetzelfde
account/wachtwoord, dan moet die stack ook bijgewerkt worden. Wil je ook
de oude, publieke git-geschiedenis opschonen (het wachtwoord staat nog in
de V0.0.1/V0.0.2-commits), dan kan dat met `git filter-repo` of BFG
Repo-Cleaner -- vraag het gerust, dat is een aparte, ingrijpendere
operatie (herschrijft geschiedenis, vereist force-push).

## Database-migratie

Migratiescripts staan in de map `migratie/`, één bestand per versie die een databasewijziging nodig heeft (niet elke versie heeft er een).

**V0.0.5** voegt twee nieuwe tabellen toe: `berichten` (mededelingen) en
`intranet_versies` (het wijzigingenlog hieronder). Draai dit één keer tegen
de bestaande, live database voordat je V0.0.5 in gebruik neemt:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.5_berichten_en_versies.sql
```
Beide tabellen zijn nieuw en alleen voor MK Intranet — `mkapp` gebruikt ze
niet en wordt hier niet door geraakt. Zet je in plaats daarvan een verse
database op, dan staan deze tabellen ook gewoon al in `database.sql`.

**V0.0.6** wijzigt alleen rechten in de PHP-code (geen nieuwe kolommen),
maar voegt wel een wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.6_crew_rechten.sql
```

**V0.0.7** verplaatst alleen de migratiescripts zelf naar deze map (geen
databasewijziging), en voegt de wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.7_changelog.sql
```

**V0.0.8** voegt de archiefpagina toe (leest alleen bestaande tabellen,
geen schemawijziging), en voegt de wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.8_changelog.sql
```

**V0.0.9** voegt het labelfilter en de PDF-export toe aan het archief
(labels/melding_labels bestonden al in de gedeelde database — geen
schemawijziging), en voegt de wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.9_changelog.sql
```

**V0.0.9.1** voegt selectie-export toe aan het archief (geen
schemawijziging), en voegt de wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.0.9.1_changelog.sql
```

**V0.1.0** voegt het logboek toe aan het dashboard (leest de bestaande
tabel `melding_notities`, geen schemawijziging), en voegt de
wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.0_changelog.sql
```

**V0.1.1** voegt een `url`-kolom toe aan `berichten` (wél een
schemawijziging dit keer) en de wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.1_beheer_en_url.sql
```

**V0.1.2** voegt de subclassificatie-filter, de melding-detailpagina en de
uitgebreide PDF-export toe (leest alleen bestaande tabellen, geen
schemawijziging), en de wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.2_changelog.sql
```

**V0.1.3** vervangt het url-veld van een bericht door een eigen
links-tabel (`bericht_links`, max. 5 per bericht). De migratie zet
bestaande url-waarden automatisch over voordat de oude kolom verdwijnt --
**belangrijk: draai deze migratie vóórdat je de nieuwe code live zet**,
anders verwijst de oude code nog naar een kolom die niet meer bestaat:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.3_bericht_links.sql
```

**V0.1.4** voegt de instelling voor automatisch verversen en de nieuwe
Meldingen-pagina toe (leest een bestaande kolom, geen schemawijziging), en
de wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.4_changelog.sql
```

**V0.1.4.2** trekt het automatisch verversen gelijk met het meldkamersysteem
(zelfde intervallen, blijft doorlopen, pauzeert bij een niet-actief
tabblad, scrollpositie onthouden). Geen schemawijziging, alleen de
wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.4.2_changelog.sql
```

**V0.1.5** voegt de pagina "Mijn instellingen" toe (geluid bij een nieuwe
melding en wachtwoord wijzigen, naast het al bestaande automatisch
verversen dat nu hiernaartoe verhuisd is). Leest bestaande kolommen, geen
schemawijziging, alleen de wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.5_instellingen.sql
```

**V0.1.6** voegt toegang-per-applicatie toe (`mag_inloggen_mkapp` /
`mag_inloggen_mkintranet`). **Belangrijk:** deze kolommen zelf zijn
aangemaakt vanuit het meldkamersysteem-project, niet vanuit dit project —
draai die migratie daar eerst. Deze migratie hier voegt alleen de
wijzigingenlog-regel toe:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.6_toegang_per_app.sql
```

**V0.1.7** voegt de Statistieken-pagina toe onder het nieuwe
Meldingen-submenu. Leest alleen bestaande tabellen/instellingen, geen
schemawijziging, alleen de wijzigingenlog-regel:
```bash
mysql -h 192.168.60.199 -P 3306 -u phpserver -pmkappwachtwoord2026 mkapp < migratie/V0.1.7_statistieken.sql
```

## Handmatig (zonder Docker)

Vereist: PHP 8.0+ met `pdo_mysql`, en netwerktoegang tot dezelfde database
als `mkapp`. Zet de databasegegevens in `config.php` (of geef ze als
omgevingsvariabelen mee) en serveer de map met een webserver, of lokaal
even met:
```bash
php -S localhost:8081
```

## Versiebeheer

Dit project begint bij **V0.0.1** en gebruikt git-tags per release, in
dezelfde stijl als `mkapp`. Het wijzigingenlog is ook zichtbaar ín de app
zelf: de versieknop onderaan elke pagina opent een overzicht van wat er
per versie is veranderd of toegevoegd (bron: de `intranet_versies`-tabel).
Nieuwe versie committen en pushen:
```bash
git add -A && git commit -m "V0.x.x - omschrijving"
git tag V0.x.x
git push && git push --tags
```

## Aannames die ik zelf heb ingevuld

Deze zijn niet expliciet gevraagd — pas ze gerust aan:
- **Wie mag crew beheren:** sinds V0.0.6 alleen beheerders (net als in
  `mkapp`). Medewerkers en viewers zien de crewlijst wel, maar de
  toevoeg-/bewerk-/verwijderknoppen verschijnen niet, en de bijbehorende
  POST-acties in `crew.php` weigeren ook als je ze zelf zou aanroepen.
- **Wie mag berichten plaatsen:** alleen gebruikers met de rol
  `beheerder` — `berichten.php` is met `vereis_beheerder()` afgeschermd,
  en de link in de navigatie is dan ook alleen voor hen zichtbaar.
- **Berichten:** platte titel + tekst, geen categorieën, vastpinnen of
  vervaldatum. Sinds V0.1.3 wel tot 5 links per bericht (knoptekst + URL),
  precies zoals protocol_links bij een protocol in het meldkamersysteem —
  zelfde validatie (verplicht http(s)://, max. 5) en dezelfde
  beheer-interactie (los toevoegen/verwijderen, geen bewerken van een
  bestaande link). Het dashboard toont de 20 meest recente berichten; ouder
  dan dat zie je alleen nog via `berichten.php`.
- **Gebruikersbeheer:** dezelfde velden en beveiligingen als het
  gebruikersbeheer in het meldkamersysteem zelf (wachtwoorden gehasht met
  `password_hash()`/`PASSWORD_DEFAULT`, dus compatibel), maar zonder de
  API-tokenfunctionaliteit (die is specifiek voor de Stream Deck-API van
  het meldkamersysteem en hoort hier niet thuis). Er blijft altijd
  minstens één actieve beheerder over, en je kunt je eigen account niet
  verwijderen. Let op: dit werkt op de live, gedeelde inlogtabel — een
  wijziging hier is ook meteen een wijziging in het meldkamersysteem.
- **Toegang per applicatie (V0.1.6):** de kolommen `mag_inloggen_mkapp`
  en `mag_inloggen_mkintranet` zijn aangemaakt vanuit het
  meldkamersysteem-project — MK Intranet leest/schrijft hier alleen
  dezelfde kolomnamen op dezelfde gedeelde tabel. Een lege/NULL-waarde
  (nog niet ingesteld) telt als **toegestaan**; alleen een expliciete 0
  blokkeert het inloggen. Zo kan niemand per ongeluk buitengesloten
  worden door deze functie. Deze check zit alleen in `login.php` (het
  moment van inloggen) — een lopende sessie wordt niet automatisch
  beëindigd als de toegang tijdens het gebruik wordt ingetrokken.
- **Statistieken (V0.1.7):** telt bewust alle meldingen mee (actief +
  afgerond), behalve bij "Gemiddelde doorlooptijd" — daar tellen alleen
  afgeronde meldingen mee, want een nog lopende melding heeft geen
  eindpunt om een duur mee te berekenen. De evenementdag-grenzen komen
  uit dezelfde `instellingen`-sleutels die het meldkamersysteem al
  gebruikt voor de dagnummering in het meld-ID (`event_start_datum`,
  `event_aantal_dagen`) — wijzig je die daar, dan verschuiven de
  dagfilters hier automatisch mee. Het blok "Meldingen per evenementdag"
  negeert het eigen dagfilter bewust (laat alle dagen naast elkaar zien
  om ze te kunnen vergelijken); classificatiefilters gelden daar wel.
- **Meldingenoverzicht:** toont alle meldingen met een status uit de
  categorie "actief" (zelfde definitie als het dashboard/Overview van
  `mkapp`), zonder logboek, zoeken of doorklikken — bewust een simpel,
  passief overzicht.
- **Archief:** toont meldingen met een status uit de categorie "afgerond",
  met filters op hoofdclassificatie, subclassificatie, prioriteit en label
  (geen zoekveld/-commando zoals in `mkapp`'s eigen archief). Gecapped op
  150 resultaten, alleen-lezen.
- **Melding-detailpagina (`melding.php`):** alleen bereikbaar voor
  afgeronde meldingen (via het archief) — een actieve, nog lopende melding
  heeft hier bewust geen detailpagina; het dashboard blijft een passief
  overzicht zonder doorklikken. Puur alleen-lezen: subtaken/losse taken
  worden getoond als afgevinkt of niet, maar kunnen hier niet aan-/
  uitgevinkt worden (dat blijft in het meldkamersysteem). Protocol-links
  (de externe verwijzingen bij een protocol in `mkapp`) worden niet
  getoond — bewust simpel gehouden.
- **Logboek op dashboard:** alleen bij de actieve meldingen op het
  dashboard, niet in het archief. Puur alleen-lezen (dezelfde notities als
  in het meldkamersysteem, zonder daar zelf een notitie toe te kunnen
  voegen) en klapt in/uit zonder pagina-herlaad (CSS-only, net als het
  wijzigingenlog onderaan de pagina). De in-/uitgeklapte staat onthoudt
  niet over een ververste pagina heen.
- **PDF-export archief:** neemt dezelfde filters mee als op het scherm
  staan (of alles, zonder filters), en toont per melding meld-ID, titel,
  classificatie, prioriteit, status, locatie, labels, aanmaakdatum en
  omschrijving. Geen logboek, protocollen, subtaken of doorlooptijden zoals
  in `mkapp`'s eigen export — dat is data die MK Intranet's archief sowieso
  niet toont. Gebruikt dezelfde dependency-vrije PDF-generator
  (`includes/minipdf.php`) als het meldkamersysteem, geen Composer/externe
  library nodig.
- **Automatisch verversen:** alleen op de Meldingen-pagina, niet
  site-breed — de andere pagina's hebben formulieren of filters waar een
  onverwachte ververs vervelend zou zijn. De instelling zelf staat in de
  gedeelde `gebruikers`-tabel, dus is per persoon en werkt hetzelfde in
  beide applicaties.
- **Open logboek onthouden:** per browser (via `localStorage`), niet
  gedeeld tussen apparaten of gebruikers. Werkt de browser met
  privénavigatie of blokkeert die lokale opslag, dan valt het gewoon terug
  op het standaardgedrag (dichtgeklapt na een ververs).
- **Mijn instellingen (V0.1.5):** vervangt de kleine keuzelijst die eerst
  in de navigatiebalk stond — die is verwijderd, alle instellingen zitten
  nu op één pagina, bereikbaar via je naam rechtsboven, net als
  `profiel.php` in het meldkamersysteem. Het losse endpoint
  `instelling_opslaan.php` uit V0.1.4 is daarmee overbodig geworden.
- **Geluid bij een nieuwe melding:** herkent een nieuwe melding aan een
  hoger meld-ID dan de vorige keer dat de Meldingen-pagina open was, net
  als het meldkamersysteem — geen serveraanroep nodig. Alleen actief als
  je het zelf aanzet bij "Mijn instellingen"; staat standaard aan (net als
  in het meldkamersysteem) voor wie de instelling nog nooit heeft
  aangeraakt.
- **Poort:** 80. Draait in een eigen Proxmox-container, los van de Docker-host
  van `mkapp` (poort 8080), dus geen conflict. Draai je 'm ooit op dezelfde
  Docker-host als `mkapp`, kies dan een andere hostpoort (bv. `8081:80`) om
  botsing met `mkapp`'s poort 8080 te voorkomen.
