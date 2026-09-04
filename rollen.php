<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if ($actie === 'aanmaken') {
        $naam = trim($_POST['naam'] ?? '');
        $niveau = $_POST['niveau'] ?? 'medewerker';
        $hoofdclassificatie_id = $_POST['hoofdclassificatie_id'] ?? '';
        $hoofdclassificatie_id = ctype_digit((string) $hoofdclassificatie_id) ? (int) $hoofdclassificatie_id : null;

        if ($naam === '') {
            $fout = 'Vul een naam voor de rol in.';
        } elseif (!in_array($niveau, ['beheerder', 'medewerker', 'view'], true)) {
            $fout = 'Ongeldig niveau.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO rollen (naam, niveau, hoofdclassificatie_id, verwijderbaar) VALUES (:n, :niv, :h, 1)'
                );
                $stmt->execute(['n' => $naam, 'niv' => $niveau, 'h' => $hoofdclassificatie_id]);
                $succes = 'Rol "' . $naam . '" is aangemaakt.';
            } catch (PDOException $e) {
                $fout = (int) $e->getCode() === 23000
                    ? 'Er bestaat al een rol met deze naam.'
                    : 'Er ging iets mis bij het aanmaken van de rol.';
            }
        }
    }

    if ($actie === 'naam_wijzigen') {
        $id = (int) ($_POST['id'] ?? 0);
        $naam = trim($_POST['naam'] ?? '');

        if ($naam === '') {
            $fout = 'De naam van een rol mag niet leeg zijn.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE rollen SET naam = :n WHERE id = :id');
                $stmt->execute(['n' => $naam, 'id' => $id]);
                $succes = 'Naam bijgewerkt.';
            } catch (PDOException $e) {
                $fout = (int) $e->getCode() === 23000
                    ? 'Er bestaat al een rol met deze naam.'
                    : 'Er ging iets mis bij het bijwerken van de naam.';
            }
        }
    }

    if ($actie === 'niveau_wijzigen') {
        $id = (int) ($_POST['id'] ?? 0);
        $niveau = $_POST['niveau'] ?? 'medewerker';

        if (!in_array($niveau, ['beheerder', 'medewerker', 'view'], true)) {
            $fout = 'Ongeldig niveau.';
        } else {
            $huidige = $pdo->prepare('SELECT niveau FROM rollen WHERE id = :id');
            $huidige->execute(['id' => $id]);
            $huidig_niveau = $huidige->fetchColumn();

            if ($huidig_niveau === 'beheerder' && $niveau !== 'beheerder' && aantal_rollen_niveau_beheerder($pdo) <= 1) {
                $fout = 'Dit is de laatste rol met niveau Beheerder; wijzig eerst een andere rol naar Beheerder.';
            } else {
                $stmt = $pdo->prepare('UPDATE rollen SET niveau = :niv WHERE id = :id');
                $stmt->execute(['niv' => $niveau, 'id' => $id]);
                $succes = 'Niveau bijgewerkt.';
            }
        }
    }

    if ($actie === 'classificatie_wijzigen') {
        $id = (int) ($_POST['id'] ?? 0);
        $hoofdclassificatie_id = $_POST['hoofdclassificatie_id'] ?? '';
        $hoofdclassificatie_id = ctype_digit((string) $hoofdclassificatie_id) ? (int) $hoofdclassificatie_id : null;

        $stmt = $pdo->prepare('UPDATE rollen SET hoofdclassificatie_id = :h WHERE id = :id');
        $stmt->execute(['h' => $hoofdclassificatie_id, 'id' => $id]);
        $succes = 'Classificatiekoppeling bijgewerkt.';
    }

    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $rij_stmt = $pdo->prepare('SELECT niveau, verwijderbaar FROM rollen WHERE id = :id');
        $rij_stmt->execute(['id' => $id]);
        $rij = $rij_stmt->fetch();

        if (!$rij) {
            $fout = 'Deze rol bestaat niet (meer).';
        } elseif (!$rij['verwijderbaar']) {
            $fout = 'Deze kernrol kan niet verwijderd worden.';
        } elseif ($rij['niveau'] === 'beheerder' && aantal_rollen_niveau_beheerder($pdo) <= 1) {
            $fout = 'Dit is de laatste rol met niveau Beheerder; kan niet verwijderd worden.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM rollen WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $succes = 'Rol verwijderd.';
        }
    }
}

