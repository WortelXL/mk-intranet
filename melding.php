<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$melding = $id ? get_afgeronde_melding($pdo, $id) : null;

$labels = [];
$notities = [];
$protocollen = [];
$losse_taken = [];
$tijdvakken = [];

if ($melding) {
    $labels = get_labels_per_melding($pdo, [$melding['id']])[$melding['id']] ?? [];
    $notities = get_notities_voor_melding($pdo, $melding['id']);
    $protocollen = get_protocollen_voor_melding($pdo, $melding['id']);
    $losse_taken = get_losse_taken_voor_melding($pdo, $melding['id']);

    $geschiedenis = get_status_geschiedenis($pdo, $melding['id']);
    $afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));
    $tijdvakken = bereken_status_tijdvakken($geschiedenis, $afgeronde_sleutels, $melding['bijgewerkt_op']);
}

$actief = 'archief';
$paginatitel = $melding ? $melding['meld_id'] : 'Melding niet gevonden';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/archief.php" class="back-link">&larr; Archief</a></p>
        <?php if ($melding): ?>
            <h1><?= e($melding['meld_id']) ?> &middot; <?= e($melding['titel']) ?></h1>
            <p>Alleen-lezen. Wijzigen doe je in het meldkamersysteem zelf.</p>
        <?php else: ?>
            <h1>Melding niet gevonden</h1>
        <?php endif; ?>
    </div>
</div>

<?php if (!$melding): ?>
    <div class="empty">Deze melding staat niet (meer) in het archief, of bestaat niet.</div>
<?php else: ?>

    <div class="panel">
        <div class="melding-detail-tags">
            <?php if ($melding['hoofd_naam']): ?>
                <span class="cat-chip" style="background: <?= e($melding['hoofd_kleur']) ?>22; color: <?= e($melding['hoofd_kleur']) ?>;">
                    <?= e($melding['hoofd_naam']) ?><?= $melding['sub_naam'] ? ' &middot; ' . e($melding['sub_naam']) : '' ?>
                </span>
            <?php endif; ?>
            <span class="tag" style="background:<?= e(prioriteit_kleur($melding['prioriteit'])) ?>22; color:<?= e(prioriteit_kleur($melding['prioriteit'])) ?>;">
                <?= e(prioriteit_label($melding['prioriteit'])) ?>
            </span>
            <span class="tag" style="background:<?= e(status_kleur($pdo, $melding['status'])) ?>22; color:<?= e(status_kleur($pdo, $melding['status'])) ?>;">
                <?= e(status_label($pdo, $melding['status'])) ?>
            </span>
            <?php foreach ($labels as $l): ?>
                <span class="label-chip" style="background: <?= e($l['kleur']) ?>22; color: <?= e($l['kleur']) ?>;"><?= e($l['naam']) ?></span>
            <?php endforeach; ?>
        </div>
        <dl class="detail-lijst">
            <div><dt>Locatie</dt><dd><?= e($melding['locatie'] ?: '—') ?></dd></div>
            <div><dt>Gemeld door</dt><dd><?= e($melding['gemeld_door'] ?: '—') ?></dd></div>
            <div><dt>Ingevoerd door</dt><dd><?= e($melding['aangemaakt_door_naam'] ?: '—') ?></dd></div>
            <div><dt>Laatst bijgewerkt door</dt><dd><?= e($melding['bijgewerkt_door_naam'] ?: '—') ?></dd></div>
            <div><dt>Aangemaakt</dt><dd><?= (new DateTime($melding['aangemaakt_op']))->format('d-m-Y H:i') ?></dd></div>
            <div><dt>Laatst bijgewerkt</dt><dd><?= (new DateTime($melding['bijgewerkt_op']))->format('d-m-Y H:i') ?></dd></div>
        </dl>
        <?php if (!empty($melding['omschrijving'])): ?>
            <h3 class="detail-subkop">Omschrijving</h3>
            <p class="detail-tekst"><?= nl2br(e($melding['omschrijving'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($tijdvakken): ?>
        <?php $totale_seconden = array_sum(array_column($tijdvakken, 'duur_seconden')); ?>
        <div class="panel">
            <h3>Statusverloop <span class="count-badge">totaal <?= e(format_duur($totale_seconden)) ?></span></h3>
            <div class="tabel-scroll">
            <table class="admin-table">
                <thead><tr><th>Status</th><th>Van</th><th>Tot</th><th>Duur</th><th>Door</th></tr></thead>
                <tbody>
                <?php foreach ($tijdvakken as $v): ?>
                    <tr>
                        <td><?= e(status_label($pdo, $v['status'])) ?></td>
                        <td class="mono muted"><?= $v['van']->format('d-m-Y H:i') ?></td>
                        <td class="mono muted"><?= $v['lopend'] ? 'nu' : $v['tot']->format('d-m-Y H:i') ?></td>
                        <td><?= e(format_duur($v['duur_seconden'])) ?><?= $v['lopend'] ? ' (loopt nog)' : '' ?></td>
                        <td class="muted"><?= e($v['gebruiker'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel">
        <h3>Logboek <span class="count-badge"><?= count($notities) ?></span></h3>
        <?php if (!$notities): ?>
            <p class="melding-log-leeg">Nog geen logboekregels voor deze melding.</p>
        <?php else: ?>
            <?php foreach ($notities as $n): ?>
                <p class="melding-log-regel">
                    <span class="melding-log-tijd"><?= (new DateTime($n['aangemaakt_op']))->format('d-m-Y H:i') ?></span>
                    <span class="melding-log-auteur"><?= e($n['auteur'] ?: 'Onbekend') ?>:</span>
                    <?= nl2br(e($n['notitie'])) ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($protocollen): ?>
        <div class="panel">
            <h3>Gekoppelde protocollen <span class="count-badge"><?= count($protocollen) ?></span></h3>
            <?php foreach ($protocollen as $p): ?>
                <div class="protocol-blok">
                    <h4><?= e($p['titel']) ?></h4>
                    <p class="detail-tekst"><?= nl2br(e($p['inhoud'])) ?></p>
                    <?php if ($p['subtaken']): ?>
                        <ul class="taak-lijst">
                            <?php foreach ($p['subtaken'] as $t): ?>
                                <li class="taak-item">
                                    <input type="checkbox" disabled <?= $t['afgevinkt'] ? 'checked' : '' ?>>
                                    <span><?= e($t['omschrijving']) ?>
                                        <?php if ($t['afgevinkt']): ?>
                                            <span class="taak-meta">— afgevinkt door <?= e($t['afgevinkt_door_naam'] ?: 'onbekend') ?> op <?= (new DateTime($t['afgevinkt_op']))->format('d-m-Y H:i') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($losse_taken): ?>
        <div class="panel">
            <h3>Losse taken <span class="count-badge"><?= count($losse_taken) ?></span></h3>
            <ul class="taak-lijst">
                <?php foreach ($losse_taken as $t): ?>
                    <li class="taak-item">
                        <input type="checkbox" disabled <?= $t['afgevinkt'] ? 'checked' : '' ?>>
                        <span><?= e($t['omschrijving']) ?>
                            <?php if ($t['afgevinkt']): ?>
                                <span class="taak-meta">— afgevinkt door <?= e($t['afgevinkt_door_naam'] ?: 'onbekend') ?> op <?= (new DateTime($t['afgevinkt_op']))->format('d-m-Y H:i') ?></span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
