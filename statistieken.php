<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$hoofdclassificaties = get_hoofdclassificaties($pdo);
$subclassificaties = get_subclassificaties($pdo);
$totaal_dagen = event_aantal_dagen($pdo);
$event_start = new DateTime(event_start_datum($pdo));

$gekozen_hoofd_id = isset($_GET['hoofd']) && $_GET['hoofd'] !== '' ? (int) $_GET['hoofd'] : null;
$gekozen_sub_id   = isset($_GET['sub']) && $_GET['sub'] !== '' ? (int) $_GET['sub'] : null;
$gekozen_dag      = isset($_GET['dag']) && $_GET['dag'] !== '' ? (int) $_GET['dag'] : null;
if ($gekozen_dag !== null && ($gekozen_dag < 1 || $gekozen_dag > $totaal_dagen)) {
    $gekozen_dag = null;
}

// Gedeelde filter (classificatie + dag) -- gebruikt voor alle blokken
// behalve "Meldingen per dag" (die laat bewust alle dagen naast elkaar
// zien, ongeacht het dagfilter).
$where = [];
$params = [];
if ($gekozen_hoofd_id) {
    $where[] = 'm.hoofdclassificatie_id = :hoofd_id';
    $params['hoofd_id'] = $gekozen_hoofd_id;
}
if ($gekozen_sub_id) {
    $where[] = 'm.subclassificatie_id = :sub_id';
    $params['sub_id'] = $gekozen_sub_id;
}
if ($gekozen_dag) {
    [$dag_start, $dag_eind] = evenement_dag_bereik($pdo, $gekozen_dag);
    $where[] = 'm.aangemaakt_op >= :dag_start AND m.aangemaakt_op < :dag_eind';
    $params['dag_start'] = $dag_start->format('Y-m-d H:i:s');
    $params['dag_eind']  = $dag_eind->format('Y-m-d H:i:s');
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Totaal --------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM meldingen m $where_sql");
$stmt->execute($params);
$totaal_meldingen = (int) $stmt->fetchColumn();

// ---- Aantal per classificatie (of subclassificatie, bij een gekozen hoofd) ----
$classificatie_titel = 'Aantal per hoofdclassificatie';
$classificatie_balken = [];
if ($gekozen_hoofd_id && $gekozen_sub_id) {
    // Beide gekozen: verdere opsplitsing heeft geen zin, alleen het totaal telt.
    $classificatie_titel = null;
} elseif ($gekozen_hoofd_id) {
    $classificatie_titel = 'Aantal per subclassificatie';
    $stmt = $pdo->prepare(
        "SELECT m.subclassificatie_id, s.naam, COUNT(*) AS aantal
         FROM meldingen m LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
         $where_sql
         GROUP BY m.subclassificatie_id, s.naam
         ORDER BY aantal DESC"
    );
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $r) {
        $classificatie_balken[] = ['label' => $r['naam'] ?? 'Geen subclassificatie', 'aantal' => (int) $r['aantal']];
    }
} else {
    $stmt = $pdo->prepare(
        "SELECT m.hoofdclassificatie_id, h.naam, h.kleur, COUNT(*) AS aantal
         FROM meldingen m LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
         $where_sql
         GROUP BY m.hoofdclassificatie_id, h.naam, h.kleur
         ORDER BY aantal DESC"
    );
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $r) {
        $classificatie_balken[] = ['label' => $r['naam'] ?? 'Geen classificatie', 'aantal' => (int) $r['aantal'], 'kleur' => $r['kleur'] ?? null];
    }
}

// ---- Verdeling per prioriteit --------------------------------------------
$stmt = $pdo->prepare("SELECT prioriteit, COUNT(*) AS aantal FROM meldingen m $where_sql GROUP BY prioriteit");
$stmt->execute($params);
$prioriteit_tellingen = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$prioriteit_balken = [];
foreach (['kritiek', 'hoog', 'normaal', 'laag'] as $p) {
    $prioriteit_balken[] = ['label' => prioriteit_label($p), 'aantal' => (int) ($prioriteit_tellingen[$p] ?? 0), 'kleur' => prioriteit_kleur($p)];
}

