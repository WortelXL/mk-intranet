<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

/* ---- Crew: toevoegen / bewerken / verwijderen -------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if ($actie === 'crew_opslaan') {
        $id             = (int) ($_POST['id'] ?? 0);
        $naam           = trim($_POST['naam'] ?? '');
        $functie        = trim($_POST['functie'] ?? '');
        $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');

        if ($naam === '') {
            $fout = 'Vul een naam in.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE crew SET naam = :n, functie = :f, telefoonnummer = :t WHERE id = :id');
            $stmt->execute(['n' => $naam, 'f' => $functie ?: null, 't' => $telefoonnummer ?: null, 'id' => $id]);
            $succes = 'Crewlid bijgewerkt.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO crew (naam, functie, telefoonnummer) VALUES (:n, :f, :t)');
            $stmt->execute(['n' => $naam, 'f' => $functie ?: null, 't' => $telefoonnummer ?: null]);
            $succes = 'Crewlid "' . $naam . '" is toegevoegd.';
        }
    }

    if ($actie === 'crew_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM crew WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Crewlid verwijderd.';
    }
}

if (isset($_GET['bewerk'])) {
    $stmt = $pdo->prepare('SELECT * FROM crew WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['bewerk']]);
    $bewerk = $stmt->fetch() ?: null;
}

$crew = get_crew($pdo);

/* ---- Meldingen: alleen-lezen overzicht ---------------------------------- */
$meldingen = get_actieve_meldingen($pdo);
$actieve_statussen = get_actieve_statussen($pdo);
$tellingen = get_status_tellingen($pdo);
$kritiek_open = 0;
foreach ($meldingen as $m) {
    if ($m['prioriteit'] === 'kritiek') {
        $kritiek_open++;
    }
}

$paginatitel = 'Intranet';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Welkom, <?= e(huidige_gebruiker_naam()) ?></p>
        <h1>MK Intranet</h1>
        <p>Live overzicht van lopende meldingen en de crewlijst van het meldkamersysteem.</p>
    </div>
    <a href="#" onclick="location.reload(); return false;" class="btn">Vernieuwen</a>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

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
            <div class="melding-row">
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
            </div>
        <?php endforeach; ?>
    </div>
    <p class="section-note">Alleen-lezen — meldingen zelf beheer je in het meldkamersysteem.</p>
</section>

<section class="section">
    <h2 class="section-title">Crew</h2>

    <div class="panel">
        <h3><?= $bewerk ? 'Crewlid bewerken' : 'Nieuw crewlid' ?></h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="actie" value="crew_opslaan">
            <input type="hidden" name="id" value="<?= $bewerk['id'] ?? 0 ?>">
            <div class="field">
                <label for="naam">Naam</label>
                <input type="text" id="naam" name="naam" required value="<?= e($bewerk['naam'] ?? '') ?>" placeholder="bv. Jan de Boer">
            </div>
            <div class="field">
                <label for="functie">Functie</label>
                <input type="text" id="functie" name="functie" value="<?= e($bewerk['functie'] ?? '') ?>" placeholder="bv. EHBO, Beveiliging, Techniek">
            </div>
            <div class="field">
                <label for="telefoonnummer">Telefoonnummer</label>
                <input type="text" id="telefoonnummer" name="telefoonnummer" value="<?= e($bewerk['telefoonnummer'] ?? '') ?>" placeholder="bv. 06-12345678">
            </div>
            <div class="actions full">
                <button type="submit" class="btn btn-primary"><?= $bewerk ? 'Wijzigingen opslaan' : 'Crewlid toevoegen' ?></button>
                <?php if ($bewerk): ?>
                    <a href="/index.php" class="btn">Annuleren</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3>Crewlijst <span class="count-badge"><?= count($crew) ?></span></h3>
        <?php if (!$crew): ?>
            <p class="section-note">Nog geen crewleden toegevoegd.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr><th>Naam</th><th>Functie</th><th>Telefoonnummer</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($crew as $c): ?>
                <tr>
                    <td><?= e($c['naam']) ?></td>
                    <td class="muted"><?= e($c['functie'] ?: '—') ?></td>
                    <td class="mono muted">
                        <?= $c['telefoonnummer'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $c['telefoonnummer'])) . '">' . e($c['telefoonnummer']) . '</a>' : '—' ?>
                    </td>
                    <td class="nowrap">
                        <a href="/index.php?bewerk=<?= $c['id'] ?>#crew" class="btn btn-small">Bewerken</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Crewlid \'<?= e($c['naam']) ?>\' verwijderen?');">
                            <input type="hidden" name="actie" value="crew_verwijderen">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
