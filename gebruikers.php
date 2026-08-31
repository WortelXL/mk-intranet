<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';

// Zelfde beveiligingen als het meldkamersysteem zelf hanteert voor deze
// gedeelde gebruikerstabel: er moet altijd minstens één actieve beheerder
// overblijven, en je kunt je eigen account niet verwijderen.
function mk_intranet_aantal_actieve_beheerders(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM gebruikers WHERE rol = 'beheerder' AND actief = 1"
    )->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if ($actie === 'aanmaken') {
        $naam           = trim($_POST['naam'] ?? '');
        $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
        $wachtwoord     = $_POST['wachtwoord'] ?? '';
        $rol            = $_POST['rol'] ?? 'medewerker';
        $functie        = trim($_POST['functie'] ?? '');
        $mag_mkapp      = isset($_POST['mag_mkapp']) ? 1 : 0;
        $mag_mkintranet = isset($_POST['mag_mkintranet']) ? 1 : 0;

        if ($naam === '' || $gebruikersnaam === '' || $wachtwoord === '') {
            $fout = 'Vul naam, gebruikersnaam en wachtwoord in.';
        } elseif (strlen($wachtwoord) < 8) {
            $fout = 'Gebruik een wachtwoord van minimaal 8 tekens.';
        } elseif (!in_array($rol, ['beheerder', 'medewerker', 'view'], true)) {
            $fout = 'Ongeldige rol.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO gebruikers (gebruikersnaam, wachtwoord_hash, naam, rol, functie, mag_inloggen_mkapp, mag_inloggen_mkintranet) VALUES (:u, :p, :n, :r, :f, :ma, :mi)'
                );
                $stmt->execute([
                    'u'  => $gebruikersnaam,
                    'p'  => password_hash($wachtwoord, PASSWORD_DEFAULT),
                    'n'  => $naam,
                    'r'  => $rol,
                    'f'  => $functie ?: null,
                    'ma' => $mag_mkapp,
                    'mi' => $mag_mkintranet,
                ]);
                $succes = 'Gebruiker "' . $naam . '" is aangemaakt.';
            } catch (PDOException $e) {
                $fout = (int) $e->getCode() === 23000
                    ? 'Deze gebruikersnaam is al in gebruik.'
                    : 'Er ging iets mis bij het aanmaken van de gebruiker.';
            }
        }
    }

    if ($actie === 'rol_wijzigen') {
        $id  = (int) ($_POST['id'] ?? 0);
        $rol = $_POST['rol'] ?? 'medewerker';
        if (in_array($rol, ['beheerder', 'medewerker', 'view'], true)) {
            $huidige = $pdo->prepare('SELECT rol, actief FROM gebruikers WHERE id = :id');
            $huidige->execute(['id' => $id]);
            $rij = $huidige->fetch();
            if ($rij && $rij['rol'] === 'beheerder' && $rol !== 'beheerder' && $rij['actief']
                && mk_intranet_aantal_actieve_beheerders($pdo) <= 1) {
                $fout = 'Dit is de laatste actieve beheerder; wijzig eerst een andere gebruiker naar beheerder.';
            } else {
                $stmt = $pdo->prepare('UPDATE gebruikers SET rol = :r WHERE id = :id');
                $stmt->execute(['r' => $rol, 'id' => $id]);
                $succes = 'Rol bijgewerkt.';
            }
        }
    }

    if ($actie === 'actief_wisselen') {
        $id = (int) ($_POST['id'] ?? 0);
        $rij_stmt = $pdo->prepare('SELECT rol, actief FROM gebruikers WHERE id = :id');
        $rij_stmt->execute(['id' => $id]);
        $rij = $rij_stmt->fetch();
        if ($rij) {
            if ($rij['rol'] === 'beheerder' && $rij['actief'] && mk_intranet_aantal_actieve_beheerders($pdo) <= 1) {
                $fout = 'Je kunt de laatste actieve beheerder niet deactiveren.';
            } else {
                $stmt = $pdo->prepare('UPDATE gebruikers SET actief = NOT actief WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $succes = 'Status bijgewerkt.';
            }
        }
    }

    if ($actie === 'functie_wijzigen') {
        $id      = (int) ($_POST['id'] ?? 0);
        $functie = trim($_POST['functie'] ?? '');
        $stmt = $pdo->prepare('UPDATE gebruikers SET functie = :f WHERE id = :id');
        $stmt->execute(['f' => $functie ?: null, 'id' => $id]);
        $succes = 'Functie bijgewerkt.';
    }

    if ($actie === 'toegang_wijzigen') {
        $id             = (int) ($_POST['id'] ?? 0);
        $mag_mkapp      = isset($_POST['mag_mkapp']) ? 1 : 0;
        $mag_mkintranet = isset($_POST['mag_mkintranet']) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE gebruikers SET mag_inloggen_mkapp = :a, mag_inloggen_mkintranet = :i WHERE id = :id');
        $stmt->execute(['a' => $mag_mkapp, 'i' => $mag_mkintranet, 'id' => $id]);
        $succes = 'Toegang bijgewerkt.';
    }

    if ($actie === 'wachtwoord_wijzigen') {
        $id               = (int) ($_POST['id'] ?? 0);
        $nieuw_wachtwoord = $_POST['nieuw_wachtwoord'] ?? '';

        if (strlen($nieuw_wachtwoord) < 8) {
            $fout = 'Gebruik een wachtwoord van minimaal 8 tekens.';
        } else {
            $stmt = $pdo->prepare('UPDATE gebruikers SET wachtwoord_hash = :h WHERE id = :id');
            $stmt->execute(['h' => password_hash($nieuw_wachtwoord, PASSWORD_DEFAULT), 'id' => $id]);
            $succes = 'Wachtwoord bijgewerkt.';
        }
    }

    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $rij_stmt = $pdo->prepare('SELECT rol, actief FROM gebruikers WHERE id = :id');
        $rij_stmt->execute(['id' => $id]);
        $rij = $rij_stmt->fetch();
        if ($id === (int) $_SESSION['gebruiker_id']) {
            $fout = 'Je kunt je eigen account niet verwijderen.';
        } elseif ($rij && $rij['rol'] === 'beheerder' && $rij['actief'] && mk_intranet_aantal_actieve_beheerders($pdo) <= 1) {
            $fout = 'Je kunt de laatste actieve beheerder niet verwijderen.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM gebruikers WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $succes = 'Gebruiker verwijderd.';
        }
    }
}

