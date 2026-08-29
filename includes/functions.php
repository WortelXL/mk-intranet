<?php
require_once __DIR__ . '/db.php';

/** Korte htmlspecialchars-helper */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Instellingen (o.a. evenementnaam) staan in de databasetabel
 * 'instellingen', beheerd via het hoofd-meldkamersysteem. Ontbreekt een
 * sleutel, dan valt deze applicatie terug op de standaardwaarde.
 */
function get_instelling(PDO $pdo, string $sleutel, string $standaard = ''): string
{
    static $cache = [];
    if (array_key_exists($sleutel, $cache)) {
        return $cache[$sleutel];
    }
    $stmt = $pdo->prepare('SELECT waarde FROM instellingen WHERE sleutel = :s');
    $stmt->execute(['s' => $sleutel]);
    $waarde = $stmt->fetchColumn();
    $cache[$sleutel] = $waarde !== false && $waarde !== '' ? $waarde : $standaard;
    return $cache[$sleutel];
}

function event_naam(PDO $pdo): string
{
    return get_instelling($pdo, 'event_naam', 'Meldkamer');
}

/* =========================================================================
 * Login / sessie
 * ========================================================================= */

function is_ingelogd(): bool
{
    return !empty($_SESSION['gebruiker_id']);
}

function huidige_gebruiker_naam(): string
{
    return $_SESSION['gebruiker_naam'] ?? '';
}

function huidige_gebruiker_rol(): string
{
    return $_SESSION['gebruiker_rol'] ?? '';
}

function rol_label(string $rol): string
{
    return [
        'beheerder'  => 'Beheerder',
        'medewerker' => 'Medewerker',
        'view'       => 'Viewer',
    ][$rol] ?? $rol;
}

/** Elke ingelogde gebruiker van het meldkamersysteem mag het intranet gebruiken */
function vereis_login(): void
{
    if (!is_ingelogd()) {
        header('Location: /login.php');
        exit;
    }
}

function is_beheerder(): bool
{
    return huidige_gebruiker_rol() === 'beheerder';
}

/** Voor pagina's die alleen beheerders mogen zien (bv. berichten aanmaken) */
function vereis_beheerder(): void
{
    vereis_login();
    if (!is_beheerder()) {
        http_response_code(403);
        $pdo = get_pdo(); // lokaal, zodat header.php (event_naam($pdo)) 'm kan gebruiken
        $paginatitel = 'Geen toegang';
        $actief = '';
        include __DIR__ . '/header.php';
        echo '<div class="empty">Je hebt geen beheerdersrechten om deze pagina te bekijken.</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

/* =========================================================================
 * Crew (contactpersonen zonder eigen login)
 * ========================================================================= */

function get_crew(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM crew ORDER BY naam ASC')->fetchAll();
}

/* =========================================================================
 * Statussen & prioriteiten (zelfde logica als het hoofdsysteem, zodat
 * eigen/aangepaste statussen ook hier correct getoond worden)
 * ========================================================================= */

function get_statussen(PDO $pdo): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT * FROM statussen ORDER BY volgorde ASC, id ASC')->fetchAll();
    }
    return $cache;
}

function get_actieve_statussen(PDO $pdo): array
{
    return array_values(array_filter(get_statussen($pdo), fn($s) => $s['categorie'] === 'actief'));
}

function get_afgeronde_statussen(PDO $pdo): array
{
    return array_values(array_filter(get_statussen($pdo), fn($s) => $s['categorie'] === 'afgerond'));
}

function statussen_sleutels(array $statussen): array
{
    return array_column($statussen, 'sleutel');
}

function status_label(PDO $pdo, string $status): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT sleutel, naam FROM statussen')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$status] ?? $status;
}

function status_kleur(PDO $pdo, string $status): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = $pdo->query('SELECT sleutel, kleur FROM statussen')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$status] ?? '#6b7280';
}

function prioriteit_label(string $prioriteit): string
{
    return [
        'laag'    => 'Laag',
        'normaal' => 'Normaal',
        'hoog'    => 'Hoog',
        'kritiek' => 'Kritiek',
    ][$prioriteit] ?? $prioriteit;
}

function prioriteit_kleur(string $prioriteit): string
{
    return [
        'laag'    => '#a8a8a8',
        'normaal' => '#7ea6d8',
        'hoog'    => '#f5a524',
        'kritiek' => '#e04b3f',
    ][$prioriteit] ?? '#c0c0c0';
}

/**
 * Alle actieve meldingen (zelfde definitie als het dashboard/Overview van
 * het hoofdsysteem: elke status met categorie 'actief'), met classificatie
 * erbij. Alleen-lezen — wordt hier nergens gewijzigd.
 */
