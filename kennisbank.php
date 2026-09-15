<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';
$bewerk_item = null;
$bewerk_document = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';

    /* --- Categorieen --- */

    if ($actie === 'categorie_aanmaken') {
        $naam = trim($_POST['naam'] ?? '');
        if ($naam === '') {
            $fout = 'Vul een naam voor de categorie in.';
        } else {
            $volgende_stmt = $pdo->query('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM kb_categorieen');
            $volgende = (int) $volgende_stmt->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO kb_categorieen (naam, volgorde) VALUES (:n, :v)');
            $stmt->execute(['n' => $naam, 'v' => $volgende]);
            $succes = 'Categorie "' . $naam . '" is aangemaakt.';
        }
    }

    if ($actie === 'categorie_hernoemen') {
        $id = (int) ($_POST['id'] ?? 0);
        $naam = trim($_POST['naam'] ?? '');
        if ($naam === '') {
            $fout = 'De naam van een categorie mag niet leeg zijn.';
        } else {
            $stmt = $pdo->prepare('UPDATE kb_categorieen SET naam = :n WHERE id = :id');
            $stmt->execute(['n' => $naam, 'id' => $id]);
            $succes = 'Naam bijgewerkt.';
        }
    }

    if ($actie === 'categorie_volgorde') {
        $id = (int) ($_POST['id'] ?? 0);
        $volgorde = (int) ($_POST['volgorde'] ?? 0);
        $stmt = $pdo->prepare('UPDATE kb_categorieen SET volgorde = :v WHERE id = :id');
        $stmt->execute(['v' => $volgorde, 'id' => $id]);
        $succes = 'Volgorde bijgewerkt.';
    }

    if ($actie === 'categorie_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!kb_categorie_is_leeg($pdo, $id)) {
            $fout = 'Deze categorie bevat nog Q&A-items of documenten -- verplaats of verwijder die eerst.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM kb_categorieen WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $succes = 'Categorie verwijderd.';
        }
    }

    /* --- Q&A-items --- */

    if ($actie === 'item_opslaan') {
        $id = (int) ($_POST['id'] ?? 0);
        $categorie_id = (int) ($_POST['categorie_id'] ?? 0);
        $vraag = trim($_POST['vraag'] ?? '');
        $antwoord = trim($_POST['antwoord'] ?? '');

        if ($categorie_id <= 0 || $vraag === '' || $antwoord === '') {
            $fout = 'Kies een categorie en vul zowel een vraag als een antwoord in.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE kb_items SET categorie_id = :c, vraag = :v, antwoord = :a WHERE id = :id');
            $stmt->execute(['c' => $categorie_id, 'v' => $vraag, 'a' => $antwoord, 'id' => $id]);
            $succes = 'Q&A-item bijgewerkt.';
        } else {
            $volgende_stmt = $pdo->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM kb_items WHERE categorie_id = :c');
            $volgende_stmt->execute(['c' => $categorie_id]);
            $volgende = (int) $volgende_stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO kb_items (categorie_id, vraag, antwoord, volgorde, auteur_id) VALUES (:c, :v, :a, :vo, :au)'
            );
            $stmt->execute([
                'c' => $categorie_id,
                'v' => $vraag,
                'a' => $antwoord,
                'vo' => $volgende,
                'au' => $_SESSION['gebruiker_id'],
            ]);
            $succes = 'Q&A-item "' . $vraag . '" is toegevoegd.';
        }
    }

    if ($actie === 'item_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM kb_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Q&A-item verwijderd.';
    }

    if ($actie === 'item_link_aanmaken') {
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $label   = trim($_POST['label'] ?? '');
        $url     = trim($_POST['url'] ?? '');

        $aantal_stmt = $pdo->prepare('SELECT COUNT(*) FROM kb_item_links WHERE kb_item_id = :i');
        $aantal_stmt->execute(['i' => $item_id]);
        $huidig_aantal = (int) $aantal_stmt->fetchColumn();

        if ($item_id <= 0 || $label === '' || $url === '') {
            $fout = 'Vul zowel een knoptekst als een link in.';
        } elseif ($huidig_aantal >= 5) {
            $fout = 'Een Q&A-item kan maximaal 5 links hebben.';
        } elseif (!preg_match('#^https?://#i', $url)) {
            $fout = 'De link moet beginnen met http:// of https://';
        } else {
            $volgorde_stmt = $pdo->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM kb_item_links WHERE kb_item_id = :i');
            $volgorde_stmt->execute(['i' => $item_id]);
            $volgende_volgorde = (int) $volgorde_stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO kb_item_links (kb_item_id, label, url, volgorde) VALUES (:i, :l, :u, :v)'
            );
            $stmt->execute(['i' => $item_id, 'l' => $label, 'u' => $url, 'v' => $volgende_volgorde]);
            $succes = 'Link toegevoegd.';
        }
    }

    if ($actie === 'item_link_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM kb_item_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Link verwijderd.';
    }

    /* --- Documenten --- */

    if ($actie === 'document_opslaan') {
        $id = (int) ($_POST['id'] ?? 0);
        $categorie_id = (int) ($_POST['categorie_id'] ?? 0);
        $titel = trim($_POST['titel'] ?? '');
        $toelichting = trim($_POST['toelichting'] ?? '');

        if ($categorie_id <= 0 || $titel === '') {
            $fout = 'Kies een categorie en vul een titel in.';
        } elseif ($id > 0) {
            $stmt = $pdo->prepare('UPDATE kb_documenten SET categorie_id = :c, titel = :t, toelichting = :o WHERE id = :id');
            $stmt->execute(['c' => $categorie_id, 't' => $titel, 'o' => $toelichting !== '' ? $toelichting : null, 'id' => $id]);
            $succes = 'Document bijgewerkt.';
        } else {
            $volgende_stmt = $pdo->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM kb_documenten WHERE categorie_id = :c');
            $volgende_stmt->execute(['c' => $categorie_id]);
            $volgende = (int) $volgende_stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO kb_documenten (categorie_id, titel, toelichting, volgorde, auteur_id) VALUES (:c, :t, :o, :vo, :au)'
            );
            $stmt->execute([
                'c' => $categorie_id,
                't' => $titel,
                'o' => $toelichting !== '' ? $toelichting : null,
                'vo' => $volgende,
                'au' => $_SESSION['gebruiker_id'],
            ]);
            $succes = 'Document "' . $titel . '" is toegevoegd.';
        }
    }

    if ($actie === 'document_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM kb_documenten WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Document verwijderd.';
    }

    if ($actie === 'document_link_aanmaken') {
        $document_id = (int) ($_POST['document_id'] ?? 0);
        $label       = trim($_POST['label'] ?? '');
        $url         = trim($_POST['url'] ?? '');

        $aantal_stmt = $pdo->prepare('SELECT COUNT(*) FROM kb_document_links WHERE kb_document_id = :d');
        $aantal_stmt->execute(['d' => $document_id]);
        $huidig_aantal = (int) $aantal_stmt->fetchColumn();

        if ($document_id <= 0 || $label === '' || $url === '') {
            $fout = 'Vul zowel een knoptekst als een link in.';
        } elseif ($huidig_aantal >= 5) {
            $fout = 'Een document kan maximaal 5 links hebben.';
        } elseif (!preg_match('#^https?://#i', $url)) {
            $fout = 'De link moet beginnen met http:// of https://';
        } else {
            $volgorde_stmt = $pdo->prepare('SELECT COALESCE(MAX(volgorde), 0) + 1 FROM kb_document_links WHERE kb_document_id = :d');
            $volgorde_stmt->execute(['d' => $document_id]);
            $volgende_volgorde = (int) $volgorde_stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO kb_document_links (kb_document_id, label, url, volgorde) VALUES (:d, :l, :u, :v)'
            );
            $stmt->execute(['d' => $document_id, 'l' => $label, 'u' => $url, 'v' => $volgende_volgorde]);
            $succes = 'Link toegevoegd.';
        }
    }

    if ($actie === 'document_link_verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM kb_document_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = 'Link verwijderd.';
    }
}

