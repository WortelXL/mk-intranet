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

/**
 * Automatisch-verversen-instelling van de ingelogde gebruiker, in
 * seconden (0 = uit). Zelfde kolom als het meldkamersysteem gebruikt
 * (gebruikers.auto_refresh_seconden), dus per persoon en overal gelijk.
 */
function huidige_gebruiker_auto_refresh(PDO $pdo): int
{
    static $cache = null;
    if ($cache === null) {
        $stmt = $pdo->prepare('SELECT auto_refresh_seconden FROM gebruikers WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['gebruiker_id'] ?? 0]);
        $waarde = $stmt->fetchColumn();
        $cache = $waarde !== false ? (int) $waarde : 0;
    }
    return $cache;
}

/** Slaat de automatisch-verversen-instelling op voor de ingelogde gebruiker */
function stel_auto_refresh_in(PDO $pdo, int $seconden): void
{
    $seconden = max(0, min(600, $seconden));
    $stmt = $pdo->prepare('UPDATE gebruikers SET auto_refresh_seconden = :s WHERE id = :id');
    $stmt->execute(['s' => $seconden, 'id' => $_SESSION['gebruiker_id'] ?? 0]);
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

/** Alle labels (zelfde labels-tabel als het meldkamersysteem) */
function get_labels(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM labels ORDER BY naam ASC')->fetchAll();
}

/** Alle subclassificaties, gegroepeerd op hoofdclassificatie voor het archieffilter */
function get_subclassificaties(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM subclassificaties ORDER BY naam ASC')->fetchAll();
}

/**
 * Afgeronde meldingen (status uit de categorie 'afgerond'), meest recent
 * eerst, optioneel gefilterd op hoofdclassificatie, subclassificatie,
 * prioriteit en/of label. Alleen-lezen, met een bovengrens op het aantal
 * resultaten.
 */
function get_archief_meldingen(PDO $pdo, ?int $hoofdclassificatie_id = null, ?string $prioriteit = null, ?int $label_id = null, ?int $subclassificatie_id = null): array
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
    if ($subclassificatie_id) {
        $where[] = 'm.subclassificatie_id = :subclassificatie_id';
        $params['subclassificatie_id'] = $subclassificatie_id;
    }
    if ($prioriteit) {
        $where[] = 'm.prioriteit = :prioriteit';
        $params['prioriteit'] = $prioriteit;
    }
    if ($label_id) {
        $where[] = 'EXISTS (SELECT 1 FROM melding_labels ml2 WHERE ml2.melding_id = m.id AND ml2.label_id = :label_id)';
        $params['label_id'] = $label_id;
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

/**
 * Eén afgeronde melding met alle details, voor de archief-detailpagina en
 * de PDF-export. Geeft null terug als de melding niet bestaat of (nog)
 * niet afgerond is -- mk-intranet toont alleen afgeronde meldingen in
 * detail, actieve meldingen blijven beperkt tot het passieve
 * dashboardoverzicht.
 */
function get_afgeronde_melding(PDO $pdo, int $id): ?array
{
    $afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));
    if (!$afgeronde_sleutels) {
        return null;
    }
    $plekhouders = [];
    $params = ['id' => $id];
    foreach ($afgeronde_sleutels as $i => $sleutel) {
        $plekhouders[] = ':s' . $i;
        $params['s' . $i] = $sleutel;
    }

    $sql = 'SELECT m.*, h.naam AS hoofd_naam, h.kleur AS hoofd_kleur, s.naam AS sub_naam,
                   g1.naam AS aangemaakt_door_naam, g2.naam AS bijgewerkt_door_naam
            FROM meldingen m
            LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
            LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
            LEFT JOIN gebruikers g1 ON g1.id = m.aangemaakt_door_id
            LEFT JOIN gebruikers g2 ON g2.id = m.bijgewerkt_door_id
            WHERE m.id = :id AND m.status IN (' . implode(',', $plekhouders) . ')';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

/** Logboekregels (notities) van één melding, chronologisch (oud -> nieuw) */
function get_notities_voor_melding(PDO $pdo, int $melding_id): array
{
    return get_notities_per_melding($pdo, [$melding_id])[$melding_id] ?? [];
}

/**
 * Gekoppelde protocollen van een melding, elk met de bijbehorende
 * subtaken en de afvinkstatus daarvan (specifiek voor déze melding --
 * dezelfde subtaak kan bij een andere melding los afgevinkt zijn).
 * Resultaat: [['id'=>.., 'titel'=>.., 'inhoud'=>.., 'subtaken'=>[...]], ...]
 */
function get_protocollen_voor_melding(PDO $pdo, int $melding_id): array
{
    $stmt = $pdo->prepare(
        'SELECT p.* FROM melding_protocollen mp
         JOIN protocollen p ON p.id = mp.protocol_id
         WHERE mp.melding_id = :m ORDER BY p.titel ASC'
    );
    $stmt->execute(['m' => $melding_id]);
    $protocollen = $stmt->fetchAll();

    if (!$protocollen) {
        return [];
    }

    $subtaak_stmt = $pdo->prepare(
        'SELECT ps.*, mss.afgevinkt, mss.afgevinkt_op, g.naam AS afgevinkt_door_naam
         FROM protocol_subtaken ps
         LEFT JOIN melding_subtaak_status mss ON mss.subtaak_id = ps.id AND mss.melding_id = :m
         LEFT JOIN gebruikers g ON g.id = mss.afgevinkt_door_id
         WHERE ps.protocol_id = :p ORDER BY ps.volgorde ASC, ps.id ASC'
    );
    foreach ($protocollen as &$protocol) {
        $subtaak_stmt->execute(['m' => $melding_id, 'p' => $protocol['id']]);
        $protocol['subtaken'] = $subtaak_stmt->fetchAll();
    }
    unset($protocol);

    return $protocollen;
}

/** Losse taken (los van protocollen) van een melding, op volgorde */
function get_losse_taken_voor_melding(PDO $pdo, int $melding_id): array
{
    $stmt = $pdo->prepare(
        'SELECT lt.*, g.naam AS afgevinkt_door_naam
         FROM melding_taken lt
         LEFT JOIN gebruikers g ON g.id = lt.afgevinkt_door_id
         WHERE lt.melding_id = :m ORDER BY lt.volgorde ASC, lt.id ASC'
    );
    $stmt->execute(['m' => $melding_id]);
    return $stmt->fetchAll();
}

/** Volledige statusgeschiedenis van een melding, oud -> nieuw */
function get_status_geschiedenis(PDO $pdo, int $melding_id): array
{
    $stmt = $pdo->prepare(
        'SELECT sl.*, g.naam AS gebruiker_naam FROM melding_status_log sl
         LEFT JOIN gebruikers g ON g.id = sl.gebruiker_id
         WHERE sl.melding_id = :m ORDER BY sl.aangemaakt_op ASC'
    );
    $stmt->execute(['m' => $melding_id]);
    return $stmt->fetchAll();
}

/**
 * Zet een statusgeschiedenis om in tijdvakken met duur, bv.
 * [['status'=>'open','van'=>DateTime,'tot'=>DateTime,'duur_seconden'=>1234], ...].
 * $afgeronde_sleutels zijn de statussleutels uit categorie 'afgerond'
 * (get_afgeronde_statussen()) -- zodra de melding in zo'n status komt,
 * loopt het laatste tijdvak tot $laatst_bijgewerkt in plaats van tot nu.
 */
function bereken_status_tijdvakken(array $geschiedenis, array $afgeronde_sleutels, string $laatst_bijgewerkt): array
{
    if (!$geschiedenis) {
        return [];
    }
    $tijdvakken = [];
    $aantal = count($geschiedenis);
    for ($i = 0; $i < $aantal; $i++) {
        $van = new DateTime($geschiedenis[$i]['aangemaakt_op']);
        if ($i + 1 < $aantal) {
            $tot = new DateTime($geschiedenis[$i + 1]['aangemaakt_op']);
        } elseif (in_array($geschiedenis[$i]['status'], $afgeronde_sleutels, true)) {
            $tot = new DateTime($laatst_bijgewerkt);
        } else {
            $tot = new DateTime(); // nog actief: loopt door tot nu
        }
        $tijdvakken[] = [
            'status'        => $geschiedenis[$i]['status'],
            'gebruiker'     => $geschiedenis[$i]['gebruiker_naam'],
            'van'           => $van,
            'tot'           => $tot,
            'duur_seconden' => max(0, $tot->getTimestamp() - $van->getTimestamp()),
            'lopend'        => $i + 1 === $aantal && !in_array($geschiedenis[$i]['status'], $afgeronde_sleutels, true),
        ];
    }
    return $tijdvakken;
}

/** Leesbare duur, bv. "2d 3u", "45m", "12s" */
function format_duur(int $seconden): string
{
    if ($seconden < 60) {
        return $seconden . 's';
    }
    $dagen = intdiv($seconden, 86400);
    $uren = intdiv($seconden % 86400, 3600);
    $minuten = intdiv($seconden % 3600, 60);

    if ($dagen > 0) {
        return $dagen . 'd ' . $uren . 'u';
    }
    if ($uren > 0) {
        return $uren . 'u ' . $minuten . 'm';
    }
    return $minuten . 'm';
}

/**
 * Labels per melding-id, voor een gegeven lijst van melding-ids (bv. het
 * huidige archiefresultaat). Los van de hoofdquery opgehaald om GROUP BY-
 * gedoe met meerdere labels per melding te voorkomen. Resultaat:
 * [melding_id => [label, label, ...]].
 */
function get_labels_per_melding(PDO $pdo, array $melding_ids): array
{
    $labels_per_melding = [];
    if (!$melding_ids) {
        return $labels_per_melding;
    }
    $plekhouders = implode(',', array_fill(0, count($melding_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT ml.melding_id, l.* FROM melding_labels ml
         JOIN labels l ON l.id = ml.label_id
         WHERE ml.melding_id IN ($plekhouders) ORDER BY l.naam ASC"
    );
    $stmt->execute($melding_ids);
    foreach ($stmt->fetchAll() as $rij) {
        $labels_per_melding[$rij['melding_id']][] = $rij;
    }
    return $labels_per_melding;
}

/**
 * Logboekregels (notities) per melding-id, voor een gegeven lijst van
 * melding-ids (bv. de actieve meldingen op het dashboard). Chronologisch
 * (oud -> nieuw, zoals een logboek leest), los van de hoofdquery opgehaald.
 * Alleen-lezen -- notities zelf toevoegen/bewerken gebeurt in het
 * meldkamersysteem. Resultaat: [melding_id => [notitie, notitie, ...]].
 */
function get_notities_per_melding(PDO $pdo, array $melding_ids): array
{
    $notities_per_melding = [];
    if (!$melding_ids) {
        return $notities_per_melding;
    }
    $plekhouders = implode(',', array_fill(0, count($melding_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT * FROM melding_notities WHERE melding_id IN ($plekhouders) ORDER BY aangemaakt_op ASC"
    );
    $stmt->execute($melding_ids);
    foreach ($stmt->fetchAll() as $rij) {
        $notities_per_melding[$rij['melding_id']][] = $rij;
    }
    return $notities_per_melding;
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

/**
 * Links per bericht-id (net als protocol_links bij een protocol in het
 * meldkamersysteem): [bericht_id => [link, link, ...]], op volgorde.
 */
function get_links_per_bericht(PDO $pdo, array $bericht_ids): array
{
    $links_per_bericht = [];
    if (!$bericht_ids) {
        return $links_per_bericht;
    }
    $plekhouders = implode(',', array_fill(0, count($bericht_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT * FROM bericht_links WHERE bericht_id IN ($plekhouders) ORDER BY volgorde ASC, id ASC"
    );
    $stmt->execute($bericht_ids);
    foreach ($stmt->fetchAll() as $rij) {
        $links_per_bericht[$rij['bericht_id']][] = $rij;
    }
    return $links_per_bericht;
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
