<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

// V0.1.20 (overgenomen van mkapp V2.0.2.7 e.v.): plannen/bewerken/
// annuleren werkt hier net als in mkapp, op dezelfde gedeelde tabel
// geplande_meldingen. Het daadwerkelijk laten verschijnen als echte
// melding (verwerk_geplande_meldingen()) blijft bewust alleen in mkapp
// lopen -- die staat toch altijd open in de meldkamer, en zo verwerken
// nooit twee apps tegelijk dezelfde rij.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if ($actie === 'opslaan') {
        $id                     = (int) ($_POST['id'] ?? 0);
        $hoofdclassificatie_id  = $_POST['hoofdclassificatie_id'] !== '' ? (int) $_POST['hoofdclassificatie_id'] : null;
        $subclassificatie_id    = $_POST['subclassificatie_id'] !== '' ? (int) $_POST['subclassificatie_id'] : null;
        $locatie                = trim($_POST['locatie'] ?? '');
        $prioriteit             = $_POST['prioriteit'] ?? 'normaal';
        $gemeld_door            = trim($_POST['gemeld_door'] ?? '');
        $omschrijving           = trim($_POST['omschrijving'] ?? '');
        $attentie               = isset($_POST['attentie']) ? 1 : 0;
        $herhaling_type         = $_POST['herhaling_type'] ?? 'geen';
        $herhaling_interval_ruw = trim($_POST['herhaling_interval'] ?? '');
        $herhaling_tot_ruw      = trim($_POST['herhaling_tot'] ?? '');

        if ($subclassificatie_id !== null && $hoofdclassificatie_id === null) {
            $subclassificatie_id = null;
        }
        if (!in_array($herhaling_type, ['geen','dagelijks','interval_uren','interval_minuten'], true)) {
            $herhaling_type = 'geen';
        }

        $herhaling_tot = $herhaling_tot_ruw !== '' ? DateTime::createFromFormat('Y-m-d\TH:i', $herhaling_tot_ruw) : null;
        $herhaling_interval = ctype_digit($herhaling_interval_ruw) ? (int) $herhaling_interval_ruw : null;

        // Bij bewerken: 1 tijdstip (past bij precies die ene rij). Bij
        // aanmaken: 1 of meerdere losse tijdstippen tegelijk -- elk
        // tijdstip wordt dan een eigen, onafhankelijke geplande melding
        // met verder dezelfde inhoud.
        if ($id > 0) {
            $geplande_tijden_ruw = [trim($_POST['geplande_tijd'] ?? '')];
        } else {
            $geplande_tijden_ruw = array_filter(array_map('trim', $_POST['geplande_tijden'] ?? []), fn($t) => $t !== '');
        }

        $geplande_tijden = [];
        foreach ($geplande_tijden_ruw as $ruw) {
            $tijd = DateTime::createFromFormat('Y-m-d\TH:i', $ruw);
            if ($tijd) {
                $geplande_tijden[] = $tijd;
            }
        }

        if (!$hoofdclassificatie_id) {
            $fout = 'Kies een hoofdclassificatie.';
        } elseif (!in_array($prioriteit, ['laag','normaal','hoog','kritiek'], true)) {
            $fout = 'Ongeldige prioriteit.';
        } elseif (!$geplande_tijden) {
            $fout = 'Vul minstens 1 geldig tijdstip in.';
        } elseif ($herhaling_type !== 'geen' && !$herhaling_tot) {
            $fout = 'Vul in tot wanneer de melding herhaald moet worden.';
        } elseif (in_array($herhaling_type, ['interval_uren','interval_minuten'], true) && (!$herhaling_interval || $herhaling_interval < 1)) {
            $fout = 'Vul een geldig interval in (minimaal 1).';
        } else {
            $herhaling_tot_sql = $herhaling_tot ? $herhaling_tot->format('Y-m-d H:i:s') : null;

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE geplande_meldingen SET hoofdclassificatie_id = :h, subclassificatie_id = :s, locatie = :l,
                        prioriteit = :p, gemeld_door = :g, omschrijving = :o, attentie = :a, geplande_tijd = :t,
                        herhaling_type = :ht, herhaling_interval = :hi, herhaling_tot = :htot
                     WHERE id = :id AND status = "wachtend"'
                );
                $stmt->execute([
                    'h' => $hoofdclassificatie_id, 's' => $subclassificatie_id, 'l' => $locatie ?: null,
                    'p' => $prioriteit, 'g' => $gemeld_door ?: null, 'o' => $omschrijving ?: null,
                    'a' => $attentie, 't' => $geplande_tijden[0]->format('Y-m-d H:i:s'),
                    'ht' => $herhaling_type, 'hi' => $herhaling_interval, 'htot' => $herhaling_tot_sql,
                    'id' => $id,
                ]);
                $succes = 'Geplande melding bijgewerkt.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO geplande_meldingen (hoofdclassificatie_id, subclassificatie_id, locatie, prioriteit, gemeld_door, omschrijving, attentie, geplande_tijd, herhaling_type, herhaling_interval, herhaling_tot, aangemaakt_door_id)
                     VALUES (:h, :s, :l, :p, :g, :o, :a, :t, :ht, :hi, :htot, :u)'
                );
                foreach ($geplande_tijden as $tijd) {
                    $stmt->execute([
                        'h' => $hoofdclassificatie_id, 's' => $subclassificatie_id, 'l' => $locatie ?: null,
                        'p' => $prioriteit, 'g' => $gemeld_door ?: null, 'o' => $omschrijving ?: null,
                        'a' => $attentie, 't' => $tijd->format('Y-m-d H:i:s'),
                        'ht' => $herhaling_type, 'hi' => $herhaling_interval, 'htot' => $herhaling_tot_sql,
                        'u' => $_SESSION['gebruiker_id'],
                    ]);
                }
                $succes = count($geplande_tijden) === 1
                    ? 'Melding ingepland voor ' . $geplande_tijden[0]->format('d-m-Y H:i') . '.'
                    : count($geplande_tijden) . ' momenten ingepland.';
            }
        }
    }

    if ($actie === 'annuleren') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE geplande_meldingen SET status = 'geannuleerd' WHERE id = :id AND status = 'wachtend'");
        $stmt->execute(['id' => $id]);
        $succes = 'Geplande melding geannuleerd.';
    }

    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM geplande_meldingen WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Verwijderd uit de lijst.';
    }
}