if (isset($_GET['bewerk_item'])) {
    $bewerk_item = get_kb_item($pdo, (int) $_GET['bewerk_item']);
}
if (isset($_GET['bewerk_document'])) {
    $bewerk_document = get_kb_document($pdo, (int) $_GET['bewerk_document']);
}

$categorieen = get_kb_categorieen($pdo);
$items = get_kb_items_met_categorie($pdo);
$links_per_item = get_kb_item_links($pdo, array_column($items, 'id'));
$documenten = get_kb_documenten_met_categorie($pdo);
$links_per_document = get_kb_document_links($pdo, array_column($documenten, 'id'));

$actief = 'beheer';
$paginatitel = 'Kennisbank beheren';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Kennisbank beheren</h1>
        <p>Categorieen, Q&amp;A en documenten voor de Event-tab in de navigatie (/qa.php en /documenten.php).</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3>Categorieen</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="categorie_aanmaken">
        <div class="field field-full">
            <label for="cat_naam">Nieuwe categorie</label>
            <input type="text" id="cat_naam" name="naam" required placeholder="bv. Inchecken crew">
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Categorie toevoegen</button>
        </div>
    </form>

    <?php if (!$categorieen): ?>
        <p class="section-note">Nog geen categorieen.</p>
    <?php else: ?>
    <div class="tabel-scroll">
    <table class="admin-table">
        <thead><tr><th>Naam</th><th>Volgorde</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categorieen as $categorie): ?>
            <tr>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="categorie_hernoemen">
                        <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
                        <input type="text" name="naam" value="<?= e($categorie['naam']) ?>" class="input-small">
                        <button type="submit" class="btn btn-small">Opslaan</button>
                    </form>
                </td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="actie" value="categorie_volgorde">
                        <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
                        <input type="number" name="volgorde" value="<?= (int) $categorie['volgorde'] ?>" class="input-small" style="width:70px" onchange="this.form.submit()">
                    </form>
                </td>
                <td class="nowrap">
                    <?php if (kb_categorie_is_leeg($pdo, (int) $categorie['id'])): ?>
                    <form method="post" class="inline-form" onsubmit="return confirm('Categorie \'<?= e(addslashes($categorie['naam'])) ?>\' verwijderen?');">
                        <input type="hidden" name="actie" value="categorie_verwijderen">
                        <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
                        <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                    </form>
                    <?php else: ?>
                    <span class="muted">Bevat items/documenten</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="panel" id="item-form">
    <h3><?= $bewerk_item ? 'Q&A-item bewerken' : 'Nieuw Q&A-item' ?></h3>
    <?php if (!$categorieen): ?>
        <p class="section-note">Maak eerst een categorie aan hierboven.</p>
    <?php else: ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="item_opslaan">
        <input type="hidden" name="id" value="<?= $bewerk_item['id'] ?? 0 ?>">
        <div class="field">
            <label for="item_categorie">Categorie</label>
            <select id="item_categorie" name="categorie_id" required>
                <?php foreach ($categorieen as $categorie): ?>
                    <option value="<?= $categorie['id'] ?>" <?= (int) ($bewerk_item['categorie_id'] ?? 0) === (int) $categorie['id'] ? 'selected' : '' ?>><?= e($categorie['naam']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field field-full">
            <label for="item_vraag">Vraag</label>
            <input type="text" id="item_vraag" name="vraag" required value="<?= e($bewerk_item['vraag'] ?? '') ?>" placeholder="bv. Waar kan ik inchecken?">
        </div>
        <div class="field field-full">
            <label for="item_antwoord">Antwoord</label>
            <textarea id="item_antwoord" name="antwoord" required rows="4"><?= e($bewerk_item['antwoord'] ?? '') ?></textarea>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary"><?= $bewerk_item ? 'Wijzigingen opslaan' : 'Item toevoegen' ?></button>
            <?php if ($bewerk_item): ?>
                <a href="/kennisbank.php#item-form" class="btn">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>

    <?php if (!$items): ?>
        <p class="section-note">Nog geen Q&amp;A-items toegevoegd.</p>
    <?php else: ?>
        <div class="bericht-list">
            <?php foreach ($items as $item): ?>
                <article class="bericht-card">
                    <p class="section-note"><?= e($item['categorie_naam']) ?></p>
                    <h3><?= e($item['vraag']) ?></h3>
                    <p><?= nl2br(e($item['antwoord'])) ?></p>
                    <div class="actions">
                        <a href="/kennisbank.php?bewerk_item=<?= $item['id'] ?>#item-form" class="btn btn-small">Bewerken</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Q&amp;A-item \'<?= e(addslashes($item['vraag'])) ?>\' verwijderen?');">
                            <input type="hidden" name="actie" value="item_verwijderen">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                        </form>
                    </div>

                    <?php $item_links = $links_per_item[$item['id']] ?? []; ?>
                    <div class="link-beheer">
                        <p class="link-beheer-kop">Links (max. 5)</p>
                        <?php if (!$item_links): ?>
                            <p class="section-note">Nog geen links voor dit item.</p>
                        <?php else: ?>
                            <ul class="link-lijst">
                                <?php foreach ($item_links as $link): ?>
                                    <li class="link-item">
                                        <span class="link-item-tekst">
                                            <strong><?= e($link['label']) ?></strong>
                                            <span class="muted"> &rarr; <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="bericht-link"><?= e($link['url']) ?></a></span>
                                        </span>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Link \'<?= e($link['label']) ?>\' verwijderen?');">
                                            <input type="hidden" name="actie" value="item_link_verwijderen">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (count($item_links) < 5): ?>
                            <form method="post" class="link-toevoegen-form">
                                <input type="hidden" name="actie" value="item_link_aanmaken">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
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

