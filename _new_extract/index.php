<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

/* ---- Berichten: alleen-lezen, beheren gebeurt op berichten.php --------- */
$berichten = get_berichten($pdo, 20);
$links_per_bericht = get_links_per_bericht($pdo, array_column($berichten, 'id'));

$actief = 'dashboard';
$paginatitel = 'Intranet';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Welkom, <?= e(huidige_gebruiker_naam()) ?></p>
        <h1>MK Intranet</h1>
        <p>Mededelingen van het meldkamersysteem. Lopende meldingen vind je onder "Meldingen" in de navigatie.</p>
    </div>
</div>

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
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
