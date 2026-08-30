<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$meldingen = get_actieve_meldingen($pdo);
$actieve_statussen = get_actieve_statussen($pdo);
$tellingen = get_status_tellingen($pdo);
$notities_per_melding = get_notities_per_melding($pdo, array_column($meldingen, 'id'));
$kritiek_open = 0;
foreach ($meldingen as $m) {
    if ($m['prioriteit'] === 'kritiek') {
        $kritiek_open++;
    }
}

$auto_refresh_seconden = huidige_gebruiker_auto_refresh($pdo);

$actief = 'meldingen';
$paginatitel = 'Meldingen';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Live overzicht</p>
        <h1>Meldingen</h1>
        <p>Lopende meldingen van het meldkamersysteem, alleen-lezen.</p>
    </div>
    <a href="#" onclick="location.reload(); return false;" class="btn">Vernieuwen</a>
</div>

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
            <div class="empty">Geen actieve meldingen.</div>
        <?php endif; ?>
        <?php foreach ($meldingen as $m): ?>
            <div class="melding-block">
                <div class="melding-row dashboard-row">
                    <span class="melding-id"><?= $m['attentie'] ? '⚠️ ' : '' ?><?= e($m['meld_id']) ?></span>
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
                    <label for="log-toggle-<?= $m['id'] ?>" class="log-toggle-wrap" title="Logboek in-/uitklappen">
                        <span class="log-toggle-switch"></span>
                        <span class="log-toggle-tekst">Laat log zien</span>
                    </label>
                </div>
                <input type="checkbox" id="log-toggle-<?= $m['id'] ?>" class="log-toggle-checkbox" data-melding-id="<?= $m['id'] ?>">
                <div class="row-log">
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
    // onthoudt de scrollpositie zodat de pagina niet steeds naar boven springt.
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
