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

        if ($titel === '' || $inhoud === '') {
            $fout = 'Vul een titel en tekst in.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE berichten SET titel = :t, inhoud = :i WHERE id = :id');
            $stmt->execute(['t' => $titel, 'i' => $inhoud, 'id' => $id]);
            $succes = 'Bericht bijgewerkt.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO berichten (titel, inhoud, auteur_id) VALUES (:t, :i, :a)');
            $stmt->execute(['t' => $titel, 'i' => $inhoud, 'a' => $_SESSION['gebruiker_id']]);
            $succes = 'Bericht "' . $titel . '" is geplaatst.';
        }
    }

    if ($actie === 'bericht_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM berichten WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Bericht verwijderd.';
    }

    // Links naar naslag/documenten per bericht -- zelfde opzet als
    // protocol-links in het meldkamersysteem: max 5 per bericht, elk met
    // een eigen knoptekst (label) en URL.
    if ($actie === 'link_aanmaken') {
        $bericht_id = (int) ($_POST['bericht_id'] ?? 0);
        $label      = trim($_POST['label'] ?? '');
        $url        = trim($_POST['url'] ?? '');

        $aantal_stmt = $pdo->prepare('SELECT COUNT(*) FROM bericht_links WHERE bericht_id = :b');
        $aantal_stmt->execute(['b' => $bericht_id]);
        $huidig_aantal = (int) $aantal_stmt->fetchColumn();

        if ($bericht_id <= 0 || $label === '' || $url === '') {
            $fout = 'Vul zowel een knoptekst als een link in.';
        } elseif ($huidig_aantal >= 5) {
            $fout = 'Een bericht kan maximaal 5 links hebben.';
        } elseif (!preg_match('#^https?://#i', $url)) {
            $fout = 'De link moet beginnen met http:// of https://';
        } else {
            $volgorde_stmt = $pdo->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM bericht_links WHERE bericht_id = :b');
            $volgorde_stmt->execute(['b' => $bericht_id]);
            $volgende_volgorde = (int) $volgorde_stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO bericht_links (bericht_id, label, url, volgorde) VALUES (:b, :l, :u, :v)'
            );
            $stmt->execute(['b' => $bericht_id, 'l' => $label, 'u' => $url, 'v' => $volgende_volgorde]);
            $succes = 'Link toegevoegd.';
        }
    }

    if ($actie === 'link_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM bericht_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Link verwijderd.';
    }
}

if (isset($_GET['bewerk'])) {
    $bewerk = get_bericht($pdo, (int) $_GET['bewerk']);
}

$berichten = get_berichten($pdo);
$links_per_bericht = get_links_per_bericht($pdo, array_column($berichten, 'id'));

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

                    <?php $links = $links_per_bericht[$b['id']] ?? []; ?>
                    <div class="link-beheer">
                        <p class="link-beheer-kop">Links naar naslag/documenten (max. 5)</p>
                        <?php if (!$links): ?>
                            <p class="section-note">Nog geen links voor dit bericht.</p>
                        <?php else: ?>
                            <ul class="link-lijst">
                                <?php foreach ($links as $link): ?>
                                    <li class="link-item">
                                        <span class="link-item-tekst">
                                            <strong><?= e($link['label']) ?></strong>
                                            <span class="muted"> &rarr; <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="bericht-link"><?= e($link['url']) ?></a></span>
                                        </span>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Link \'<?= e($link['label']) ?>\' verwijderen?');">
                                            <input type="hidden" name="actie" value="link_verwijderen">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (count($links) < 5): ?>
                            <form method="post" class="link-toevoegen-form">
                                <input type="hidden" name="actie" value="link_aanmaken">
                                <input type="hidden" name="bericht_id" value="<?= $b['id'] ?>">
                                <input type="text" name="label" placeholder="Knoptekst, bv. 'Draaiboek'" class="input-small link-input-label" required>
                                <input type="text" name="url" placeholder="https://..." class="input-small link-input-url" required>
                                <button type="submit" class="btn btn-small">Link toevoegen</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
