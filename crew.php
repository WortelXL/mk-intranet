<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

// Alleen beheerders mogen crew toevoegen/bewerken/verwijderen -- net als in
// het meldkamersysteem. Medewerkers (en viewers) mogen de lijst wel zien,
// maar niet wijzigen. Dit wordt hier ook op de POST-acties zelf afgedwongen
// (niet alleen door de knoppen te verbergen), zodat een handmatig verstuurd
// formulier van een medewerker ook geweigerd wordt.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if (!is_beheerder()) {
        $fout = 'Je hebt geen rechten om de crew te bewerken.';
    } elseif ($actie === 'crew_opslaan') {
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
    } elseif ($actie === 'crew_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM crew WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Crewlid verwijderd.';
    }
}

if (is_beheerder() && isset($_GET['bewerk'])) {
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
        <p>Contactpersonen zonder eigen login — een telefoonlijst, gedeeld met het meldkamersysteem.<?= is_beheerder() ? '' : ' Alleen beheerders kunnen deze lijst bewerken.' ?></p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<?php if (is_beheerder()): ?>
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
<?php endif; ?>

<div class="panel">
    <h3>Crewlijst <span class="count-badge"><?= count($crew) ?></span></h3>
    <?php if (!$crew): ?>
        <p class="section-note">Nog geen crewleden toegevoegd.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>Naam</th><th>Functie</th><th>Telefoonnummer</th><?php if (is_beheerder()): ?><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php foreach ($crew as $c): ?>
            <tr>
                <td><?= e($c['naam']) ?></td>
                <td class="muted"><?= e($c['functie'] ?: '—') ?></td>
                <td class="mono muted">
                    <?= $c['telefoonnummer'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $c['telefoonnummer'])) . '">' . e($c['telefoonnummer']) . '</a>' : '—' ?>
                </td>
                <?php if (is_beheerder()): ?>
                <td class="nowrap">
                    <a href="/crew.php?bewerk=<?= $c['id'] ?>" class="btn btn-small">Bewerken</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Crewlid \'<?= e($c['naam']) ?>\' verwijderen?');">
                        <input type="hidden" name="actie" value="crew_verwijderen">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