<div class="panel" id="document-form">
    <h3><?= $bewerk_document ? 'Document bewerken' : 'Nieuw document' ?></h3>
    <?php if (!$categorieen): ?>
        <p class="section-note">Maak eerst een categorie aan hierboven.</p>
    <?php else: ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="document_opslaan">
        <input type="hidden" name="id" value="<?= $bewerk_document['id'] ?? 0 ?>">
        <div class="field">
            <label for="doc_categorie">Categorie</label>
            <select id="doc_categorie" name="categorie_id" required>
                <?php foreach ($categorieen as $categorie): ?>
                    <option value="<?= $categorie['id'] ?>" <?= (int) ($bewerk_document['categorie_id'] ?? 0) === (int) $categorie['id'] ? 'selected' : '' ?>><?= e($categorie['naam']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field field-full">
            <label for="doc_titel">Titel</label>
            <input type="text" id="doc_titel" name="titel" required value="<?= e($bewerk_document['titel'] ?? '') ?>" placeholder="bv. Draaiboek dag 2">
        </div>
        <div class="field field-full">
            <label for="doc_toelichting">Toelichting (optioneel)</label>
            <textarea id="doc_toelichting" name="toelichting" rows="3"><?= e($bewerk_document['toelichting'] ?? '') ?></textarea>
        </div>
        <div class="actions full">
            <button type="submit" class="btn btn-primary"><?= $bewerk_document ? 'Wijzigingen opslaan' : 'Document toevoegen' ?></button>
            <?php if ($bewerk_document): ?>
                <a href="/kennisbank.php#document-form" class="btn">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>

    <?php if (!$documenten): ?>
        <p class="section-note">Nog geen documenten toegevoegd.</p>
    <?php else: ?>
        <div class="bericht-list">
            <?php foreach ($documenten as $doc): ?>
                <article class="bericht-card">
                    <p class="section-note"><?= e($doc['categorie_naam']) ?></p>
                    <h3><?= e($doc['titel']) ?></h3>
                    <?php if ($doc['toelichting']): ?>
                        <p><?= nl2br(e($doc['toelichting'])) ?></p>
                    <?php endif; ?>
                    <div class="actions">
                        <a href="/kennisbank.php?bewerk_document=<?= $doc['id'] ?>#document-form" class="btn btn-small">Bewerken</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Document \'<?= e(addslashes($doc['titel'])) ?>\' verwijderen?');">
                            <input type="hidden" name="actie" value="document_verwijderen">
                            <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                        </form>
                    </div>

                    <?php $doc_links = $links_per_document[$doc['id']] ?? []; ?>
                    <div class="link-beheer">
                        <p class="link-beheer-kop">Links (max. 5)</p>
                        <?php if (!$doc_links): ?>
                            <p class="section-note">Nog geen links voor dit document.</p>
                        <?php else: ?>
                            <ul class="link-lijst">
                                <?php foreach ($doc_links as $link): ?>
                                    <li class="link-item">
                                        <span class="link-item-tekst">
                                            <strong><?= e($link['label']) ?></strong>
                                            <span class="muted"> &rarr; <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener" class="bericht-link"><?= e($link['url']) ?></a></span>
                                        </span>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Link \'<?= e($link['label']) ?>\' verwijderen?');">
                                            <input type="hidden" name="actie" value="document_link_verwijderen">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (count($doc_links) < 5): ?>
                            <form method="post" class="link-toevoegen-form">
                                <input type="hidden" name="actie" value="document_link_aanmaken">
                                <input type="hidden" name="document_id" value="<?= $doc['id'] ?>">
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
