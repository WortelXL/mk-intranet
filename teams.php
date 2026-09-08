<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';

// V0.1.18: teams zelf (aanmaken/hernoemen/verwijderen) blijven een taak
// van het meldkamersysteem (Beheer > Teams daar) -- hier kun je alleen
// de bezetting van een bestaand team wijzigen (leden toevoegen/
// verwijderen). Zelfde gedeelde tabellen (teams, team_leden,
// gebruikers) als mkapp, dus wijzigingen zijn direct zichtbaar in
// beide apps en op het Plotbord.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $gebruiker_id = (int) ($_POST['gebruiker_id'] ?? 0);

    if ($actie === 'lid_toevoegen') {
        if ($id && $gebruiker_id) {
            team_lid_toevoegen($pdo, $id, $gebruiker_id);
            $succes = 'Lid toegevoegd.';
        }
    } elseif ($actie === 'lid_verwijderen') {
        if ($id && $gebruiker_id) {
            team_lid_verwijderen($pdo, $id, $gebruiker_id);
            $succes = 'Lid verwijderd.';
        }
    }
}

$teams = alle_teams($pdo);
$mdt_gebruikers = get_mdt_gebruikers($pdo);

$actief = 'beheer';
$paginatitel = 'Teams beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" style="color:var(--muted); text-decoration:none;">&larr; Beheer</a></p>
        <h1>Teams</h1>
        <p>Wie er in welk team zit voor het Plotbord — een team kan 0 of meerdere leden hebben, en iemand mag in meerdere teams tegelijk zitten. Alleen accounts met MDT-toegang (Beheer &rarr; Gebruikers) staan in de keuzelijst. Teams zelf aanmaken, hernoemen of verwijderen blijft een taak van het meldkamersysteem.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3>Teams <span class="count-badge"><?= count($teams) ?></span></h3>
    <?php if (!$teams): ?>
        <p style="color:var(--muted);">Nog geen teams aangemaakt — dat doe je in het meldkamersysteem (Beheer &rarr; Teams).</p>
    <?php endif; ?>
    <?php if ($teams): ?>
    <table class="admin-table">
        <thead>
            <tr><th>Naam</th><th>Leden</th></tr>
        </thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <?php
                $lid_ids = array_column($team['leden'], 'id');
                $beschikbaar = array_filter($mdt_gebruikers, fn($g) => !in_array((int) $g['id'], $lid_ids, true));
            ?>
            <tr>
                <td><?= e($team['naam']) ?></td>
                <td>
                    <?php if (!$team['leden']): ?>
                        <span style="color:var(--muted); font-size:12.5px;">— onbemand —</span>
                    <?php else: ?>
                        <div class="team-leden-lijst">
                            <?php foreach ($team['leden'] as $lid): ?>
                                <span class="team-lid-chip">
                                    <?= e($lid['naam']) ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('<?= e(addslashes($lid['naam'])) ?> uit team &quot;<?= e(addslashes($team['naam'])) ?>&quot; verwijderen?');">
                                        <input type="hidden" name="actie" value="lid_verwijderen">
                                        <input type="hidden" name="id" value="<?= $team['id'] ?>">
                                        <input type="hidden" name="gebruiker_id" value="<?= $lid['id'] ?>">
                                        <button type="submit" class="team-lid-chip-x" title="Verwijderen uit team">&times;</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($beschikbaar): ?>
                        <form method="post" style="display:inline-flex; gap:6px; align-items:center; margin-top:6px;">
                            <input type="hidden" name="actie" value="lid_toevoegen">
                            <input type="hidden" name="id" value="<?= $team['id'] ?>">
                            <select name="gebruiker_id" style="padding:5px 8px; font-size:12.5px;">
                                <?php foreach ($beschikbaar as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= e($g['naam']) ?><?= $g['functie'] ? ' (' . e($g['functie']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-small">+ Toevoegen</button>
                        </form>
                    <?php elseif (!$mdt_gebruikers): ?>
                        <span style="color:var(--muted); font-size:12px;">Nog geen accounts met MDT-toegang (Beheer &rarr; Gebruikers).</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