if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare("SELECT * FROM geplande_meldingen WHERE id = :id AND status = 'wachtend'");
    $stmt->execute(['id' => (int) $_GET['bewerk']]);
    $bewerk = $stmt->fetch() ?: null;
}

$hoofdclassificaties = get_hoofdclassificaties($pdo);
$subs_per_hoofd = get_subclassificaties_gegroepeerd($pdo);
$gekozen_hoofd = (string) ($bewerk['hoofdclassificatie_id'] ?? '');
$gekozen_sub   = (string) ($bewerk['subclassificatie_id'] ?? '');

$wachtend_stmt = $pdo->query(
    "SELECT gp.*, h.naam AS hoofd_naam, s.naam AS sub_naam, g.naam AS aangemaakt_door_naam
     FROM geplande_meldingen gp
     LEFT JOIN hoofdclassificaties h ON h.id = gp.hoofdclassificatie_id
     LEFT JOIN subclassificaties s ON s.id = gp.subclassificatie_id
     LEFT JOIN gebruikers g ON g.id = gp.aangemaakt_door_id
     WHERE gp.status = 'wachtend'
     ORDER BY gp.geplande_tijd ASC"
);
$wachtende_meldingen = $wachtend_stmt->fetchAll();

$afgehandeld_stmt = $pdo->query(
    "SELECT gp.*, h.naam AS hoofd_naam, s.naam AS sub_naam, m.meld_id AS verwerkte_meld_id
     FROM geplande_meldingen gp
     LEFT JOIN hoofdclassificaties h ON h.id = gp.hoofdclassificatie_id
     LEFT JOIN subclassificaties s ON s.id = gp.subclassificatie_id
     LEFT JOIN meldingen m ON m.id = gp.verwerkte_melding_id
     WHERE gp.status IN ('verwerkt', 'geannuleerd')
     ORDER BY gp.geplande_tijd DESC
     LIMIT 50"
);
$afgehandelde_meldingen = $afgehandeld_stmt->fetchAll();