$gebruikers = $pdo->query('SELECT * FROM gebruikers ORDER BY actief DESC, naam ASC')->fetchAll();

$actief = 'beheer';
$paginatitel = 'Gebruikers beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Gebruikers beheren</h1>
        <p>Dit is dezelfde inlogtabel als het meldkamersysteem — een account dat je hier aanmaakt of wijzigt, werkt (of verandert) daar ook meteen mee.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3>Nieuwe gebruiker</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="aanmaken">
        <div class="field">
            <label for="naam">Volledige naam</label>
            <input type="text" id="naam" name="naam" required placeholder="bv. Sanne de Vries">
        </div>
        <div class="field">
            <label for="gebruikersnaam">Gebruikersnaam</label>
            <input type="text" id="gebruikersnaam" name="gebruikersnaam" required placeholder="bv. sanne">
        </div>
        <div class="field">
            <label for="wachtwoord">Tijdelijk wachtwoord</label>
            <input type="password" id="wachtwoord" name="wachtwoord" required minlength="8">
        </div>
        <div class="field">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="medewerker">Medewerker</option>
                <option value="beheerder">Beheerder</option>
                <option value="view">Viewer</option>
            </select>
        </div>
        <div class="field">
            <label for="functie">Functie (optioneel)</label>
            <input type="text" id="functie" name="functie" placeholder="bv. Centralist, Hoofd EHBO">
        </div>
        <div class="field field-full">
            <label style="text-transform:none; font-size:13px; color:var(--text); font-weight:500;">Mag inloggen in</label>
            <div class="toegang-vinkjes">
                <label class="toegang-checkbox">
                    <input type="checkbox" name="mag_mkapp" value="1" checked> het meldkamersysteem (MK)
                </label>
                <label class="toegang-checkbox">
                    <input type="checkbox" name="mag_mkintranet" value="1" checked> MK Intranet
                </label>
            </div>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Gebruiker aanmaken</button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Bestaande gebruikers <span class="count-badge"><?= count($gebruikers) ?></span></h3>
    <div class="tabel-scroll">
    <table class="admin-table">
        <thead>
            <tr><th>Naam</th><th>Gebruikersnaam</th><th>Rol</th><th>Functie</th><th>Status</th><th>Toegang</th><th>Wachtwoord</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($gebruikers as $g): ?>
            <tr>
                <td><?= e($g['naam']) ?><?= (int) $g['id'] === (int) $_SESSION['gebruiker_id'] ? ' <span class="muted">(jij)</span>' : '' ?></td>
                <td class="mono muted"><?= e($g['gebruikersnaam']) ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="rol_wijzigen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <select name="rol" onchange="this.form.submit()" class="select-small">
                            <option value="medewerker" <?= $g['rol'] === 'medewerker' ? 'selected' : '' ?>>Medewerker</option>
                            <option value="beheerder" <?= $g['rol'] === 'beheerder' ? 'selected' : '' ?>>Beheerder</option>
                            <option value="view" <?= $g['rol'] === 'view' ? 'selected' : '' ?>>Viewer</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="functie_wijzigen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <input type="text" name="functie" value="<?= e($g['functie'] ?? '') ?>" placeholder="bv. Centralist" class="input-small">
                        <button type="submit" class="btn btn-small">Opslaan</button>
                    </form>
                </td>
                <td>
                    <span class="tag" style="background: <?= $g['actief'] ? '#3fae6a22' : '#8888882e' ?>; color: <?= $g['actief'] ? '#3fae6a' : 'var(--muted)' ?>;">
                        <?= $g['actief'] ? 'Actief' : 'Gedeactiveerd' ?>
                    </span>
                </td>
                <td class="nowrap">
                    <form method="post" class="inline-form toegang-form">
                        <input type="hidden" name="actie" value="toegang_wijzigen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <label class="toegang-checkbox" title="Mag inloggen in het meldkamersysteem">
                            <input type="checkbox" name="mag_mkapp" value="1" <?= gebruiker_mag_inloggen($g, 'mag_inloggen_mkapp') ? 'checked' : '' ?> onchange="this.form.submit()"> MK
                        </label>
                        <label class="toegang-checkbox" title="Mag inloggen in MK Intranet">
                            <input type="checkbox" name="mag_mkintranet" value="1" <?= gebruiker_mag_inloggen($g, 'mag_inloggen_mkintranet') ? 'checked' : '' ?> onchange="this.form.submit()"> Intranet
                        </label>
                    </form>
                </td>
                <td class="nowrap">
                    <form method="post" class="inline-form" onsubmit="return confirm('Wachtwoord van \'<?= e($g['naam']) ?>\' wijzigen naar het ingevulde wachtwoord?');">
                        <input type="hidden" name="actie" value="wachtwoord_wijzigen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <input type="password" name="nieuw_wachtwoord" placeholder="nieuw wachtwoord" minlength="8" class="input-small" required>
                        <button type="submit" class="btn btn-small">Wijzigen</button>
                    </form>
                </td>
                <td class="nowrap">
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="actief_wisselen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn btn-small"><?= $g['actief'] ? 'Deactiveren' : 'Activeren' ?></button>
                    </form>
                    <form method="post" class="inline-form" onsubmit="return confirm('Gebruiker \'<?= e($g['naam']) ?>\' definitief verwijderen?');">
                        <input type="hidden" name="actie" value="verwijderen">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="section-note">Er blijft altijd minstens één actieve beheerder over — rol wijzigen, deactiveren en verwijderen worden geweigerd als dat de laatste zou zijn. Je eigen account kun je hier niet verwijderen. De kolom "Toegang" bepaalt of iemand mag inloggen in het meldkamersysteem (MK) en/of MK Intranet — let op dat je jezelf of de laatste beheerder niet per ongeluk overal buitensluit.</p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
