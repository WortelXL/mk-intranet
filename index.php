<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

/* ---- Statuschips (V0.1.23): compacte tellingen, net als op het voorbeeld ---- */
$aantal_actief           = tel_actieve_meldingen($pdo);
$aantal_attentie         = tel_actieve_meldingen_attentie($pdo);
$aantal_van_mij          = tel_toegewezen_aan_mij($pdo, (int) $_SESSION['gebruiker_id']);
$aantal_gepland_vandaag  = tel_gepland_later_vandaag($pdo);
$aantal_afgerond_vandaag = tel_afgerond_vandaag($pdo);

/* ---- Dagteller ---- */
$evenement_dag          = bepaal_evenement_dag($pdo);
$evenement_dagen_totaal = event_aantal_dagen($pdo);

/* ---- Berichten: alleen-lezen, beheren gebeurt op berichten.php --------- */
$berichten = get_berichten($pdo, 3, true);
$links_per_bericht = get_links_per_bericht($pdo, array_column($berichten, 'id'));

/* ---- Nieuw in de kennisbank ---- */
$kb_recent = get_kb_recente_items($pdo, 3);

/* ---- Automatisch verversen (V0.1.27): stond al op Meldingen/Plotbord/
 * Plattegrond, maar ontbrak nog op het Dashboard zelf. ---- */
$mijn_instellingen = huidige_gebruiker_instellingen($pdo);
$auto_refresh_seconden = (int) $mijn_instellingen['auto_refresh_seconden'];

$actief = 'dashboard';
$paginatitel = 'Intranet';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Welkom, <?= e(huidige_gebruiker_naam()) ?></p>
        <h1>MK Intranet</h1>
        <p>Mededelingen van het meldkamersysteem. Lopende meldingen vind je onder "Meldingen" in de navigatie.</p>
        <p class="dagteller">Dag <?= (int) $evenement_dag ?> van <?= (int) $evenement_dagen_totaal ?> &middot; <?= (new DateTime())->format('d-m-Y') ?></p>
    </div>
</div>

<div class="status-chips">
    <a href="/meldingen.php" class="chip">
        <span class="chip-icon">📋</span>
        <span class="chip-tekst"><span class="chip-getal"><?= $aantal_actief ?></span> <span class="chip-label">actief</span></span>
    </a>
    <a href="/meldingen.php" class="chip<?= $aantal_attentie > 0 ? ' warn' : '' ?>">
        <span class="chip-icon">⚠️</span>
        <span class="chip-tekst"><span class="chip-getal"><?= $aantal_attentie ?></span> <span class="chip-label">attentie / kritiek</span></span>
    </a>
    <a href="/meldingen.php?van_mij=1" class="chip">
        <span class="chip-icon">👤</span>
        <span class="chip-tekst"><span class="chip-getal"><?= $aantal_van_mij ?></span> <span class="chip-label">toegewezen aan mij</span></span>
    </a>
    <?php if (is_beheerder()): ?>
    <a href="/gepland.php" class="chip">
        <span class="chip-icon">🕒</span>
        <span class="chip-tekst"><span class="chip-getal"><?= $aantal_gepland_vandaag ?></span> <span class="chip-label">gepland vandaag</span></span>
    </a>
    <?php endif; ?>
    <a href="/archief.php" class="chip ok">
        <span class="chip-icon">✅</span>
        <span class="chip-tekst"><span class="chip-getal"><?= $aantal_afgerond_vandaag ?></span> <span class="chip-label">afgerond vandaag</span></span>
    </a>
</div>

<div class="quicklinks">
    <a href="/plotbord.php">Plotbord</a>
    <a href="/crew.php">Crew</a>
    <a href="/qa.php">Q&amp;A</a>
</div>

<section class="section">
    <h2 class="section-title">
        Berichten
        <span class="section-title-actions">
            <a href="/alle_berichten.php" class="btn btn-small section-title-action">Alle berichten &rarr;</a>
            <?php if (is_beheerder()): ?>
                <a href="/berichten.php" class="btn btn-small section-title-action">Beheren</a>
            <?php endif; ?>
        </span>
    </h2>

    <?php if (!$berichten): ?>
        <div class="empty">Nog geen berichten geplaatst.</div>
    <?php else: ?>
        <div class="bericht-list">
            <?php foreach ($berichten as $b): ?>
                <article class="bericht-card<?= $b['belangrijk'] ? ' gepind' : '' ?>">
                    <h3>
                        <?= e($b['titel']) ?>
                        <?php if ($b['belangrijk']): ?><span class="tag tag-belangrijk">Belangrijk</span><?php endif; ?>
                    </h3>
                    <p><?= nl2br(e($b['inhoud'])) ?></p>
                    <?php if (!empty($links_per_bericht[$b['id']])): ?>
                        <div class="link-knoppen">
                            <?php foreach ($links_per_bericht[$b['id']] as $link): ?>
                                <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="btn btn-small">&#128279; <?= e($link['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <p class="section-note">
                        <?= e($b['auteur_naam'] ?: 'Onbekend') ?>
                        &middot; <?= (new DateTime($b['aangemaakt_op']))->format('d-m-Y H:i') ?>
                        <?php if ($b['geldig_tot']): ?>
                            &middot; <span class="tag-geldig">geldig tot <?= (new DateTime($b['geldig_tot']))->format('d-m-Y H:i') ?></span>
                        <?php endif; ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($kb_recent): ?>
<section class="section">
    <h2 class="section-title">
        Nieuw in de kennisbank
        <a href="/qa.php" class="btn btn-small section-title-action">Naar Q&amp;A &rarr;</a>
    </h2>
    <div class="kb-preview-list">
        <?php foreach ($kb_recent as $item): ?>
            <a href="<?= $item['type'] === 'qa' ? '/qa.php' : '/documenten.php' ?>" class="kb-preview-item">
                <span><?= e($item['titel']) ?></span>
                <span class="cat"><?= $item['type'] === 'qa' ? 'Q&amp;A' : 'Document' ?> &middot; <?= e($item['categorie_naam']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($auto_refresh_seconden > 0): ?>
<script>
(function () {
    // Zelfde auto-refresh-logica als de Meldingen-pagina: blijft doorlopen
    // (niet één keer), pauzeert vanzelf zodra dit tabblad niet actief in
    // beeld is, en onthoudt de scrollpositie zodat de pagina niet steeds
    // naar boven springt.
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

<?php include __DIR__ . '/includes/footer.php'; ?>
