<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    if ($actie === 'bericht_opslaan') {
        $id      = (int) ($_POST['id'] ?? 0);
        $titel   = trim($_POST['titel'] ?? '');
        $inhoud  = trim($_POST['inhoud'] ?? '');
        $url     = trim($_POST['url'] ?? '');

        if ($titel === '' || $inhoud === '') {
            $fout = 'Vul een titel en tekst in.';
        } elseif ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            $fout = 'Vul een geldige URL in (met http:// of https://), of laat het veld leeg.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE berichten SET titel = :t, inhoud = :i, url = :u WHERE id = :id');
            $stmt->execute(['t' => $titel, 'i' => $inhoud, 'u' => $url ?: null, 'id' => $id]);
            $succes = 'Bericht bijgewerkt.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO berichten (titel, inhoud, url, auteur_id) VALUES (:t, :i, :u, :a)');
            $stmt->execute(['t' => $titel, 'i' => $inhoud, 'u' => $url ?: null, 'a' => $_SESSION['gebruiker_id']]);
            $succes = 'Bericht "' . $titel . '" is geplaatst.';
        }
    }

    if ($actie === 'bericht_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM berichten WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Bericht verwijderd.';
    }
}

if (isset($_GET['bewerk'])) {
    $bewerk = get_bericht($pdo, (int) $_GET['bewerk']);
}

$berichten = get_berichten($pdo);

$actief = 'beheer';
$paginatitel = 'Berichten beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Berichten beheren</h1>
        <p>Mededelingen die je hier plaatst, verschijnen voor iedereen op het dashboard onder de lopende meldingen.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3><?= $bewerk ? 'Bericht bewerken' : 'Nieuw bericht' ?></h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="bericht_opslaan">
        <input type="hidden" name="id" value="<?= $bewerk['id'] ?? 0 ?>">
        <div class="field field-full">
            <label for="titel">Titel</label>
            <input type="text" id="titel" name="titel" required value="<?= e($bewerk['titel'] ?? '') ?>" placeholder="bv. Aangepaste openingstijden dag 2">
        </div>
        <div class="field field-full">
            <label for="inhoud">Tekst</label>
            <textarea id="inhoud" name="inhoud" required rows="4" placeholder="Wat wil je delen met de crew?"><?= e($bewerk['inhoud'] ?? '') ?></textarea>
        </div>
        <div class="field field-full">
            <label for="url">URL (optioneel)</label>
            <input type="url" id="url" name="url" value="<?= e($bewerk['url'] ?? '') ?>" placeholder="bv. link naar een draaiboek of externe pagina">
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary"><?= $bewerk ? 'Wijzigingen opslaan' : 'Bericht plaatsen' ?></button>
            <?php if ($bewerk): ?>
                <a href="/berichten.php" class="btn">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Geplaatste berichten <span class="count-badge"><?= count($berichten) ?></span></h3>
    <?php if (!$berichten): ?>
        <p class="section-note">Nog geen berichten geplaatst.</p>
    <?php else: ?>
        <div class="bericht-list">
            <?php foreach ($berichten as $b): ?>
                <article class="bericht-card">
                    <h3><?= e($b['titel']) ?></h3>
                    <p><?= nl2br(e($b['inhoud'])) ?></p>
                    <?php if (!empty($b['url'])): ?>
                        <p><a href="<?= e($b['url']) ?>" target="_blank" rel="noopener" class="bericht-link">&#128279; <?= e($b['url']) ?></a></p>
                    <?php endif; ?>
                    <p class="section-note">
                        <?= e($b['auteur_naam'] ?: 'Onbekend') ?>
                        &middot; <?= (new DateTime($b['aangemaakt_op']))->format('d-m-Y H:i') ?>
                    </p>
                    <div class="actions">
                        <a href="/berichten.php?bewerk=<?= $b['id'] ?>" class="btn btn-small">Bewerken</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Bericht \'<?= e($b['titel']) ?>\' verwijderen?');">
                            <input type="hidden" name="actie" value="bericht_verwijderen">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
