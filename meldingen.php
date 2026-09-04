<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

// V0.1.8: optioneel filter op hoofdclassificatie. Voor een gebruiker
// wiens actieve rol een gekoppelde hoofdclassificatie heeft, staat dit
// filter altijd al vast op die classificatie (afgedwongen door
// vereis_rol_beperking(), aangeroepen via vereis_login() hierboven) --
// zie mijn-rol.php, dat hier met dit filter al ingesteld naartoe stuurt.
$hoofdclassificaties = get_hoofdclassificaties($pdo);
$gekozen_hoofd_id = isset($_GET['hoofd']) && $_GET['hoofd'] !== '' ? (int) $_GET['hoofd'] : null;

$mijn_actieve_rol = actieve_rol($pdo);
$rol_beperkt = $mijn_actieve_rol && $mijn_actieve_rol['hoofdclassificatie_id'] !== null;
$gekozen_hoofd_naam = null;
if ($gekozen_hoofd_id) {
    foreach ($hoofdclassificaties as $h) {
        if ((int) $h['id'] === $gekozen_hoofd_id) {
            $gekozen_hoofd_naam = $h['naam'];
            break;
        }
    }
}

$meldingen = get_actieve_meldingen($pdo, $gekozen_hoofd_id);
$actieve_statussen = get_actieve_statussen($pdo);
$tellingen = get_status_tellingen($pdo);
$notities_per_melding = get_notities_per_melding($pdo, array_column($meldingen, 'id'));
// V0.1.10: gekoppelde meldingen (bv. een EHBO-inzet met een gekoppelde
// AMBU-inzet) -- zelfde 🔗-icoon als het dashboard van het
// meldkamersysteem, met de gekoppelde meldingen zelf in het uitklapbare
// logboekblok hieronder.
$gekoppelde_per_melding = get_gekoppelde_meldingen_per_melding($pdo, array_column($meldingen, 'id'));
$afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));
$kritiek_open = 0;
foreach ($meldingen as $m) {
    if ($m['prioriteit'] === 'kritiek') {
        $kritiek_open++;
    }
}

$mijn_instellingen = huidige_gebruiker_instellingen($pdo);
$auto_refresh_seconden = (int) $mijn_instellingen['auto_refresh_seconden'];
$geluid_aan = (bool) $mijn_instellingen['geluid_nieuwe_melding'];
$hoogste_ids = get_hoogste_actieve_melding_ids($pdo);

$actief = $rol_beperkt ? 'mijn-rol' : 'meldingen';
$paginatitel = 'Overview';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Dag <?= bepaal_evenement_dag($pdo) ?> van <?= event_aantal_dagen($pdo) ?></p>
        <h1>Overview</h1>
        <p>Lopende meldingen van het meldkamersysteem, alleen-lezen.</p>
    </div>
    <a href="#" onclick="location.reload(); return false;" class="btn">Vernieuwen</a>
</div>

<?php if ($rol_beperkt): ?>
    <div class="empty">Gefilterd op <strong><?= e($gekozen_hoofd_naam ?? $mijn_actieve_rol['naam']) ?></strong> — jouw actieve rol (<?= e($mijn_actieve_rol['naam']) ?>) toont alleen deze classificatie.</div>
