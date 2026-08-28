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
