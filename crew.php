<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

// Alleen beheerders mogen crew/MDT-personen toevoegen/bewerken/verwijderen
// -- net als in het meldkamersysteem. Medewerkers (en viewers) mogen de
// lijst wel zien, maar niet wijzigen. Dit wordt hier ook op de POST-acties
// zelf afgedwongen (niet alleen door de knoppen te verbergen), zodat een
// handmatig verstuurd formulier van een medewerker ook geweigerd wordt.
//
// Sinds V0.1.16 (overgenomen uit mkapp V2.0.2.17/V2.0.2.20): Crew-
// contacten en MDT-gebruikers staan hier samen in 1 lijst, met een
// vinkje "Zichtbaar in MDT" per persoon, en kun je vanuit hier ook een
// MDT-login aanmaken/bewerken (wachtwoord/gebruikersnaam). Zelfde
// beveiligingen als gebruikers.php voor deze gedeelde gebruikerstabel:
// altijd minstens 1 actieve beheerder, geen eigen account verwijderen.
function mk_intranet_aantal_actieve_beheerders(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM gebruikers WHERE rol = 'beheerder' AND actief = 1"
    )->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if (!is_beheerder()) {
        $fout = 'Je hebt geen rechten om de crew te bewerken.';
    } elseif ($actie === 'opslaan') {
        $id             = (int) ($_POST['id'] ?? 0);
        $type           = $_POST['type'] ?? 'crew';
        $naam           = trim($_POST['naam'] ?? '');
        $functie        = trim($_POST['functie'] ?? '');
        $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');

        if ($naam === '') {
            $fout = 'Vul een naam in.';
        } elseif ($id > 0 && $type === 'crew') {
            $stmt = $pdo->prepare('UPDATE crew SET naam = :n, functie = :f, telefoonnummer = :t WHERE id = :id');
            $stmt->execute(['n' => $naam, 'f' => $functie ?: null, 't' => $telefoonnummer ?: null, 'id' => $id]);
            $succes = 'Crewlid bijgewerkt.';
        } elseif ($id > 0 && $type === 'mdt') {
            $wachtwoord = $_POST['wachtwoord'] ?? '';
            if ($wachtwoord !== '' && strlen($wachtwoord) < 8) {
                $fout = 'Gebruik een wachtwoord van minimaal 8 tekens (of laat het veld leeg om het wachtwoord niet te wijzigen).';
            } else {
                $pdo->prepare('UPDATE gebruikers SET naam = :n, functie = :f WHERE id = :id')
                    ->execute(['n' => $naam, 'f' => $functie ?: null, 'id' => $id]);
                $pdo->prepare('UPDATE mdt_gebruikers SET telefoonnummer = :t WHERE gebruiker_id = :id')
                    ->execute(['t' => $telefoonnummer ?: null, 'id' => $id]);
                if ($wachtwoord !== '') {
                    $pdo->prepare('UPDATE gebruikers SET wachtwoord_hash = :h WHERE id = :id')
                        ->execute(['h' => password_hash($wachtwoord, PASSWORD_DEFAULT), 'id' => $id]);
                }
                $succes = 'Persoon bijgewerkt.';
            }
        } elseif ($id === 0 && ($_POST['mag_inloggen_mdt'] ?? '') === '1') {
            // Nieuw persoon, meteen met MDT-login -- zelfde aanmaak-logica
            // als op Beheer > Gebruikers, nu vanuit Crew.
            $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
            $wachtwoord     = $_POST['wachtwoord'] ?? '';
            if ($gebruikersnaam === '' || $wachtwoord === '') {
                $fout = 'Vul ook gebruikersnaam en wachtwoord in voor de MDT-login.';
            } elseif (strlen($wachtwoord) < 8) {
                $fout = 'Gebruik een wachtwoord van minimaal 8 tekens.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare(
                        'INSERT INTO gebruikers (gebruikersnaam, wachtwoord_hash, naam, rol, functie, mag_inloggen_mkapp, mag_inloggen_mkintranet)
                         VALUES (:u, :p, :n, :r, :f, 0, 0)'
                    );
                    $stmt->execute([
                        'u' => $gebruikersnaam,
                        'p' => password_hash($wachtwoord, PASSWORD_DEFAULT),
                        'n' => $naam,
                        'r' => 'view',
                        'f' => $functie ?: null,
                    ]);
                    $nieuw_id = (int) $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO mdt_gebruikers (gebruiker_id, telefoonnummer) VALUES (:g, :t)')
                        ->execute(['g' => $nieuw_id, 't' => $telefoonnummer ?: null]);
                    $pdo->commit();
                    $succes = 'Persoon "' . $naam . '" is toegevoegd, met MDT-login.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $fout = (int) $e->getCode() === 23000
                        ? 'Deze gebruikersnaam is al in gebruik.'
                        : 'Er ging iets mis bij het aanmaken van het account.';
                }
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO crew (naam, functie, telefoonnummer) VALUES (:n, :f, :t)');
            $stmt->execute(['n' => $naam, 'f' => $functie ?: null, 't' => $telefoonnummer ?: null]);
            $succes = 'Crewlid "' . $naam . '" is toegevoegd.';
        }
    } elseif ($actie === 'zichtbaar_in_mdt_wisselen') {
        $id   = (int) ($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'crew';

        if ($type === 'mdt') {
            $pdo->prepare('UPDATE mdt_gebruikers SET zichtbaar_in_mdt = NOT zichtbaar_in_mdt WHERE gebruiker_id = :id')
                ->execute(['id' => $id]);
        } else {
            $pdo->prepare('UPDATE crew SET zichtbaar_in_mdt = NOT zichtbaar_in_mdt WHERE id = :id')
                ->execute(['id' => $id]);
        }
    } elseif ($actie === 'verwijderen') {
        $id   = (int) ($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? 'crew';

        if ($type === 'mdt') {
            $rij_stmt = $pdo->prepare('SELECT naam, rol, actief, mag_inloggen_mkapp FROM gebruikers WHERE id = :id');
            $rij_stmt->execute(['id' => $id]);
            $rij = $rij_stmt->fetch();

            if ($id === (int) $_SESSION['gebruiker_id']) {
                $fout = 'Je kunt je eigen account niet verwijderen.';
            } elseif ($rij && (int) $rij['mag_inloggen_mkapp'] === 1) {
                // Een "echt" MKAPP-account (centralist) dat ook aan MDT
                // gekoppeld is -- vanuit Crew alleen de MDT-toegang
                // weghalen, niet het hele account (dat beheer je op
                // Beheer > Gebruikers).
                $pdo->prepare('DELETE FROM mdt_gebruikers WHERE gebruiker_id = :id')->execute(['id' => $id]);
                $succes = 'MDT-toegang van "' . $rij['naam'] . '" verwijderd. Het account zelf blijft bestaan -- zie Beheer &rarr; Gebruikers.';
            } elseif ($rij && $rij['rol'] === 'beheerder' && $rij['actief'] && mk_intranet_aantal_actieve_beheerders($pdo) <= 1) {
                $fout = 'Je kunt de laatste actieve beheerder niet verwijderen.';
            } else {
                $pdo->prepare('DELETE FROM gebruikers WHERE id = :id')->execute(['id' => $id]);
                $succes = 'Persoon en MDT-account verwijderd.';
            }
        } else {
            $stmt = $pdo->prepare('DELETE FROM crew WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $succes = 'Crewlid verwijderd.';
        }
    }
}

if (is_beheerder() && isset($_GET['bewerk'])) {
    $bewerk_type = $_GET['type'] ?? 'crew';
    if ($bewerk_type === 'mdt') {
        $stmt = $pdo->prepare(
            'SELECT g.id, g.naam, g.functie, g.gebruikersnaam, m.telefoonnummer
             FROM gebruikers g JOIN mdt_gebruikers m ON m.gebruiker_id = g.id
             WHERE g.id = :id'
        );
        $stmt->execute(['id' => (int) $_GET['bewerk']]);
        $bewerk = $stmt->fetch() ?: null;
        if ($bewerk) {
            $bewerk['type'] = 'mdt';
        }
    } else {
        $stmt = $pdo->prepare('SELECT * FROM crew WHERE id = :id');
        $stmt->execute(['id' => (int) $_GET['bewerk']]);
        $bewerk = $stmt->fetch() ?: null;
        if ($bewerk) {
            $bewerk['type'] = 'crew';
        }
    }
}

$personen = alle_crew_personen($pdo);

$actief = 'crew';
$paginatitel = 'Crew';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Crew</h1>
        <p>Iedereen die aan een melding toegewezen kan worden, gedeeld met het meldkamersysteem -- met of zonder MDT-login. Zonder vinkje "Mag inloggen op MDT" is het een kale contactpersoon (telefoonlijst); mét vinkje krijgt diegene een eigen account waarmee op MDT ingelogd kan worden.<?= is_beheerder() ? '' : ' Alleen beheerders kunnen deze lijst bewerken.' ?></p>
        <?php if (is_beheerder()): ?>
        <p class="section-note" style="margin-top:8px;">Het vinkje "Zichtbaar in MDT" hieronder bepaalt los daarvan of iemand in de crew-/bellijst van de MDT-app zelf staat -- handig om die lijst overzichtelijk te houden zonder iemand te hoeven verwijderen of deactiveren. Fijnere MDT-instellingen (rol, alle-meldingen, mag schrijven) beheer je in het meldkamersysteem zelf, bij Beheer &rarr; MDT-gebruikers.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<?php if (is_beheerder()): ?>
<div class="panel">
    <h3><?= $bewerk ? 'Persoon bewerken' : 'Nieuw persoon' ?></h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="opslaan">
        <input type="hidden" name="id" value="<?= $bewerk['id'] ?? 0 ?>">
        <input type="hidden" name="type" value="<?= e($bewerk['type'] ?? 'crew') ?>">
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

        <?php if (!$bewerk): ?>
            <div class="field full">
                <label style="display:flex; align-items:center; gap:8px; text-transform:none; font-weight:400; font-size:13.5px; color:var(--text);">
                    <input type="checkbox" id="mdt-login-toggle" name="mag_inloggen_mdt" value="1" style="width:auto; height:auto;">
                    Mag inloggen op MDT
                </label>
            </div>
            <div class="mdt-login-velden">
                <div class="field">
                    <label for="gebruikersnaam">Gebruikersnaam</label>
                    <input type="text" id="gebruikersnaam" name="gebruikersnaam" placeholder="bv. jan">
                </div>
                <div class="field">
                    <label for="wachtwoord">Wachtwoord</label>
                    <input type="password" id="wachtwoord" name="wachtwoord" minlength="8" placeholder="minimaal 8 tekens">
                </div>
            </div>
        <?php elseif ($bewerk['type'] === 'mdt'): ?>
            <div class="field">
                <label for="gebruikersnaam_weergave">Gebruikersnaam</label>
                <input type="text" id="gebruikersnaam_weergave" value="<?= e($bewerk['gebruikersnaam'] ?? '') ?>" disabled style="opacity:0.6;">
            </div>
            <div class="field">
                <label for="wachtwoord">Nieuw wachtwoord</label>
                <input type="password" id="wachtwoord" name="wachtwoord" minlength="8" placeholder="laat leeg om niet te wijzigen">
            </div>
            <p class="field full section-note" style="margin:-8px 0 0;">Dit is een MDT-account.</p>
        <?php endif; ?>

        <div class="actions full">
            <button type="submit" class="btn btn-primary"><?= $bewerk ? 'Wijzigingen opslaan' : 'Persoon toevoegen' ?></button>
            <?php if ($bewerk): ?>
                <a href="/crew.php" class="btn">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <h3>Crewlijst <span class="count-badge"><?= count($personen) ?></span></h3>
    <?php if (!$personen): ?>
        <p class="section-note">Nog geen crew/MDT-personen toegevoegd.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>Naam</th><th>Functie</th><th>Telefoonnummer</th><th>Type</th><?php if (is_beheerder()): ?><th>Zichtbaar in MDT</th><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php foreach ($personen as $p): ?>
            <tr>
                <td><?= e($p['naam']) ?></td>
                <td class="muted"><?= e($p['functie'] ?: '—') ?></td>
                <td class="mono muted">
                    <?= $p['telefoonnummer'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $p['telefoonnummer'])) . '">' . e($p['telefoonnummer']) . '</a>' : '—' ?>
                </td>
                <td>
                    <?php if ($p['type'] === 'mdt'): ?>
                        <span class="tag" style="background:rgba(245,165,36,0.14); color:#f5a524;">MDT-login<?= !$p['actief'] ? ' (gedeactiveerd)' : '' ?></span>
                    <?php else: ?>
                        <span class="tag" style="background:var(--panel-2); color:var(--muted);">Crew</span>
                    <?php endif; ?>
                </td>
                <?php if (is_beheerder()): ?>
                <td>
                    <form method="post">
                        <input type="hidden" name="actie" value="zichtbaar_in_mdt_wisselen">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="type" value="<?= $p['type'] ?>">
                        <label style="display:flex; align-items:center; gap:4px; font-size:11.5px; font-weight:400; text-transform:none; color:var(--text); margin:0;" title="Staat deze persoon in de crew-/bellijst van de MDT-app?">
                            <input type="checkbox" <?= $p['zichtbaar_in_mdt'] ? 'checked' : '' ?> onchange="this.form.submit()" style="width:13px; height:13px; margin:0;">
                        </label>
                    </form>
                </td>
                <td class="nowrap">
                    <a href="/crew.php?bewerk=<?= $p['id'] ?>&type=<?= $p['type'] ?>" class="btn btn-small">Bewerken</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('<?php
                        if ($p['type'] === 'mdt' && !empty($p['mag_inloggen_mkapp'])) {
                            echo 'MDT-toegang van \'' . e(addslashes($p['naam'])) . '\' verwijderen? Het account zelf blijft bestaan.';
                        } elseif ($p['type'] === 'mdt') {
                            echo 'Persoon \'' . e(addslashes($p['naam'])) . '\' (incl. het MDT-account) definitief verwijderen? Meldingen die aan deze persoon zijn toegewezen verliezen die koppeling.';
                        } else {
                            echo 'Crewlid \'' . e(addslashes($p['naam'])) . '\' verwijderen? Meldingen die aan deze persoon zijn toegewezen verliezen die koppeling.';
                        }
                    ?>');">
                        <input type="hidden" name="actie" value="verwijderen">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="type" value="<?= $p['type'] ?>">
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