<?php else: ?>
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
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Filteren</button>
            <?php if ($gekozen_hoofd_id): ?>
                <a href="/meldingen.php" class="btn">Wis filter</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<section class="section">
    <div class="board">
        <div class="board-cell <?= $kritiek_open > 0 ? 'pulse' : '' ?>">
            <div class="num c-red"><?= $kritiek_open ?></div>
            <div class="lbl">Kritiek &amp; open</div>
        </div>
        <?php foreach ($actieve_statussen as $s): ?>
            <div class="board-cell">
                <div class="num" style="color:<?= e($s['kleur']) ?>;"><?= (int) ($tellingen[$s['sleutel']] ?? 0) ?></div>
                <div class="lbl"><?= e($s['naam']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="melding-list">
        <?php if (!$meldingen): ?>
            <div class="empty">Geen actieve meldingen<?= $gekozen_hoofd_id ? ' voor deze classificatie' : '' ?>.</div>
        <?php endif; ?>
        <?php foreach ($meldingen as $m): ?>
            <div class="melding-block">
                <div class="melding-row dashboard-row">
                    <span class="melding-id"><?= $m['attentie'] ? '⚠️ ' : '' ?><?= $m['heeft_koppeling'] ? '🔗 ' : '' ?><?= e($m['meld_id']) ?></span>
                    <span class="melding-main">
                        <span class="titel"><?= e($m['titel']) ?></span>
                        <span class="meta">
                            <?= e($m['locatie'] ?: 'Geen locatie opgegeven') ?>
                            &middot; <?= (new DateTime($m['aangemaakt_op']))->format('d-m H:i') ?>
                        </span>
                    </span>
                    <?php if ($m['hoofd_naam']): ?>
                        <span class="cat-chip" style="background: <?= e($m['hoofd_kleur']) ?>22; color: <?= e($m['hoofd_kleur']) ?>;">
                            <?= e($m['hoofd_naam']) ?><?= $m['sub_naam'] ? ' &middot; ' . e($m['sub_naam']) : '' ?>
                        </span>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <span class="tag" style="background:<?= e(prioriteit_kleur($m['prioriteit'])) ?>22; color:<?= e(prioriteit_kleur($m['prioriteit'])) ?>;">
                        <?= e(prioriteit_label($m['prioriteit'])) ?>
                    </span>
                    <span class="tag" style="background:<?= e(status_kleur($pdo, $m['status'])) ?>22; color:<?= e(status_kleur($pdo, $m['status'])) ?>;">
                        <?= e(status_label($pdo, $m['status'])) ?>
                    </span>
                    <label for="log-toggle-<?= $m['id'] ?>" class="log-toggle-wrap" title="Details in-/uitklappen">
                        <span class="log-toggle-switch"></span>
                        <span class="log-toggle-tekst">Laat details zien</span>
                    </label>
                </div>
                <input type="checkbox" id="log-toggle-<?= $m['id'] ?>" class="log-toggle-checkbox" data-melding-id="<?= $m['id'] ?>">
                <div class="row-log">
                    <?php if (!empty($gekoppelde_per_melding[$m['id']])): ?>
                        <div class="koppeling-lijst">
                            <p class="koppeling-lijst-kop">🔗 Gekoppelde meldingen</p>
                            <?php foreach ($gekoppelde_per_melding[$m['id']] as $g): ?>
                                <p class="koppeling-regel">
                                    <span class="muted"><?= e($g['label']) ?>:</span>
                                    <?php if (in_array($g['status'], $afgeronde_sleutels, true)): ?>
                                        <a href="/melding.php?id=<?= (int) $g['melding_id'] ?>" class="melding-link"><?= e($g['meld_id']) ?></a>
                                    <?php else: ?>
                                        <?= e($g['meld_id']) ?>
                                    <?php endif; ?>
                                    &middot; <?= e($g['titel']) ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($notities_per_melding[$m['id']])): ?>
                        <?php foreach ($notities_per_melding[$m['id']] as $n): ?>
                            <p class="melding-log-regel">
                                <span class="melding-log-tijd"><?= (new DateTime($n['aangemaakt_op']))->format('d-m H:i') ?></span>
                                <span class="melding-log-auteur"><?= e($n['auteur'] ?: 'Onbekend') ?>:</span>
                                <?= nl2br(e($n['notitie'])) ?>
                            </p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="melding-log-leeg">Nog geen logboekregels voor deze melding.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="section-note">Alleen-lezen — meldingen zelf beheer je in het meldkamersysteem. Een geopend logboek blijft open staan bij het verversen van de pagina, tot je het zelf weer dichtklikt.</p>
</section>

<?php if ($auto_refresh_seconden > 0): ?>
<script>
(function () {
    // Ververst de pagina automatisch op het ingestelde interval -- net als
    // het dashboard van het meldkamersysteem: blijft doorlopen (niet één
    // keer), pauzeert vanzelf zodra dit tabblad niet actief in beeld is, en
    // onthoudt de scrollpositie zodat de pagina niet steeds naar boven
    // springt.
    var SCROLL_SLEUTEL = 'mkintranet_scroll_' + location.pathname;
    var opgeslagen_scroll = sessionStorage.getItem(SCROLL_SLEUTEL);
    if (opgeslagen_scroll !== null) {
        window.scrollTo(0, parseInt(opgeslagen_scroll, 10) || 0);
        sessionStorage.removeItem(SCROLL_SLEUTEL);
    }

    setInterval(function () {
        if (document.visibilityState === 'visible') {
            sessionStorage.setItem(SCROLL_SLEUTEL, window.scrollY);
            window.location.reload();
        }
    }, <?= $auto_refresh_seconden * 1000 ?>);
})();
</script>
<?php endif; ?>