$rollen = alle_rollen($pdo);
$hoofdclassificaties = get_hoofdclassificaties($pdo);

$actief = 'beheer';
$paginatitel = 'Rollen beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Rollen</h1>
        <p>Maak hier rollen aan, wijzig naam en niveau, en koppel er eventueel een hoofdclassificatie aan. Het niveau (Beheerder/Medewerker/Viewer) bepaalt de rechten van iedereen die deze rol als actieve rol heeft &mdash; wisselt iemand bijvoorbeeld naar een rol met niveau Viewer, dan gelden ook de bijbehorende beperkingen, ook al is het account zelf beheerder. Een gekoppelde hoofdclassificatie zorgt dat iemand met die rol actief alleen nog een eigen, gefilterde weergave van Overview ziet (geen Dashboard, Statistieken, Archief, Crew of Beheer meer). Zonder koppeling verandert daar niets aan.</p>
        <p class="section-note">De kernrollen Admin, Centralist en Viewer zijn niet te verwijderen (zo blijft er altijd minstens 1 rol per niveau over) &mdash; naam en niveau zijn wel aan te passen.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3>Nieuwe rol</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="aanmaken">
        <div class="field">
            <label for="naam">Naam</label>
            <input type="text" id="naam" name="naam" required placeholder="bv. Beveiliging">
        </div>
        <div class="field">
            <label for="niveau">Niveau</label>
            <select id="niveau" name="niveau">
                <option value="medewerker">Medewerker</option>
                <option value="beheerder">Beheerder</option>
                <option value="view">Viewer</option>
            </select>
        </div>
        <div class="field">
            <label for="hoofdclassificatie_id">Gekoppelde hoofdclassificatie (optioneel)</label>
            <select id="hoofdclassificatie_id" name="hoofdclassificatie_id">
                <option value="">Geen koppeling (ziet alles)</option>
                <?php foreach ($hoofdclassificaties as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= e($h['naam']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Rol aanmaken</button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Bestaande rollen <span class="count-badge"><?= count($rollen) ?></span></h3>
    <div class="tabel-scroll">
    <table class="admin-table">
        <thead>
            <tr><th>Naam</th><th>Niveau</th><th>Gekoppelde hoofdclassificatie</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rollen as $rol): ?>
            <tr>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="naam_wijzigen">
                        <input type="hidden" name="id" value="<?= $rol['id'] ?>">
                        <input type="text" name="naam" value="<?= e($rol['naam']) ?>" class="input-small">
                        <button type="submit" class="btn btn-small">Opslaan</button>
                    </form>
                </td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="niveau_wijzigen">
                        <input type="hidden" name="id" value="<?= $rol['id'] ?>">
                        <select name="niveau" onchange="this.form.submit()" class="select-small">
                            <option value="medewerker" <?= $rol['niveau'] === 'medewerker' ? 'selected' : '' ?>>Medewerker</option>
                            <option value="beheerder" <?= $rol['niveau'] === 'beheerder' ? 'selected' : '' ?>>Beheerder</option>
                            <option value="view" <?= $rol['niveau'] === 'view' ? 'selected' : '' ?>>Viewer</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="classificatie_wijzigen">
                        <input type="hidden" name="id" value="<?= $rol['id'] ?>">
                        <select name="hoofdclassificatie_id" onchange="this.form.submit()" class="select-small">
                            <option value="">Geen koppeling (ziet alles)</option>
                            <?php foreach ($hoofdclassificaties as $h): ?>
                                <option value="<?= $h['id'] ?>" <?= (int) ($rol['hoofdclassificatie_id'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>><?= e($h['naam']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="nowrap">
                    <?php if ($rol['verwijderbaar']): ?>
                    <form method="post" class="inline-form" onsubmit="return confirm('Rol \'<?= e(addslashes($rol['naam'])) ?>\' verwijderen? Gebruikers met deze rol verliezen 'm direct.');">
                        <input type="hidden" name="actie" value="verwijderen">
                        <input type="hidden" name="id" value="<?= $rol['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                    </form>
                    <?php else: ?>
                    <span class="muted">Kernrol</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="section-note">Wie welke rol heeft, stel je in bij <a href="/gebruikers.php">Beheer &gt; Gebruikers</a> (kolom "Rollen"). Heeft iemand 2 of meer rollen, dan verschijnt rechtsboven een keuzelijst om de actieve rol te wisselen.</p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
