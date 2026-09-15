<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$berichten = get_berichten($pdo);
$links_per_bericht = get_links_per_bericht($pdo, array_column($berichten, 'id'));

$actief = 'alle_berichten';
$paginatitel = 'Berichten';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet &middot; Event</p>
        <h1>Berichten</h1>
        <p>Alle mededelingen, inclusief eerder geplaatste. Het dashboard toont alleen de 3 meest recente.</p>
    </div>
</div>

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
                        &middot; <span class="tag-geldig"><?= new DateTime($b['geldig_tot']) < new DateTime() ? 'verlopen op' : 'geldig tot' ?> <?= (new DateTime($b['geldig_tot']))->format('d-m-Y H:i') ?></span>
                    <?php endif; ?>
                </p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
