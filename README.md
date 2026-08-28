# MK Intranet

Een kleine, losse PHP-applicatie naast het meldkamersysteem (`mkapp`): één
mooie pagina voor het intranet met een live, alleen-lezen overzicht van
lopende meldingen en een crewlijst (toevoegen/bewerken/verwijderen).

Belangrijk: dit is **geen kopie** van de data. MK Intranet praat via een
live databaseverbinding met dezelfde MariaDB-database als `mkapp` — een
crewlid dat je hier toevoegt, zie je dus meteen terug in het
meldkamersysteem zelf (en andersom). Inloggen gebeurt met dezelfde
`gebruikers`-tabel: elk bestaand account (beheerder, medewerker of viewer)
werkt hier ook.

`database.sql` staat er bewust wel bij, als kopie van het schema — handig
als naslag of om lokaal een losstaande testdatabase op te zetten, maar in
productie draait deze app dus tegen de bestaande, echte database.

## Starten met Docker

Deze app moet op **hetzelfde Docker-netwerk** draaien als de
meldkamerstack, zodat hij de `meldkamer_db`-container bij naam kan
bereiken.

1. Zorg dat de meldkamerstack (in `Documents/Docker`) al draait.
2. Zoek het netwerk waar `meldkamer_db` op zit:
   ```bash
   docker inspect meldkamer_db --format '{{json .NetworkSettings.Networks}}'
   ```
   Staat de meldkamerstack in een map genaamd `Docker` en is die gestart met
   `docker compose up`? Dan heet het netwerk standaard **`docker_default`**
   — dat is ook de standaardwaarde in `docker-compose.yml` hier. Zie je een
   andere naam, kopieer dan `.env.example` naar `.env` en zet die naam bij
   `MELDKAMER_NETWORK`.
3. Bouw en start:
   ```bash
   docker compose up -d --build
   ```
4. Ga naar **http://localhost:8081** en log in met een bestaand account.

**Databasewachtwoord.** De `DB_PASS` in `docker-compose.yml` is dezelfde
die ook in de `docker-compose.yml` van `mkapp` staat (`phpserver`-account).
Die staat dus nu op twee plekken. Wil je dat liever niet, dan kun je in de
database van `mkapp` een los, beperkter account aanmaken (bijvoorbeeld één
dat alleen bij `crew`, `meldingen`, `gebruikers`, `statussen`,
`hoofdclassificaties`, `subclassificaties` en `instellingen` mag) en dat
hier gebruiken in plaats van het volledige `phpserver`-account.

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
dezelfde stijl als `mkapp` (bv. `V0.1.0`, `V0.2.0`, ...). Er is bewust nog
geen GitHub-remote gekoppeld — dat doe je zelf:
```bash
git remote add origin https://github.com/<jouw-account>/mk-intranet.git
git push -u origin main --tags
```

## Aannames die ik zelf heb ingevuld

Deze zijn niet expliciet gevraagd — pas ze gerust aan:
- **Wie mag crew beheren:** elke ingelogde gebruiker (beheerder, medewerker
  of viewer), niet alleen beheerders. Wil je dit beperken tot beheerders
  (zoals in `mkapp`), dan is dat een kleine aanpassing in `index.php`
  (`vereis_login()` vervangen door een check op `is_beheerder()`).
- **Meldingenoverzicht:** toont alle meldingen met een status uit de
  categorie "actief" (zelfde definitie als het dashboard/Overview van
  `mkapp`), zonder logboek, zoeken of doorklikken — bewust een simpel,
  passief overzicht.
- **Poort:** 8081, zodat deze niet botst met poort 8080 van `mkapp`.
