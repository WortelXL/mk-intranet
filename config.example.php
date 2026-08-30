<?php
/**
 * CONFIGURATIE
 * ---------------------------------------------------------------------
 * MK Intranet praat met DEZELFDE database als het meldkamersysteem
 * (mkapp) — het is geen kopie van de data, maar een tweede applicatie die
 * op dezelfde tabellen leest/schrijft (gebruikers, crew, meldingen, ...).
 *
 * Draai je dit via Docker Compose? Dan hoef je dit bestand niet aan te
 * passen: alle waarden hieronder kunnen ook via omgevingsvariabelen
 * aangeleverd worden (zie docker-compose.yml / .env). Een
 * omgevingsvariabele overschrijft altijd de standaardwaarde hieronder.
 */

function env_of(string $naam, $standaard)
{
    $waarde = getenv($naam);
    return $waarde !== false && $waarde !== '' ? $waarde : $standaard;
}

// ---- Database van het meldkamersysteem (live, gedeeld) -----------------
define('DB_HOST', env_of('DB_HOST', 'localhost'));       // bv. 'meldkamer_db' als je op hetzelfde Docker-netwerk zit
define('DB_PORT', env_of('DB_PORT', '3306'));
define('DB_NAME', env_of('DB_NAME', 'mkapp'));
define('DB_USER', env_of('DB_USER', 'phpserver'));
define('DB_PASS', env_of('DB_PASS', 'wijzig_dit_wachtwoord'));
define('DB_CHARSET', env_of('DB_CHARSET', 'utf8mb4'));

// ---- Versie --------------------------------------------------------------
define('APP_VERSION', 'V0.1.4');

// ---- Overig ----------------------------------------------------------------
date_default_timezone_set(env_of('APP_TIMEZONE', 'Europe/Amsterdam'));

// Sessie moet gestart zijn voordat er output is (voor login-beheer). Eigen
// sessienaam, zodat een sessiecookie van deze app nooit botst met een
// sessiecookie van het hoofd-meldkamersysteem als ze ooit op hetzelfde
// domein zouden draaien.
if (session_status() === PHP_SESSION_NONE) {
    session_name('mkintranet_sessie');
    session_start();
}