// ---- Verdeling per status --------------------------------------------
$stmt = $pdo->prepare("SELECT m.status, COUNT(*) AS aantal FROM meldingen m $where_sql GROUP BY m.status ORDER BY aantal DESC");
$stmt->execute($params);
$status_balken = [];
foreach ($stmt->fetchAll() as $r) {
    $status_balken[] = ['label' => status_label($pdo, $r['status']), 'aantal' => (int) $r['aantal'], 'kleur' => status_kleur($pdo, $r['status'])];
}

// ---- Meldingen per evenementdag (negeert het dagfilter zelf bewust) ----
$dag_where = [];
$dag_params = [];
if ($gekozen_hoofd_id) {
    $dag_where[] = 'm.hoofdclassificatie_id = :hoofd_id';
    $dag_params['hoofd_id'] = $gekozen_hoofd_id;
}
if ($gekozen_sub_id) {
    $dag_where[] = 'm.subclassificatie_id = :sub_id';
    $dag_params['sub_id'] = $gekozen_sub_id;
}
$per_dag_balken = [];
for ($d = 1; $d <= $totaal_dagen; $d++) {
    [$dag_start, $dag_eind] = evenement_dag_bereik($pdo, $d);
    $dw = array_merge($dag_where, ['m.aangemaakt_op >= :ds' . $d, 'm.aangemaakt_op < :de' . $d]);
    $dp = array_merge($dag_params, ['ds' . $d => $dag_start->format('Y-m-d H:i:s'), 'de' . $d => $dag_eind->format('Y-m-d H:i:s')]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM meldingen m WHERE ' . implode(' AND ', $dw));
    $stmt->execute($dp);
    $per_dag_balken[] = ['label' => 'Dag ' . $d . ' (' . $dag_start->format('d-m') . ')', 'aantal' => (int) $stmt->fetchColumn()];
}

// ---- Gemiddelde doorlooptijd (alleen afgeronde meldingen binnen de selectie) ----
// Telescoop-som: de som van alle statustijdvakken van een melding (zoals
// melding.php per melding berekent) is gelijk aan (laatste tijdstip -
// eerste statuswijziging) -- dat maken we hier in 1 query per melding-
// groep i.p.v. losse geschiedenisquery's per melding.
$afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));
$doorlooptijd_groepen = [];
$doorlooptijd_totaal_seconden = 0;
$doorlooptijd_aantal = 0;
if ($afgeronde_sleutels) {
    $dt_placeholders = [];
    $dt_params = $params;
    foreach ($afgeronde_sleutels as $i => $sleutel) {
        $dt_placeholders[] = ':afg' . $i;
        $dt_params['afg' . $i] = $sleutel;
    }
    $dt_where = $where;
    $dt_where[] = 'm.status IN (' . implode(',', $dt_placeholders) . ')';
    $dt_where_sql = 'WHERE ' . implode(' AND ', $dt_where);

    $groep_kolom = $gekozen_hoofd_id ? 's.naam' : 'h.naam';
    $stmt = $pdo->prepare(
        "SELECT m.id, $groep_kolom AS groep_naam, m.bijgewerkt_op, MIN(sl.aangemaakt_op) AS eerste_status_op
         FROM meldingen m
         JOIN melding_status_log sl ON sl.melding_id = m.id
         LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
         LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
         $dt_where_sql
         GROUP BY m.id, groep_naam, m.bijgewerkt_op"
    );
    $stmt->execute($dt_params);
    $per_groep_seconden = [];
    foreach ($stmt->fetchAll() as $r) {
        $duur = (new DateTime($r['bijgewerkt_op']))->getTimestamp() - (new DateTime($r['eerste_status_op']))->getTimestamp();
        $duur = max(0, $duur);
        $groep = $r['groep_naam'] ?? 'Onbekend';
        $per_groep_seconden[$groep][] = $duur;
        $doorlooptijd_totaal_seconden += $duur;
        $doorlooptijd_aantal++;
    }
    foreach ($per_groep_seconden as $groep => $seconden_lijst) {
        $gemiddeld = (int) round(array_sum($seconden_lijst) / count($seconden_lijst));
        $doorlooptijd_groepen[] = ['label' => $groep, 'aantal' => $gemiddeld, 'aantal_tekst' => format_duur($gemiddeld), 'n' => count($seconden_lijst)];
    }
    usort($doorlooptijd_groepen, fn($a, $b) => $b['aantal'] <=> $a['aantal']);
}
$gemiddelde_doorlooptijd_totaal = $doorlooptijd_aantal > 0 ? (int) round($doorlooptijd_totaal_seconden / $doorlooptijd_aantal) : null;

