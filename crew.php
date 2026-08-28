<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

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

$actief = 'crew';
$paginatitel = 'Crew';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Crew</h1>
        <p>Contactpersonen zonder eigen login — een telefoonlijst, gedeeld met het meldkamersysteem.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

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
                <a href="/crew.php" class="btn">Annuleren</a>
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
                    <a href="/crew.php?bewerk=<?= $c['id'] ?>" class="btn btn-small">Bewerken</a>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
