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

/**
 * Startdatum (evenementdag 1) en aantal evenementdagen -- zelfde
 * instellingen-sleutels als het meldkamersysteem gebruikt om het meld-ID
 * (bv. MK-D2-014) te bepalen. Gebruikt hier voor het dagfilter op de
 * statistiekenpagina.
 */
function event_start_datum(PDO $pdo): string
{
    return get_instelling($pdo, 'event_start_datum', '2026-08-14');
}

function event_aantal_dagen(PDO $pdo): int
{
    return (int) get_instelling($pdo, 'event_aantal_dagen', '3');
}

/** Start (inclusief) en eind (exclusief) van evenementdag $dag (1-based) */
function evenement_dag_bereik(PDO $pdo, int $dag): array
{
    $start = new DateTime(event_start_datum($pdo));
    $start->modify('+' . ($dag - 1) . ' days')->setTime(0, 0, 0);
    $eind = (clone $start)->modify('+1 day');
    return [$start, $eind];
}

/** Bepaalt op welke evenementdag (1, 2, 3...) een tijdstip valt -- zelfde berekening als het meldkamersysteem */
function bepaal_evenement_dag(PDO $pdo, ?DateTime $moment = null): int
{
    $moment = $moment ?? new DateTime();
    $start  = new DateTime(event_start_datum($pdo));
    $totaal = event_aantal_dagen($pdo);
    $diff   = (int) $start->diff($moment)->format('%r%a');
    $dag    = $diff + 1;
    if ($dag < 1) {
        $dag = 1;
    }
    if ($dag > $totaal) {
        $dag = $totaal;
    }
    return $dag;
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
/**
 * Persoonlijke instellingen van de ingelogde gebruiker -- zelfde kolommen
 * (auto_refresh_seconden, geluid_nieuwe_melding) als het meldkamersysteem
 * gebruikt, dus altijd synchroon: wijzig je 'm hier, dan verandert 'm ook
 * daar (en andersom).
 */
function huidige_gebruiker_instellingen(PDO $pdo): array
{
    $standaard = ['auto_refresh_seconden' => 20, 'geluid_nieuwe_melding' => 1];
    static $cache = null;
    if ($cache === null) {
        if (empty($_SESSION['gebruiker_id'])) {
            return $standaard;
        }
        $stmt = $pdo->prepare('SELECT auto_refresh_seconden, geluid_nieuwe_melding FROM gebruikers WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['gebruiker_id']]);
        $rij = $stmt->fetch();
        $cache = $rij ?: $standaard;
    }
    return $cache;
}

/**
 * Mag deze gebruiker inloggen in een van de twee gekoppelde applicaties?
 * Kolom (mag_inloggen_mkapp / mag_inloggen_mkintranet) staat op de
 * gedeelde `gebruikers`-tabel, aangemaakt vanuit het meldkamersysteem.
 * Ontbrekend/NULL telt als toegestaan (bv. nog niet ingesteld) -- alleen
 * een expliciete 0 blokkeert, zodat niemand per ongeluk buitengesloten
 * wordt.
 */
function gebruiker_mag_inloggen(array $gebruiker, string $kolom): bool
{
    $waarde = $gebruiker[$kolom] ?? null;
    return $waarde === null || (int) $waarde !== 0;
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
    vereis_rol_beperking();
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
 * Rollen (V0.1.8) -- overgenomen van het meldkamersysteem: een gebruiker
 * kan naast de klassieke rol (beheerder/medewerker/view op de
 * gebruikers-tabel zelf) ook 0 of meer benoemde rollen toegewezen krijgen
 * (tabellen 'rollen'/'gebruiker_rollen', al aangemaakt door het
 * meldkamersysteem op de gedeelde database). Eén daarvan is de "actieve"
 * rol (sessiegebonden); die bepaalt de rechten (niveau) en, als er een
 * hoofdclassificatie aan gekoppeld is, beperkt 'm de navigatie tot een
 * eigen gefilterde weergave (zie vereis_rol_beperking() hieronder).
 * ========================================================================= */

/** Alle rollen die aan deze gebruiker zijn toegewezen, alfabetisch op naam. */
function gebruiker_rollen(PDO $pdo, int $gebruiker_id): array
{
    $stmt = $pdo->prepare(
        'SELECT rn.* FROM rollen rn
         INNER JOIN gebruiker_rollen gr ON gr.rol_id = rn.id
         WHERE gr.gebruiker_id = :id
         ORDER BY rn.naam ASC'
    );
    $stmt->execute(['id' => $gebruiker_id]);
    return $stmt->fetchAll();
}

/** Alle beschikbare rollen, alfabetisch op naam. */
function alle_rollen(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM rollen ORDER BY naam ASC')->fetchAll();
}

/**
 * De actieve rol van de ingelogde gebruiker (sessiegebonden via
 * $_SESSION['actieve_rol_id']). Heeft iemand geen (geldige) actieve rol
 * meer in de sessie staan, dan wordt de eerst toegewezen rol de nieuwe
 * standaard. Geeft null als de gebruiker geen enkele rol toegewezen
 * heeft (dan gelden alleen de klassieke rechten op de gebruikers-tabel).
 */
function actieve_rol(PDO $pdo): ?array
{
    if (!is_ingelogd()) {
        return null;
    }

    $mijn_rollen = gebruiker_rollen($pdo, (int) $_SESSION['gebruiker_id']);
    if (!$mijn_rollen) {
        unset($_SESSION['actieve_rol_id']);
        return null;
    }

    $huidige_id = $_SESSION['actieve_rol_id'] ?? null;
    foreach ($mijn_rollen as $rol) {
        if ((int) $rol['id'] === (int) $huidige_id) {
            return $rol;
        }
    }

    // Nog geen (geldige) actieve rol in de sessie: pak de eerst
    // toegewezen rol als standaard.
    $stmt = $pdo->prepare(
        'SELECT rn.* FROM rollen rn
         INNER JOIN gebruiker_rollen gr ON gr.rol_id = rn.id
         WHERE gr.gebruiker_id = :id
         ORDER BY gr.toegewezen_op ASC, rn.id ASC
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['gebruiker_id']]);
    $standaard = $stmt->fetch();
    if ($standaard) {
        $_SESSION['actieve_rol_id'] = (int) $standaard['id'];
    }
    return $standaard ?: null;
}

/**
 * De rol waarvan de gekoppelde hoofdclassificatie bepaalt welke
 * gefilterde weergave iemand ziet. Geeft bij voorkeur de actieve rol
 * terug als die zelf een koppeling heeft; heeft de actieve rol geen
 * koppeling, dan de eerste toegewezen rol die er wel een heeft. Gebruikt
 * door mijn-rol.php (het intranet-equivalent van mkapp's ehbo.php).
 */
function mijn_gefilterde_rol(PDO $pdo): ?array
{
    if (!is_ingelogd()) {
        return null;
    }

    $actief = actieve_rol($pdo);
    if ($actief && $actief['hoofdclassificatie_id'] !== null) {
        return $actief;
    }

    foreach (gebruiker_rollen($pdo, (int) $_SESSION['gebruiker_id']) as $rol) {
        if ($rol['hoofdclassificatie_id'] !== null) {
            return $rol;
        }
    }

    return null;
}

/**
 * Heeft iemands actieve rol een gekoppelde hoofdclassificatie, dan mag
 * die verder alleen nog de eigen gefilterde weergave zien -- geen
 * Dashboard, Meldingen/Statistieken, Archief, Crew of Beheer meer, ook
 * niet via een directe link. Wisselt iemand naar een rol zonder
 * koppeling, dan geldt deze beperking niet meer. Wordt aangeroepen
 * vanuit vereis_login(), dus geldt automatisch voor elke pagina die
 * daar (of via vereis_beheerder(), die vereis_login() zelf al aanroept)
 * doorheen gaat -- geen losse aanroep per pagina nodig.
 *
 * Uitzonderingen, anders zou iemand er nooit meer weg kunnen komen of
 * zou de gefilterde weergave zelf onbruikbaar zijn:
 * - mijn-rol.php, instellingen.php, de uitlogpagina en de rol-wisselaar zelf.
 * - meldingen.php ("Overview"), maar ALLEEN met het eigen
 *   classificatiefilter erbij (?hoofd=<eigen classificatie-id>) -- dat is
 *   precies de weergave waar mijn-rol.php zelf naar doorstuurt.
 * - melding.php (archiefdetail), maar ALLEEN als de opgevraagde melding
 *   zelf tot de eigen hoofdclassificatie behoort.
 *
 * Overgenomen van (en functioneel identiek aan) mkapp's
 * vereis_rol_beperking() -- alleen de paginanamen zijn aangepast aan de
 * eigen paginaset van MK Intranet (die geen ehbo.php/index.php-filter
 * kent, maar mijn-rol.php/meldingen.php).
 */
function vereis_rol_beperking(): void
{
    if (!is_ingelogd()) {
        return;
    }

    $uitgezonderd = ['mijn-rol.php', 'instellingen.php', 'logout.php', 'wissel_rol.php'];
    $huidige_pagina = basename((string) parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    if (in_array($huidige_pagina, $uitgezonderd, true)) {
        return;
    }

    $pdo = get_pdo();
    $rol = actieve_rol($pdo);
    if (!$rol || $rol['hoofdclassificatie_id'] === null) {
        return;
    }

    $eigen_classificatie_id = (int) $rol['hoofdclassificatie_id'];

    if ($huidige_pagina === 'meldingen.php') {
        $gevraagd = isset($_GET['hoofd']) && ctype_digit((string) $_GET['hoofd']) ? (int) $_GET['hoofd'] : null;
        if ($gevraagd === $eigen_classificatie_id) {
            return;
        }
    }

    if ($huidige_pagina === 'melding.php') {
        $melding_id = (int) ($_GET['id'] ?? 0);
        if ($melding_id > 0) {
            $stmt = $pdo->prepare('SELECT hoofdclassificatie_id FROM meldingen WHERE id = :id');
            $stmt->execute(['id' => $melding_id]);
            $gevonden = $stmt->fetchColumn();
            if ($gevonden !== false && $gevonden !== null && (int) $gevonden === $eigen_classificatie_id) {
                return;
            }
        }
    }

    header('Location: /mijn-rol.php');
    exit;
}

/**
 * Aantal rollen (in de tabel `rollen`, dus onafhankelijk van hoeveel
 * gebruikers eraan gekoppeld zijn) met niveau 'beheerder'. Gebruikt in
 * Beheer > Rollen om te voorkomen dat de laatste beheerdersrol wordt
 * omgezet naar een ander niveau of verwijderd -- zonder deze controle
 * zou niemand meer bij Beheer kunnen komen via de rol-wisselaar.
 */
function aantal_rollen_niveau_beheerder(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM rollen WHERE niveau = 'beheerder'")->fetchColumn();
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
 * erbij. Optioneel gefilterd op hoofdclassificatie (V0.1.8, o.a. gebruikt
 * door de gefilterde weergave voor een classificatie-gekoppelde rol).
 * Alleen-lezen — wordt hier nergens gewijzigd.
 */
function get_actieve_meldingen(PDO $pdo, ?int $hoofdclassificatie_id = null): array
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

    $where = 'm.status IN (' . implode(',', $plekhouders) . ')';
    if ($hoofdclassificatie_id) {
        $where .= ' AND m.hoofdclassificatie_id = :hoofd_id';
        $params['hoofd_id'] = $hoofdclassificatie_id;
    }

    $sql = 'SELECT m.*, h.naam AS hoofd_naam, h.kleur AS hoofd_kleur, s.naam AS sub_naam,
                   EXISTS(SELECT 1 FROM melding_koppelingen k WHERE k.melding_id = m.id OR k.gekoppelde_melding_id = m.id) AS heeft_koppeling
            FROM meldingen m
            LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
            LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
            WHERE ' . $where . '
            ORDER BY FIELD(m.prioriteit,"kritiek","hoog","normaal","laag"), m.aangemaakt_op DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Beschikbare koppelingstypes tussen meldingen (overgenomen van het
 * meldkamersysteem). "label" is het label zoals getoond op de melding
 * die de koppeling aangemaakt heeft (bv. "Vervolg van: MK-D2-011"),
 * "omgekeerd" is het label op de andere kant van diezelfde koppeling
 * (bv. "Vervolgmelding: MK-D2-014"). Koppelingen zelf worden alleen in
 * het meldkamersysteem aangemaakt/verwijderd -- MK Intranet toont ze
 * hier alleen-lezen.
 */
function koppeling_types(): array
{
    return [
        'vervolg'     => ['label' => 'Vervolg van', 'omgekeerd' => 'Vervolgmelding'],
        'gerelateerd' => ['label' => 'Gerelateerd aan', 'omgekeerd' => 'Gerelateerd aan'],
    ];
}

/**
 * Gekoppelde meldingen per melding-id (V0.1.10), voor een gegeven lijst
 * van melding-ids (bv. de actieve meldingen op Overview) -- beide
 * richtingen van de koppeling meegenomen, net als
 * get_gekoppelde_meldingen() in het meldkamersysteem doet voor 1 melding
 * tegelijk, maar hier gebatched voor een hele lijst (zelfde aanpak als
 * get_notities_per_melding()/get_labels_per_melding() hieronder).
 * Resultaat: [melding_id => [['meld_id'=>.., 'titel'=>.., 'status'=>..,
 * 'label'=>..], ...]].
 */
function get_gekoppelde_meldingen_per_melding(PDO $pdo, array $melding_ids): array
{
    $gekoppeld_per_melding = [];
    if (!$melding_ids) {
        return $gekoppeld_per_melding;
    }
    $types = koppeling_types();
    $plekhouders = implode(',', array_fill(0, count($melding_ids), '?'));

    // Eigen kant van de koppeling (deze melding_id staat als "melding_id"
    // in melding_koppelingen) -- toont het "label"-label.
    $stmt = $pdo->prepare(
        "SELECT k.melding_id AS bron_id, k.type, m.id AS melding_id, m.meld_id, m.titel, m.status
         FROM melding_koppelingen k
         JOIN meldingen m ON m.id = k.gekoppelde_melding_id
         WHERE k.melding_id IN ($plekhouders)"
    );
    $stmt->execute($melding_ids);
    foreach ($stmt->fetchAll() as $rij) {
        $rij['label'] = $types[$rij['type']]['label'] ?? $rij['type'];
        $gekoppeld_per_melding[$rij['bron_id']][] = $rij;
    }

    // Omgekeerde kant (deze melding_id staat als "gekoppelde_melding_id")
    // -- toont het "omgekeerd"-label.
    $stmt = $pdo->prepare(
        "SELECT k.gekoppelde_melding_id AS bron_id, k.type, m.id AS melding_id, m.meld_id, m.titel, m.status
         FROM melding_koppelingen k
         JOIN meldingen m ON m.id = k.melding_id
         WHERE k.gekoppelde_melding_id IN ($plekhouders)"
    );
    $stmt->execute($melding_ids);
    foreach ($stmt->fetchAll() as $rij) {
        $rij['label'] = $types[$rij['type']]['omgekeerd'] ?? $rij['type'];
        $gekoppeld_per_melding[$rij['bron_id']][] = $rij;
    }

    return $gekoppeld_per_melding;
}

/** Aantal actieve meldingen per status, voor de statistiektegels */
function get_status_tellingen(PDO $pdo): array
{
    return $pdo->query('SELECT status, COUNT(*) AS aantal FROM meldingen GROUP BY status')
        ->fetchAll(PDO::FETCH_KEY_PAIR);
}

/**
 * Hoogste ID onder de actieve meldingen (ongeacht filters) en hoogste ID
 * onder de actieve meldingen met attentie=1 -- gebruikt om clientside te
 * bepalen of er een nieuwe (of nieuw-attentie) melding is bijgekomen, voor
 * het optionele geluidssignaal. Zelfde aanpak als het meldkamersysteem.
 */
function get_hoogste_actieve_melding_ids(PDO $pdo): array
{
    $actieve_sleutels = statussen_sleutels(get_actieve_statussen($pdo));
    if (!$actieve_sleutels) {
        return ['hoogste' => 0, 'hoogste_attentie' => 0];
    }

    $plekhouders = [];
    $params = [];
    foreach ($actieve_sleutels as $i => $sleutel) {
        $plekhouders[] = ':s' . $i;
        $params['s' . $i] = $sleutel;
    }
    $in_clausule = implode(',', $plekhouders);

    $stmt = $pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM meldingen WHERE status IN ($in_clausule)");
    $stmt->execute($params);
    $hoogste = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM meldingen WHERE attentie = 1 AND status IN ($in_clausule)");
    $stmt->execute($params);
    $hoogste_attentie = (int) $stmt->fetchColumn();

    return ['hoogste' => $hoogste, 'hoogste_attentie' => $hoogste_attentie];
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
 * Rendert een simpele, dependency-vrije staafdiagram (CSS-only) voor de
 * statistiekenpagina. $items is een lijst van ['label'=>string,
 * 'aantal'=>int, 'kleur'=>?string, 'aantal_tekst'=>?string], al
 * gesorteerd zoals gewenst. Balken zijn relatief aan het hoogste aantal
 * in de lijst; 'aantal' bepaalt de balkbreedte, 'aantal_tekst' (optioneel)
 * overschrijft alleen het getoonde getal -- handig voor bv. een
 * gemiddelde duur, waar 'aantal' de ruwe seconden is (voor de balkbreedte)
 * maar de tekst een leesbare duur moet tonen (bv. "1u 30m").
 */
function render_stat_balken(array $items, string $standaardkleur = 'var(--teal)'): string
{
    if (!$items) {
        return '<p class="stat-leeg">Geen gegevens voor deze selectie.</p>';
    }
    $max = max(1, max(array_column($items, 'aantal')));
    $html = '<div class="stat-balken">';
    foreach ($items as $item) {
        $kleur = $item['kleur'] ?? $standaardkleur;
        $pct = (int) round(((int) $item['aantal']) / $max * 100);
        $tekst = $item['aantal_tekst'] ?? (string) (int) $item['aantal'];
        $html .= '<div class="stat-balk-rij">'
            . '<span class="stat-balk-label">' . e($item['label']) . '</span>'
            . '<span class="stat-balk-track"><span class="stat-balk-vulling" style="width:' . $pct . '%; background:' . e($kleur) . ';"></span></span>'
            . '<span class="stat-balk-aantal">' . e($tekst) . '</span>'
            . '</div>';
    }
    $html .= '</div>';
    return $html;
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
