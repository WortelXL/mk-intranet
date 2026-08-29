<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$hoofdclassificaties = get_hoofdclassificaties($pdo);

$gekozen_hoofd_id  = isset($_GET['hoofd']) && $_GET['hoofd'] !== '' ? (int) $_GET['hoofd'] : null;
$gekozen_prioriteit = $_GET['prioriteit'] ?? '';
$gekozen_prioriteit = in_array($gekozen_prioriteit, ['laag', 'normaal', 'hoog', 'kritiek'], true) ? $gekozen_prioriteit : null;

$meldingen = get_archief_meldingen($pdo, $gekozen_hoofd_id, $gekozen_prioriteit);

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
            <label for="prioriteit">Prioriteit</label>
            <select id="prioriteit" name="prioriteit">
                <option value="">Alle prioriteiten</option>
                <?php foreach (['kritiek', 'hoog', 'normaal', 'laag'] as $p): ?>
                    <option value="<?= $p ?>" <?= $gekozen_prioriteit === $p ? 'selected' : '' ?>><?= e(prioriteit_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Filteren</button>
            <?php if ($gekozen_hoofd_id || $gekozen_prioriteit): ?>
                <a href="/archief.php" class="btn">Wis filters</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<section class="section">
    <h2 class="section-title">Afgeronde meldingen <span class="count-badge"><?= count($meldingen) ?></span></h2>

    <div class="melding-list">
        <?php if (!$meldingen): ?>
            <div class="empty">Geen afgeronde meldingen gevonden<?= ($gekozen_hoofd_id || $gekozen_prioriteit) ? ' voor deze filters' : '' ?>.</div>
        <?php endif; ?>
        <?php foreach ($meldingen as $m): ?>
            <div class="melding-row">
                <span class="melding-id"><?= e($m['meld_id']) ?></span>
                <span class="melding-main">
                    <span class="titel"><?= e($m['titel']) ?></span>
                    <span class="meta">
                        <?= e($m['locatie'] ?: 'Geen locatie opgegeven') ?>
                        &middot; <?= (new DateTime($m['aangemaakt_op']))->format('d-m-Y H:i') ?>
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
    <p class="section-note">Alleen-lezen, maximaal 150 resultaten. Meldingen zelf beheer je in het meldkamersysteem.</p>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