<?php if ($geluid_aan): ?>
<script>
(function () {
    // Speelt een geluidje bij een nieuwe melding (of een nieuwe attentie-
    // melding) t.o.v. wat we hier eerder al zagen -- puur clientside via
    // localStorage, geen serveraanroep nodig. In te stellen bij "Mijn
    // instellingen". Zelfde aanpak als het meldkamersysteem.
    var hoogste_id = <?= (int) $hoogste_ids['hoogste'] ?>;
    var hoogste_attentie_id = <?= (int) $hoogste_ids['hoogste_attentie'] ?>;
    var OPSLAG_SLEUTEL = 'mkintranet_laatste_gezien_id';
    var OPSLAG_SLEUTEL_ATTENTIE = 'mkintranet_laatste_gezien_attentie_id';

    function speel_meldingsgeluid() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            [880, 660].forEach(function (freq, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.2, ctx.currentTime + i * 0.15);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.15 + 0.25);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + i * 0.15);
                osc.stop(ctx.currentTime + i * 0.15 + 0.3);
            });
        } catch (e) {
            // Geluid kan geblokkeerd zijn door de browser (autoplay-beleid);
            // negeer dat dan stil, de melding zelf is al zichtbaar.
        }
    }

    function speel_attentiegeluid() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.35].forEach(function (vertraging) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 1046;
                gain.gain.setValueAtTime(0.001, ctx.currentTime + vertraging);
                gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + vertraging + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + vertraging + 0.5);
                osc.connect(gain).connect(ctx.destination);
                osc.start(ctx.currentTime + vertraging);
                osc.stop(ctx.currentTime + vertraging + 0.55);
            });
        } catch (e) {
            // Geluid kan geblokkeerd zijn door de browser, negeer dan stil.
        }
    }

    var opgeslagen = localStorage.getItem(OPSLAG_SLEUTEL);
    if (opgeslagen !== null && hoogste_id > parseInt(opgeslagen, 10)) {
        speel_meldingsgeluid();
    }
    var opgeslagen_attentie = localStorage.getItem(OPSLAG_SLEUTEL_ATTENTIE);
    if (opgeslagen_attentie !== null && hoogste_attentie_id > parseInt(opgeslagen_attentie, 10)) {
        speel_attentiegeluid();
    }
    localStorage.setItem(OPSLAG_SLEUTEL, String(hoogste_id));
    localStorage.setItem(OPSLAG_SLEUTEL_ATTENTIE, String(hoogste_attentie_id));
})();
</script>
<?php endif; ?>

<script>
(function () {
    // Onthoudt per melding of het logboek open staat, zodat dat overleeft
    // wanneer de pagina ververst (handmatig of automatisch) -- puur
    // per-browser (localStorage), niet gedeeld met andere gebruikers.
    var OPSLAG_SLEUTEL = 'mkintranet_open_logs';

    function open_logs_ophalen() {
        try {
            return new Set(JSON.parse(localStorage.getItem(OPSLAG_SLEUTEL) || '[]'));
        } catch (e) {
            return new Set();
        }
    }

    function open_logs_opslaan(set) {
        try {
            localStorage.setItem(OPSLAG_SLEUTEL, JSON.stringify(Array.from(set)));
        } catch (e) {
            // localStorage niet beschikbaar (bv. privénavigatie) -- dan
            // werkt de pagina gewoon zonder onthouden toggle-status.
        }
    }

    var open_logs = open_logs_ophalen();
    document.querySelectorAll('.log-toggle-checkbox').forEach(function (checkbox) {
        var id = checkbox.getAttribute('data-melding-id');
        if (open_logs.has(id)) {
            checkbox.checked = true;
        }
        checkbox.addEventListener('change', function () {
            var huidige = open_logs_ophalen();
            if (checkbox.checked) {
                huidige.add(id);
            } else {
                huidige.delete(id);
            }
            open_logs_opslaan(huidige);
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