function get_actieve_meldingen(PDO $pdo): array
{
    $actieve_sleutels = statussen_sleutels(get_actieve_statussen($pdo));
    if (!$actieve_sleutels) {
        return [];
    }

    $plekhouders = [];
    $params = [];
    foreach ($actieve_sleutels as $i => $sleutel) {
        $plekhouders[] = ':s' . $i;
        $params['s' . $i] = $sleutel;
    }

    $sql = 'SELECT m.*, h.naam AS hoofd_naam, h.kleur AS hoofd_kleur, s.naam AS sub_naam
            FROM meldingen m
            LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
            LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
            WHERE m.status IN (' . implode(',', $plekhouders) . ')
            ORDER BY FIELD(m.prioriteit,"kritiek","hoog","normaal","laag"), m.aangemaakt_op DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Aantal actieve meldingen per status, voor de statistiektegels */
function get_status_tellingen(PDO $pdo): array
{
    return $pdo->query('SELECT status, COUNT(*) AS aantal FROM meldingen GROUP BY status')
        ->fetchAll(PDO::FETCH_KEY_PAIR);
}

/* =========================================================================
 * Archief (afgeronde meldingen) — alleen-lezen, simpel gehouden: alleen
 * filters op hoofdclassificatie en prioriteit (geen zoeken, geen
 * subclassificatie/labelfilter zoals in het hoofdsysteem).
 * ========================================================================= */

function get_hoofdclassificaties(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM hoofdclassificaties ORDER BY naam ASC')->fetchAll();
}

/**
 * Afgeronde meldingen (status uit de categorie 'afgerond'), meest recent
 * eerst, optioneel gefilterd op hoofdclassificatie en/of prioriteit.
 * Alleen-lezen, met een bovengrens op het aantal resultaten.
 */
function get_archief_meldingen(PDO $pdo, ?int $hoofdclassificatie_id = null, ?string $prioriteit = null): array
{
    $max_resultaten = 150;

    $afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));
    if (!$afgeronde_sleutels) {
        return [];
    }

    $plekhouders = [];
    $params = [];
    foreach ($afgeronde_sleutels as $i => $sleutel) {
        $plekhouders[] = ':s' . $i;
        $params['s' . $i] = $sleutel;
    }

    $where = ['m.status IN (' . implode(',', $plekhouders) . ')'];

    if ($hoofdclassificatie_id) {
        $where[] = 'm.hoofdclassificatie_id = :hoofdclassificatie_id';
        $params['hoofdclassificatie_id'] = $hoofdclassificatie_id;
    }
    if ($prioriteit) {
        $where[] = 'm.prioriteit = :prioriteit';
        $params['prioriteit'] = $prioriteit;
    }

    $sql = 'SELECT m.*, h.naam AS hoofd_naam, h.kleur AS hoofd_kleur, s.naam AS sub_naam
            FROM meldingen m
            LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
            LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY m.aangemaakt_op DESC
            LIMIT ' . $max_resultaten;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/* =========================================================================
 * Berichten (mededelingen van beheerders, los van meldingen)
 * ========================================================================= */

/** Meest recente berichten eerst, met naam van de auteur erbij */
function get_berichten(PDO $pdo, ?int $limiet = null): array
{
    $sql = 'SELECT b.*, g.naam AS auteur_naam
            FROM berichten b
            LEFT JOIN gebruikers g ON g.id = b.auteur_id
            ORDER BY b.aangemaakt_op DESC';
    if ($limiet !== null) {
        $sql .= ' LIMIT ' . (int) $limiet;
    }
    return $pdo->query($sql)->fetchAll();
}

function get_bericht(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM berichten WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

/* =========================================================================
 * Versiebeheer / wijzigingenlog van MK Intranet zelf (los van mkapp's
 * eigen 'versies'-tabel)
 * ========================================================================= */

function get_intranet_versies(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM intranet_versies ORDER BY id DESC')->fetchAll();
}

function huidige_intranet_versie(PDO $pdo): string
{
    $nieuwste = $pdo->query('SELECT versienummer FROM intranet_versies ORDER BY id DESC LIMIT 1')->fetchColumn();
    return $nieuwste ?: APP_VERSION;
}

/**
 * Zet de vrije wijzigingen-tekst van een versie om naar HTML. Een regel
 * die begint met "## " wordt een groepskop, een regel die begint met "- "
 * (of gewoon een losse regel) wordt een bullet-item.
 */
function render_wijzigingen_html(string $tekst): string
{
    $html = '';
    $binnen_lijst = false;
    foreach (explode("\n", $tekst) as $regel) {
        $regel = trim($regel);
        if ($regel === '') {
            continue;
        }
        if (str_starts_with($regel, '## ')) {
            if ($binnen_lijst) {
                $html .= '</ul>';
                $binnen_lijst = false;
            }
            $html .= '<p class="wijzigingen-groep">' . e(trim(substr($regel, 3))) . '</p>';
        } else {
            if (!$binnen_lijst) {
                $html .= '<ul>';
                $binnen_lijst = true;
            }
            $regel = str_starts_with($regel, '- ') ? trim(substr($regel, 2)) : $regel;
            $html .= '<li>' . e($regel) . '</li>';
        }
    }
    if ($binnen_lijst) {
        $html .= '</ul>';
    }
    return $html;
}