$actief = 'beheer';
$paginatitel = 'Geplande meldingen';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Geplande meldingen</h1>
        <p>Plan een melding in voor een toekomstig tijdstip — deze verschijnt dan vanzelf als een echte melding zodra dat tijdstip is bereikt. Zelfde gedeelde lijst als mkapp's Beheer &rarr; Geplande meldingen; je kunt hier plannen, bewerken en annuleren.</p>
        <p class="section-note" style="margin-top:8px;">Het daadwerkelijk laten verschijnen gebeurt via mkapp (die controleert dit bij elke paginabezoek van een ingelogde gebruiker) — zolang daar ergens een dashboard/Overview openstaat, verschijnt de melding vrijwel op tijd, ook als hier verder niemand ingelogd is.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3><?= $bewerk ? 'Geplande melding bewerken' : 'Nieuwe geplande melding' ?></h3>
    <form method="post">
        <input type="hidden" name="actie" value="opslaan">
        <input type="hidden" name="id" value="<?= $bewerk['id'] ?? 0 ?>">
        <div class="form-grid">
            <?php if ($bewerk): ?>
                <div class="field">
                    <label for="geplande_tijd">Tijdstip</label>
                    <input type="datetime-local" id="geplande_tijd" name="geplande_tijd" required
                           value="<?= e((new DateTime($bewerk['geplande_tijd']))->format('Y-m-d\TH:i')) ?>">
                </div>
            <?php else: ?>
                <div class="field field-full">
                    <label>Tijdstip(pen)</label>
                    <div id="momenten_lijst">
                        <div class="moment-regel" style="display:flex; gap:8px; margin-bottom:8px;">
                            <input type="datetime-local" name="geplande_tijden[]" required style="flex:1;">
                            <button type="button" class="btn btn-small moment-verwijderen" style="visibility:hidden;">&times;</button>
                        </div>
                    </div>
                    <button type="button" id="moment_toevoegen" class="btn btn-small">+ Nog een tijdstip toevoegen</button>
                    <p class="section-note" style="margin:6px 0 0;">Meerdere tijdstippen tegelijk invullen (bv. 13:00, 15:30 en 18:00) maakt evenzoveel losse, onafhankelijke geplande meldingen met dezelfde inhoud.</p>
                </div>
            <?php endif; ?>
            <div class="field">
                <label for="prioriteit">Prioriteit</label>
                <select id="prioriteit" name="prioriteit">
                    <?php foreach (['laag','normaal','hoog','kritiek'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($bewerk['prioriteit'] ?? 'normaal') === $p ? 'selected' : '' ?>><?= prioriteit_label($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="hoofdclassificatie_id">Hoofdclassificatie</label>
                <select id="hoofdclassificatie_id" name="hoofdclassificatie_id" required>
                    <option value="">Kies een hoofdclassificatie</option>
                    <?php foreach ($hoofdclassificaties as $h): ?>
                        <option value="<?= $h['id'] ?>" <?= $gekozen_hoofd === (string) $h['id'] ? 'selected' : '' ?>><?= e($h['naam']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="subclassificatie_id">Subclassificatie</label>
                <select id="subclassificatie_id" name="subclassificatie_id">
                    <option value="">Geen subclassificatie</option>
                </select>
            </div>

            <div class="field">
                <label for="locatie">Locatie</label>
                <input type="text" id="locatie" name="locatie" value="<?= e($bewerk['locatie'] ?? '') ?>" placeholder="bv. Podium 2, ingang Noord">
            </div>
            <div class="field">
                <label for="gemeld_door">Gemeld door</label>
                <input type="text" id="gemeld_door" name="gemeld_door" value="<?= e($bewerk['gemeld_door'] ?? '') ?>" placeholder="optioneel">
            </div>

            <div class="field field-full">
                <label for="omschrijving">Omschrijving</label>
                <textarea id="omschrijving" name="omschrijving" placeholder="Wat is er aan de hand zodra dit ingaat?"><?= e($bewerk['omschrijving'] ?? '') ?></textarea>
            </div>

            <div class="field field-full" style="border-top:1px solid var(--border); padding-top:16px; margin-top:4px;">
                <label for="herhaling_type">Herhaling</label>
                <select id="herhaling_type" name="herhaling_type">
                    <option value="geen" <?= ($bewerk['herhaling_type'] ?? 'geen') === 'geen' ? 'selected' : '' ?>>Geen — eenmalig</option>
                    <option value="dagelijks" <?= ($bewerk['herhaling_type'] ?? '') === 'dagelijks' ? 'selected' : '' ?>>Dagelijks, op hetzelfde tijdstip</option>
                    <option value="interval_uren" <?= ($bewerk['herhaling_type'] ?? '') === 'interval_uren' ? 'selected' : '' ?>>Elke X uur</option>
                    <option value="interval_minuten" <?= ($bewerk['herhaling_type'] ?? '') === 'interval_minuten' ? 'selected' : '' ?>>Elke X minuten</option>
                </select>
                <p class="section-note" style="margin:6px 0 0;">Bij elke herhaling wordt een nieuwe, aparte melding aangemaakt (eigen meld-ID en kladblok) — geen doorlopende melding.</p>
            </div>
            <div class="field" id="herhaling_interval_veld" style="display:none;">
                <label for="herhaling_interval">Interval</label>
                <input type="number" id="herhaling_interval" name="herhaling_interval" min="1" value="<?= e($bewerk['herhaling_interval'] ?? '') ?>" placeholder="bv. 30">
            </div>
            <div class="field" id="herhaling_tot_veld" style="display:none;">
                <label for="herhaling_tot">Herhalen tot</label>
                <input type="datetime-local" id="herhaling_tot" name="herhaling_tot"
                       value="<?= $bewerk && $bewerk['herhaling_tot'] ? e((new DateTime($bewerk['herhaling_tot']))->format('Y-m-d\TH:i')) : '' ?>">
            </div>

            <div class="field field-full">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; text-transform:none; font-size:13.5px; color:var(--text); font-weight:400;">
                    <input type="checkbox" name="attentie" <?= (!$bewerk || $bewerk['attentie']) ? 'checked' : '' ?> style="width:auto; height:auto;">
                    ⚠️ Geef automatisch een attentiesignaal zodra deze melding verschijnt
                </label>
                <p class="section-note" style="margin:6px 0 0;">Speelt het belgeluid af en toont ⚠️ voor het meld-ID, net als een handmatig gegeven attentiesignaal.</p>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary"><?= $bewerk ? 'Wijzigingen opslaan' : 'Melding inplannen' ?></button>
            <?php if ($bewerk): ?>
                <a href="/gepland.php" class="btn">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Wachtend <span class="count-badge"><?= count($wachtende_meldingen) ?></span></h3>
    <?php if (!$wachtende_meldingen): ?>
        <p class="section-note">Geen geplande meldingen in de wachtrij.</p>
    <?php else: ?>
        <div class="tabel-scroll">
        <table class="admin-table">
            <thead><tr><th>Volgende keer</th><th>Classificatie</th><th>Locatie</th><th>Prioriteit</th><th>Herhaling</th><th>⚠️</th><th>Aangemaakt door</th><th></th></tr></thead>
            <tbody>
            <?php
            $herhaling_labels = [
                'geen' => null,
                'dagelijks' => 'Dagelijks',
                'interval_uren' => 'Elke %d uur',
                'interval_minuten' => 'Elke %d min',
            ];
            ?>
            <?php foreach ($wachtende_meldingen as $gp): ?>
                <tr>
                    <td class="mono"><?= (new DateTime($gp['geplande_tijd']))->format('d-m-Y H:i') ?></td>
                    <td><?= e($gp['hoofd_naam'] ?: '—') ?><?= $gp['sub_naam'] ? ' · ' . e($gp['sub_naam']) : '' ?></td>
                    <td class="muted"><?= e($gp['locatie'] ?: '—') ?></td>
                    <td><span class="tag" style="background:<?= e(prioriteit_kleur($gp['prioriteit'])) ?>22; color:<?= e(prioriteit_kleur($gp['prioriteit'])) ?>;"><?= prioriteit_label($gp['prioriteit']) ?></span></td>
                    <td class="muted" style="font-size:12.5px;">
                        <?php if ($gp['herhaling_type'] === 'geen'): ?>
                            —
                        <?php else: ?>
                            <?php
                                $label = $herhaling_labels[$gp['herhaling_type']];
                                echo in_array($gp['herhaling_type'], ['interval_uren','interval_minuten'], true)
                                    ? sprintf($label, (int) $gp['herhaling_interval'])
                                    : $label;
                            ?>
                            tot <?= (new DateTime($gp['herhaling_tot']))->format('d-m H:i') ?>
                            <?php if ($gp['keer_verwerkt'] > 0): ?>
                                <br><span class="muted">al <?= (int) $gp['keer_verwerkt'] ?>&times; verschenen</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $gp['attentie'] ? '⚠️' : '—' ?></td>
                    <td class="muted"><?= e($gp['aangemaakt_door_naam'] ?: '—') ?></td>
                    <td class="nowrap">
                        <a href="/gepland.php?bewerk=<?= $gp['id'] ?>" class="btn btn-small">Bewerken</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Deze geplande melding annuleren? Verschijnt dan niet meer (ook geen volgende herhalingen).');">
                            <input type="hidden" name="actie" value="annuleren">
                            <input type="hidden" name="id" value="<?= $gp['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Annuleren</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($afgehandelde_meldingen): ?>
<div class="panel">
    <h3>Verwerkt / geannuleerd</h3>
    <div class="tabel-scroll">
    <table class="admin-table">
        <thead><tr><th>Tijdstip</th><th>Classificatie</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($afgehandelde_meldingen as $gp): ?>
            <tr>
                <td class="mono muted"><?= (new DateTime($gp['geplande_tijd']))->format('d-m-Y H:i') ?></td>
                <td class="muted"><?= e($gp['hoofd_naam'] ?: '—') ?><?= $gp['sub_naam'] ? ' · ' . e($gp['sub_naam']) : '' ?></td>
                <td>
                    <?php if ($gp['status'] === 'verwerkt' && $gp['verwerkte_meld_id']): ?>
                        <a href="/melding.php?id=<?= $gp['verwerkte_melding_id'] ?>" style="color:var(--amber);">Verwerkt &rarr; <?= e($gp['verwerkte_meld_id']) ?></a>
                    <?php else: ?>
                        <span class="muted">Geannuleerd</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Verwijderen uit deze lijst? Dit heeft geen invloed op de eventueel al aangemaakte melding.');">
                        <input type="hidden" name="actie" value="verwijderen">
                        <input type="hidden" name="id" value="<?= $gp['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<script>
const subclassificaties = <?= json_encode($subs_per_hoofd, JSON_UNESCAPED_UNICODE) ?>;
const gekozenSub = <?= json_encode($gekozen_sub) ?>;

const hoofdSelect = document.getElementById('hoofdclassificatie_id');
const subSelect = document.getElementById('subclassificatie_id');

function vulSubclassificaties() {
    const hoofdId = hoofdSelect.value;
    subSelect.innerHTML = '<option value="">Geen subclassificatie</option>';
    const lijst = subclassificaties[hoofdId] || [];
    lijst.forEach(function (sub) {
        const optie = document.createElement('option');
        optie.value = sub.id;
        optie.textContent = sub.naam;
        if (String(sub.id) === gekozenSub) {
            optie.selected = true;
        }
        subSelect.appendChild(optie);
    });
    subSelect.disabled = lijst.length === 0;
}

hoofdSelect.addEventListener('change', vulSubclassificaties);
vulSubclassificaties();

// Toont het interval-veld alleen bij "Elke X uur/minuten", en het
// "Herhalen tot"-veld bij elke herhalingsoptie behalve "Geen"
const herhalingSelect = document.getElementById('herhaling_type');
const herhalingIntervalVeld = document.getElementById('herhaling_interval_veld');
const herhalingTotVeld = document.getElementById('herhaling_tot_veld');

function werkHerhalingVeldenBij() {
    const type = herhalingSelect.value;
    herhalingIntervalVeld.style.display = (type === 'interval_uren' || type === 'interval_minuten') ? '' : 'none';
    herhalingTotVeld.style.display = type !== 'geen' ? '' : 'none';
}
herhalingSelect.addEventListener('change', werkHerhalingVeldenBij);
werkHerhalingVeldenBij();

// Dynamisch tijdstippen toevoegen/verwijderen (alleen bij aanmaken, niet
// bij bewerken -- dat veld bestaat dan niet)
const momentenLijst = document.getElementById('momenten_lijst');
const momentToevoegenKnop = document.getElementById('moment_toevoegen');

function werkVerwijderKnoppenBij() {
    const regels = momentenLijst.querySelectorAll('.moment-regel');
    regels.forEach(function (regel, i) {
        const knop = regel.querySelector('.moment-verwijderen');
        knop.style.visibility = regels.length > 1 ? 'visible' : 'hidden';
    });
}

if (momentenLijst && momentToevoegenKnop) {
    momentToevoegenKnop.addEventListener('click', function () {
        const nieuweRegel = document.createElement('div');
        nieuweRegel.className = 'moment-regel';
        nieuweRegel.style.cssText = 'display:flex; gap:8px; margin-bottom:8px;';
        nieuweRegel.innerHTML = '<input type="datetime-local" name="geplande_tijden[]" required style="flex:1;">' +
            '<button type="button" class="btn btn-small moment-verwijderen">&times;</button>';
        momentenLijst.appendChild(nieuweRegel);
        werkVerwijderKnoppenBij();
    });

    momentenLijst.addEventListener('click', function (e) {
        if (e.target.classList.contains('moment-verwijderen')) {
            e.target.closest('.moment-regel').remove();
            werkVerwijderKnoppenBij();
        }
    });

    werkVerwijderKnoppenBij();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