$actief = 'statistieken';
$paginatitel = 'Statistieken';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Meldingen</p>
        <h1>Statistieken</h1>
        <p>Cijfers over alle meldingen (actief + afgerond), alleen-lezen. Filter op classificatie en/of evenementdag.</p>
    </div>
</div>

<div class="panel">
    <form method="get" class="form-grid">
        <div class="field">
            <label for="hoofd">Hoofdclassificatie</label>
            <select id="hoofd" name="hoofd">
                <option value="">Alle classificaties</option>
                <?php foreach ($hoofdclassificaties as $h): ?>
                    <option value="<?= $h['id'] ?>" <?= $gekozen_hoofd_id === (int) $h['id'] ? 'selected' : '' ?>><?= e($h['naam']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="sub">Subclassificatie</label>
            <select id="sub" name="sub">
                <option value="">Alle subclassificaties</option>
                <?php foreach ($hoofdclassificaties as $h): ?>
                    <?php $subs_van_hoofd = array_filter($subclassificaties, fn($s) => (int) $s['hoofdclassificatie_id'] === (int) $h['id']); ?>
                    <?php if ($subs_van_hoofd): ?>
                        <optgroup label="<?= e($h['naam']) ?>">
                            <?php foreach ($subs_van_hoofd as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $gekozen_sub_id === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['naam']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="dag">Evenementdag</label>
            <select id="dag" name="dag">
                <option value="">Alle dagen</option>
                <?php for ($d = 1; $d <= $totaal_dagen; $d++): ?>
                    <?php [$ds] = evenement_dag_bereik($pdo, $d); ?>
                    <option value="<?= $d ?>" <?= $gekozen_dag === $d ? 'selected' : '' ?>>Dag <?= $d ?> (<?= e($ds->format('d-m')) ?>)</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Filteren</button>
            <?php if ($gekozen_hoofd_id || $gekozen_sub_id || $gekozen_dag): ?>
                <a href="/statistieken.php" class="btn">Wis filters</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<section class="section">
    <div class="board">
        <div class="board-cell">
            <div class="num"><?= $totaal_meldingen ?></div>
            <div class="lbl">Meldingen (deze selectie)</div>
        </div>
        <div class="board-cell">
            <div class="num"><?= $gemiddelde_doorlooptijd_totaal !== null ? e(format_duur($gemiddelde_doorlooptijd_totaal)) : '—' ?></div>
            <div class="lbl">Gem. doorlooptijd</div>
        </div>
        <div class="board-cell">
            <div class="num"><?= $doorlooptijd_aantal ?></div>
            <div class="lbl">Afgerond meegenomen</div>
        </div>
    </div>

    <?php if ($classificatie_titel): ?>
    <div class="panel">
        <h3><?= e($classificatie_titel) ?></h3>
        <?= render_stat_balken($classificatie_balken) ?>
    </div>
    <?php endif; ?>

    <div class="panel">
        <h3>Verdeling per prioriteit</h3>
        <?= render_stat_balken($prioriteit_balken) ?>
    </div>

    <div class="panel">
        <h3>Verdeling per status</h3>
        <?= render_stat_balken($status_balken) ?>
    </div>

    <div class="panel">
        <h3>Meldingen per evenementdag</h3>
        <?= render_stat_balken($per_dag_balken) ?>
        <p class="section-note">Toont altijd alle dagen naast elkaar, ongeacht het dagfilter hierboven — classificatiefilters gelden hier wel.</p>
    </div>

    <div class="panel">
        <h3>Gemiddelde doorlooptijd <?= $gekozen_hoofd_id ? 'per subclassificatie' : 'per hoofdclassificatie' ?></h3>
        <?php if (!$doorlooptijd_groepen): ?>
            <p class="stat-leeg">Nog geen afgeronde meldingen voor deze selectie.</p>
        <?php else: ?>
            <?= render_stat_balken($doorlooptijd_groepen) ?>
        <?php endif; ?>
        <p class="section-note">Alleen meldingen die al zijn afgehandeld/geannuleerd tellen mee — zelfde berekening (tijd tussen eerste statusregel en laatste wijziging) als op de melding-detailpagina in het archief.</p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
