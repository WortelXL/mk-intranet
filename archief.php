<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$hoofdclassificaties = get_hoofdclassificaties($pdo);
$subclassificaties = get_subclassificaties($pdo);
$labels = get_labels($pdo);

$gekozen_hoofd_id  = isset($_GET['hoofd']) && $_GET['hoofd'] !== '' ? (int) $_GET['hoofd'] : null;
$gekozen_sub_id = isset($_GET['sub']) && $_GET['sub'] !== '' ? (int) $_GET['sub'] : null;
$gekozen_prioriteit = $_GET['prioriteit'] ?? '';
$gekozen_prioriteit = in_array($gekozen_prioriteit, ['laag', 'normaal', 'hoog', 'kritiek'], true) ? $gekozen_prioriteit : null;
$gekozen_label_id = isset($_GET['label']) && $_GET['label'] !== '' ? (int) $_GET['label'] : null;

$meldingen = get_archief_meldingen($pdo, $gekozen_hoofd_id, $gekozen_prioriteit, $gekozen_label_id, $gekozen_sub_id);
$labels_per_melding = get_labels_per_melding($pdo, array_column($meldingen, 'id'));

// Query-string voor de exportlink: precies dezelfde filters als hierboven.
$export_query = http_build_query(array_filter([
    'hoofd'      => $gekozen_hoofd_id,
    'sub'        => $gekozen_sub_id,
    'prioriteit' => $gekozen_prioriteit,
    'label'      => $gekozen_label_id,
]));

$actief = 'archief';
$paginatitel = 'Archief';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Archief</h1>
        <p>Afgeronde meldingen uit het meldkamersysteem, alleen-lezen.</p>
    </div>
    <a href="/export.php<?= $export_query ? '?' . $export_query : '' ?>" class="btn">Exporteren naar PDF (alles, huidige filters)</a>
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
            <label for="prioriteit">Prioriteit</label>
            <select id="prioriteit" name="prioriteit">
                <option value="">Alle prioriteiten</option>
                <?php foreach (['kritiek', 'hoog', 'normaal', 'laag'] as $p): ?>
                    <option value="<?= $p ?>" <?= $gekozen_prioriteit === $p ? 'selected' : '' ?>><?= e(prioriteit_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="label">Label</label>
            <select id="label" name="label">
                <option value="">Alle labels</option>
                <?php foreach ($labels as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $gekozen_label_id === (int) $l['id'] ? 'selected' : '' ?>><?= e($l['naam']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Filteren</button>
            <?php if ($gekozen_hoofd_id || $gekozen_sub_id || $gekozen_prioriteit || $gekozen_label_id): ?>
                <a href="/archief.php" class="btn">Wis filters</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<section class="section">
    <h2 class="section-title">Afgeronde meldingen <span class="count-badge"><?= count($meldingen) ?></span></h2>

    <form method="post" action="/export.php">
    <div class="melding-list">
        <?php if (!$meldingen): ?>
            <div class="empty">Geen afgeronde meldingen gevonden<?= ($gekozen_hoofd_id || $gekozen_sub_id || $gekozen_prioriteit || $gekozen_label_id) ? ' voor deze filters' : '' ?>.</div>
        <?php endif; ?>

        <?php if ($meldingen): ?>
            <div class="selectie-balk">
                <input type="checkbox" id="selecteer-alles" onchange="document.querySelectorAll('.export-checkbox').forEach(function(c){ c.checked = this.checked; })">
                <label for="selecteer-alles">Alles selecteren (<?= count($meldingen) ?>)</label>
                <span class="selectie-balk-spacer"></span>
                <button type="submit" class="btn btn-small">Exporteren naar PDF (selectie)</button>
            </div>
        <?php endif; ?>

        <?php foreach ($meldingen as $m): ?>
            <div class="melding-row archief-row">
                <input type="checkbox" name="ids[]" value="<?= (int) $m['id'] ?>" class="export-checkbox">
                <a href="/melding.php?id=<?= (int) $m['id'] ?>" class="melding-id melding-link"><?= e($m['meld_id']) ?></a>
                <span class="melding-main">
                    <a href="/melding.php?id=<?= (int) $m['id'] ?>" class="titel melding-link"><?= e($m['titel']) ?></a>
                    <span class="meta">
                        <?= e($m['locatie'] ?: 'Geen locatie opgegeven') ?>
                        &middot; <?= (new DateTime($m['aangemaakt_op']))->format('d-m-Y H:i') ?>
                        <?php if (!empty($labels_per_melding[$m['id']])): ?>
                            &middot;
                            <?php foreach ($labels_per_melding[$m['id']] as $l): ?>
                                <span class="label-chip" style="background: <?= e($l['kleur']) ?>22; color: <?= e($l['kleur']) ?>;"><?= e($l['naam']) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
            </div>
        <?php endforeach; ?>
    </div>
    </form>
    <p class="section-note">Alleen-lezen, maximaal 150 resultaten. Meldingen zelf beheer je in het meldkamersysteem. Vink meldingen aan om alleen die te exporteren, of gebruik de knop hierboven om alles binnen de huidige filters te exporteren.</p>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
