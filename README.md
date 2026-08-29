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

- **Dashboard** (`index.php`) — lopende meldingen (alleen-lezen) en de
  laatste mededelingen. Per melding kan het logboek (de notities)
  in-/uitgeklapt worden via het vinkje "Laat log zien".
- **Crew** (`crew.php`) — crewlijst bekijken, toevoegen, bewerken,
  verwijderen. Toegankelijk voor elke ingelogde gebruiker.
- **Archief** (`archief.php`) — afgeronde meldingen bekijken (alleen-lezen),
  met filters op hoofdclassificatie, prioriteit en label. Exporteren naar
  PDF (`export.php`) kan op twee manieren: alles binnen de huidige filters,
  of een handmatige selectie via de aanvinkvakjes per melding.
  Toegankelijk voor elke ingelogde gebruiker.
- **Berichten beheren** (`berichten.php`) — mededelingen aanmaken,
  bewerken en verwijderen. Alleen zichtbaar/toegankelijk voor gebruikers
  met de rol `beheerder`; de link verschijnt dan ook alleen voor hen in de
  navigatie.

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
  vervaldatum. Het dashboard toont de 20 meest recente; ouder dan dat zie
  je alleen nog via `berichten.php`.
- **Meldingenoverzicht:** toont alle meldingen met een status uit de
  categorie "actief" (zelfde definitie als het dashboard/Overview van
  `mkapp`), zonder logboek, zoeken of doorklikken — bewust een simpel,
  passief overzicht.
- **Archief:** toont meldingen met een status uit de categorie "afgerond",
  met filters op hoofdclassificatie, prioriteit en label (geen zoeken of
  subclassificatiefilter zoals in `mkapp`'s eigen archief). Gecapped op 150
  resultaten, alleen-lezen.
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
- **Poort:** 80. Draait in een eigen Proxmox-container, los van de Docker-host
  van `mkapp` (poort 8080), dus geen conflict. Draai je 'm ooit op dezelfde
  Docker-host als `mkapp`, kies dan een andere hostpoort (bv. `8081:80`) om
  botsing met `mkapp`'s poort 8080 te voorkomen.
