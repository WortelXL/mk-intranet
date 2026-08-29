<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

/* ---- Meldingen: alleen-lezen overzicht ---------------------------------- */
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

/* ---- Berichten: alleen-lezen, beheren gebeurt op berichten.php --------- */
$berichten = get_berichten($pdo, 20);

$actief = 'dashboard';
$paginatitel = 'Intranet';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Welkom, <?= e(huidige_gebruiker_naam()) ?></p>
        <h1>MK Intranet</h1>
        <p>Live overzicht van lopende meldingen en mededelingen van het meldkamersysteem.</p>
    </div>
    <a href="#" onclick="location.reload(); return false;" class="btn">Vernieuwen</a>
</div>

<section class="section">
    <h2 class="section-title">Lopende meldingen</h2>

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
                <input type="checkbox" id="log-toggle-<?= $m['id'] ?>" class="log-toggle-checkbox">
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
    <p class="section-note">Alleen-lezen — meldingen zelf beheer je in het meldkamersysteem.</p>
</section>

<section class="section">
    <h2 class="section-title">
        Berichten
        <?php if (is_beheerder()): ?>
            <a href="/berichten.php" class="btn btn-small section-title-action">Beheren</a>
        <?php endif; ?>
    </h2>

    <?php if (!$berichten): ?>
        <div class="empty">Nog geen berichten geplaatst.</div>
    <?php else: ?>
        <div class="bericht-list">
            <?php foreach ($berichten as $b): ?>
                <article class="bericht-card">
                    <h3><?= e($b['titel']) ?></h3>
                    <p><?= nl2br(e($b['inhoud'])) ?></p>
                    <?php if (!empty($b['url'])): ?>
                        <p><a href="<?= e($b['url']) ?>" target="_blank" rel="noopener" class="bericht-link">&#128279; <?= e($b['url']) ?></a></p>
                    <?php endif; ?>
                    <p class="section-note">
                        <?= e($b['auteur_naam'] ?: 'Onbekend') ?>
                        &middot; <?= (new DateTime($b['aangemaakt_op']))->format('d-m-Y H:i') ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
