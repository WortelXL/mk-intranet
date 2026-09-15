<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$categorieen = get_kb_categorieen($pdo);
$documenten_per_categorie = get_kb_documenten_gegroepeerd($pdo);
$alle_documenten = $documenten_per_categorie ? array_merge(...array_values($documenten_per_categorie)) : [];
$links_per_document = get_kb_document_links($pdo, array_column($alle_documenten, 'id'));

$actief = 'documenten';
$paginatitel = 'Documenten';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet &middot; Event</p>
        <h1>Documenten</h1>
        <p>Draaiboeken, protocollen en ander naslagmateriaal per categorie.</p>
    </div>
</div>

<div class="panel">
    <input type="text" id="kb-doc-zoek" class="input-small kb-zoekveld" placeholder="Zoeken in documenten...">
</div>

<?php if (!$categorieen): ?>
    <div class="empty">Nog geen categorieen of documenten toegevoegd. Een beheerder richt dit in via Beheer &gt; Kennisbank beheren.</div>
<?php else: ?>
    <?php foreach ($categorieen as $categorie): ?>
        <?php $documenten = $documenten_per_categorie[$categorie['id']] ?? []; ?>
        <?php if (!$documenten): continue; endif; ?>
        <div class="panel kb-categorie-blok">
            <h3><?= e($categorie['naam']) ?></h3>
            <div class="kb-doc-lijst">
                <?php foreach ($documenten as $doc): ?>
                    <article class="kb-doc-item">
                        <h4><?= e($doc['titel']) ?></h4>
                        <?php if ($doc['toelichting']): ?>
                            <p class="muted"><?= nl2br(e($doc['toelichting'])) ?></p>
                        <?php endif; ?>
                        <?php $links = $links_per_document[$doc['id']] ?? []; ?>
                        <?php if ($links): ?>
                            <div class="kb-item-links">
                                <?php foreach ($links as $link): ?>
                                    <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="btn btn-small"><?= e($link['label']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
(function () {
    var zoekveld = document.getElementById('kb-doc-zoek');
    if (!zoekveld) return;
    var blokken = document.querySelectorAll('.kb-categorie-blok');
    zoekveld.addEventListener('input', function () {
        var term = zoekveld.value.trim().toLowerCase();
        blokken.forEach(function (blok) {
            var zichtbaarInBlok = false;
            blok.querySelectorAll('.kb-doc-item').forEach(function (item) {
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
