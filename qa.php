<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$categorieen = get_kb_categorieen($pdo);
$items_per_categorie = get_kb_items_gegroepeerd($pdo);
$alle_items = $items_per_categorie ? array_merge(...array_values($items_per_categorie)) : [];
$links_per_item = get_kb_item_links($pdo, array_column($alle_items, 'id'));

$actief = 'qa';
$paginatitel = 'Q&A';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet &middot; Event</p>
        <h1>Q&amp;A</h1>
        <p>Vragen en antwoorden per categorie. Klik op een vraag om 'm open- of dicht te klappen.</p>
    </div>
</div>

<div class="panel">
    <input type="text" id="kb-qa-zoek" class="input-small kb-zoekveld" placeholder="Zoeken in vragen en antwoorden...">
</div>

<?php if (!$categorieen): ?>
    <div class="empty">Nog geen categorieen of vragen toegevoegd. Een beheerder richt dit in via Beheer &gt; Kennisbank beheren.</div>
<?php else: ?>
    <?php foreach ($categorieen as $categorie): ?>
        <?php $items = $items_per_categorie[$categorie['id']] ?? []; ?>
        <?php if (!$items): continue; endif; ?>
        <div class="panel kb-categorie-blok">
            <h3><?= e($categorie['naam']) ?></h3>
            <div class="kb-item-lijst">
                <?php foreach ($items as $item): ?>
                    <details class="kb-item">
                        <summary class="kb-item-vraag"><?= e($item['vraag']) ?></summary>
                        <div class="kb-item-antwoord">
                            <p><?= nl2br(e($item['antwoord'])) ?></p>
                            <?php $links = $links_per_item[$item['id']] ?? []; ?>
                            <?php if ($links): ?>
                                <div class="kb-item-links">
                                    <?php foreach ($links as $link): ?>
                                        <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="btn btn-small"><?= e($link['label']) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
    var zoekveld = document.getElementById('kb-qa-zoek');
    if (!zoekveld) return;
    var blokken = document.querySelectorAll('.kb-categorie-blok');
    zoekveld.addEventListener('input', function () {
        var term = zoekveld.value.trim().toLowerCase();
        blokken.forEach(function (blok) {
            var zichtbaarInBlok = false;
            blok.querySelectorAll('.kb-item').forEach(function (item) {
                var tekst = item.textContent.toLowerCase();
                var treft = term === '' || tekst.indexOf(term) !== -1;
                item.hidden = !treft;
                if (treft) zichtbaarInBlok = true;
            });
            blok.hidden = !zichtbaarInBlok;
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
