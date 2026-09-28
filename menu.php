<?php
/**
 * Beheer > Menu (V0.1.33): eigen hyperlinks toevoegen aan de
 * navigatiebalk, net als "Beheer > Menu" in mkapp -- maar met een eigen
 * tabel (intranet_menu_items/intranet_menu_item_rollen), zodat een link
 * die hier wordt toegevoegd alleen in MK Intranet verschijnt.
 *
 * De ingebouwde onderdelen van de navigatiebalk (Dashboard, Meldingen,
 * Crew, Event, Beheer, ...) zijn hier niet aan te passen -- dit is puur
 * een plek voor eigen, door de beheerder toegevoegde links.
 */
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$fout = '';
$succes = '';

/** Geldige plekken in de navigatiebalk waar een eigen link onder gehangen kan worden. */
$geldige_parents = ['' => 'Hoofdmenu (los, niet in een uitklapmenu)', 'meldingen' => 'Meldingen', 'event' => 'Event'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? '';
    $geldige_rol_ids = array_column($pdo->query('SELECT id FROM rollen')->fetchAll(), 'id');

    if ($actie === 'volgorde_wijzigen') {
        $id       = (int) ($_POST['id'] ?? 0);
        $volgorde = (int) ($_POST['volgorde'] ?? 0);
        $pdo->prepare('UPDATE intranet_menu_items SET volgorde = :v WHERE id = :id')->execute(['v' => $volgorde, 'id' => $id]);
        $succes = 'Volgorde bijgewerkt.';
    }

    if ($actie === 'zichtbaar_wijzigen') {
        $id        = (int) ($_POST['id'] ?? 0);
        $zichtbaar = !empty($_POST['zichtbaar']) ? 1 : 0;
        $pdo->prepare('UPDATE intranet_menu_items SET zichtbaar = :z WHERE id = :id')->execute(['z' => $zichtbaar, 'id' => $id]);
        $succes = 'Zichtbaarheid bijgewerkt.';
    }

    if ($actie === 'rollen_wijzigen') {
        $id = (int) ($_POST['id'] ?? 0);
        $gekozen_rol_ids = array_values(array_intersect(array_map('intval', $_POST['rollen'] ?? []), $geldige_rol_ids));

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM intranet_menu_item_rollen WHERE menu_item_id = :id')->execute(['id' => $id]);
        if ($gekozen_rol_ids) {
            $insert = $pdo->prepare('INSERT INTO intranet_menu_item_rollen (menu_item_id, rol_id) VALUES (:m, :r)');
            foreach ($gekozen_rol_ids as $rid) {
                $insert->execute(['m' => $id, 'r' => $rid]);
            }
        }
        $pdo->commit();
        $succes = $gekozen_rol_ids
            ? 'Rollen bijgewerkt.'
            : 'Rollen bijgewerkt — zonder gekoppelde rol is deze link voor iedereen zichtbaar.';
    }

    if ($actie === 'eigen_toevoegen') {
        $naam            = trim($_POST['naam'] ?? '');
        $url             = trim($_POST['url'] ?? '');
        $parent_sleutel  = $_POST['parent_sleutel'] ?? '';
        $nieuw_tab       = !empty($_POST['nieuw_tab']) ? 1 : 0;
        $gekozen_rol_ids = array_values(array_intersect(array_map('intval', $_POST['rollen'] ?? []), $geldige_rol_ids));

        if ($naam === '') {
            $fout = 'Vul een naam voor de link in.';
        } elseif ($url === '' || !preg_match('#^(https?://|/)#i', $url)) {
            $fout = 'Vul een geldige url in (beginnend met http://, https:// of /).';
        } elseif (!array_key_exists($parent_sleutel, $geldige_parents)) {
            $fout = 'Ongeldige plek in het menu gekozen.';
        } else {
            $volgende_volgorde = (int) $pdo->query(
                'SELECT COALESCE(MAX(volgorde), 0) + 1 FROM intranet_menu_items WHERE ' . ($parent_sleutel === '' ? 'parent_sleutel IS NULL' : 'parent_sleutel = ' . $pdo->quote($parent_sleutel))
            )->fetchColumn();

            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO intranet_menu_items (parent_sleutel, naam, url, nieuw_tab, volgorde) VALUES (:p, :n, :u, :nt, :v)'
            );
            $stmt->execute([
                'p'  => $parent_sleutel === '' ? null : $parent_sleutel,
                'n'  => $naam,
                'u'  => $url,
                'nt' => $nieuw_tab,
                'v'  => $volgende_volgorde,
            ]);
            $nieuw_id = (int) $pdo->lastInsertId();
            if ($gekozen_rol_ids) {
                $insert = $pdo->prepare('INSERT INTO intranet_menu_item_rollen (menu_item_id, rol_id) VALUES (:m, :r)');
                foreach ($gekozen_rol_ids as $rid) {
                    $insert->execute(['m' => $nieuw_id, 'r' => $rid]);
                }
            }
            $pdo->commit();
            $succes = 'Link "' . $naam . '" is toegevoegd.';
        }
    }

    if ($actie === 'eigen_wijzigen') {
        $id             = (int) ($_POST['id'] ?? 0);
        $naam           = trim($_POST['naam'] ?? '');
        $url            = trim($_POST['url'] ?? '');
        $parent_sleutel = $_POST['parent_sleutel'] ?? '';
        $nieuw_tab      = !empty($_POST['nieuw_tab']) ? 1 : 0;

        if ($naam === '') {
            $fout = 'Vul een naam voor de link in.';
        } elseif ($url === '' || !preg_match('#^(https?://|/)#i', $url)) {
            $fout = 'Vul een geldige url in (beginnend met http://, https:// of /).';
        } elseif (!array_key_exists($parent_sleutel, $geldige_parents)) {
            $fout = 'Ongeldige plek in het menu gekozen.';
        } else {
            $pdo->prepare('UPDATE intranet_menu_items SET naam = :n, url = :u, parent_sleutel = :p, nieuw_tab = :nt WHERE id = :id')
                ->execute([
                    'n'  => $naam,
                    'u'  => $url,
                    'p'  => $parent_sleutel === '' ? null : $parent_sleutel,
                    'nt' => $nieuw_tab,
                    'id' => $id,
                ]);
            $succes = 'Link bijgewerkt.';
        }
    }

    if ($actie === 'verwijderen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM intranet_menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $succes = $stmt->rowCount() > 0 ? 'Link verwijderd.' : 'Link niet gevonden.';
    }
}

$rollen = alle_rollen($pdo);
$beheer_eigen_links = alle_eigen_menu_links($pdo);
$rollen_per_item = [];
foreach ($beheer_eigen_links as $it) {
    $rollen_per_item[$it['id']] = menu_item_rol_ids($pdo, (int) $it['id']);
}

$actief = 'beheer';
$paginatitel = 'Menu beheren';
include __DIR__ . '/includes/header.php';

/** Tekent 1 rij van de tabel (1 eigen link). */
function menu_item_rij(array $item, array $geldige_parents, array $rollen, array $rollen_per_item): void
{
    ?>
    <tr>
        <td>
            <form method="post" class="inline-form">
                <input type="hidden" name="actie" value="eigen_wijzigen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <input type="hidden" name="url" value="<?= e($item['url'] ?? '') ?>">
                <input type="hidden" name="parent_sleutel" value="<?= e($item['parent_sleutel'] ?? '') ?>">
                <input type="hidden" name="nieuw_tab" value="<?= !empty($item['nieuw_tab']) ? '1' : '0' ?>">
                <input type="text" name="naam" value="<?= e($item['naam']) ?>" class="input-small">
                <button type="submit" class="btn btn-small">Opslaan</button>
            </form>
        </td>
        <td class="mono nowrap">
            <form method="post" class="inline-form" style="flex-wrap:wrap;">
                <input type="hidden" name="actie" value="eigen_wijzigen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <input type="hidden" name="naam" value="<?= e($item['naam']) ?>">
                <input type="text" name="url" value="<?= e($item['url'] ?? '') ?>" class="input-small" style="width:160px; font-family:var(--font-mono);">
                <select name="parent_sleutel" class="select-small">
                    <?php foreach ($geldige_parents as $sleutel => $label): ?>
                        <option value="<?= e($sleutel) ?>" <?= ($item['parent_sleutel'] ?? '') === $sleutel ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="toegang-checkbox">
                    <input type="checkbox" name="nieuw_tab" value="1" <?= !empty($item['nieuw_tab']) ? 'checked' : '' ?>>
                    nieuw tabblad
                </label>
                <button type="submit" class="btn btn-small">Opslaan</button>
            </form>
        </td>
        <td>
            <form method="post" class="inline-form">
                <input type="hidden" name="actie" value="zichtbaar_wijzigen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <input type="hidden" name="zichtbaar" value="<?= $item['zichtbaar'] ? '0' : '1' ?>">
                <button type="submit" class="btn btn-small <?= $item['zichtbaar'] ? '' : 'btn-danger' ?>"><?= $item['zichtbaar'] ? 'Zichtbaar' : 'Verborgen' ?></button>
            </form>
        </td>
        <td>
            <form method="post" class="inline-form">
                <input type="hidden" name="actie" value="volgorde_wijzigen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <input type="number" name="volgorde" value="<?= (int) $item['volgorde'] ?>" class="input-small" style="width:56px;">
                <button type="submit" class="btn btn-small">Opslaan</button>
            </form>
        </td>
        <td>
            <?php if (!$rollen): ?>
                <span class="muted" style="font-size:12px;">—</span>
            <?php else: ?>
            <form method="post" class="rollen-grid">
                <input type="hidden" name="actie" value="rollen_wijzigen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <?php foreach ($rollen as $r): ?>
                    <label class="rollen-checkbox">
                        <input type="checkbox" name="rollen[]" value="<?= $r['id'] ?>" <?= in_array((int) $r['id'], $rollen_per_item[$item['id']] ?? [], true) ? 'checked' : '' ?> onchange="this.form.submit()">
                        <?= e($r['naam']) ?>
                    </label>
                <?php endforeach; ?>
            </form>
            <?php endif; ?>
        </td>
        <td>
            <form method="post" onsubmit="return confirm('Link \'<?= e(addslashes($item['naam'])) ?>\' verwijderen?');">
                <input type="hidden" name="actie" value="verwijderen">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn btn-small btn-danger">Verwijderen</button>
            </form>
        </td>
    </tr>
    <?php
}
?>

<div class="page-head">
    <div>
        <p class="eyebrow"><a href="/beheer.php" class="back-link">&larr; Beheer</a></p>
        <h1>Menu beheren</h1>
        <p>Voeg eigen hyperlinks toe aan de navigatiebalk (bv. een link naar het meldkamersysteem), intern of extern. Stel eventueel per link in voor welke rollen 'ie zichtbaar is.</p>
        <p class="section-note">De ingebouwde onderdelen van de navigatiebalk (Dashboard, Meldingen, Crew, Event, Beheer, ...) zijn hier niet aan te passen.</p>
    </div>
</div>

<?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<div class="panel">
    <h3>Eigen links</h3>
    <?php if (!$beheer_eigen_links): ?>
        <p class="section-note">Nog geen eigen links toegevoegd.</p>
    <?php else: ?>
    <div class="tabel-scroll">
    <table class="admin-table">
        <thead><tr><th>Naam</th><th>Url / plek</th><th>Zichtbaar</th><th>Volgorde</th><th>Rollen</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($beheer_eigen_links as $el): ?>
            <?php menu_item_rij($el, $geldige_parents, $rollen, $rollen_per_item); ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <h3>Eigen link toevoegen</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="eigen_toevoegen">
        <div class="field">
            <label for="naam">Naam</label>
            <input type="text" id="naam" name="naam" required placeholder="bv. Meldkamersysteem">
        </div>
        <div class="field">
            <label for="url">Url</label>
            <input type="text" id="url" name="url" required placeholder="bv. https://voorbeeld.nl of /profiel.php">
        </div>
        <div class="field">
            <label for="parent_sleutel">Plek in het menu</label>
            <select id="parent_sleutel" name="parent_sleutel">
                <?php foreach ($geldige_parents as $sleutel => $label): ?>
                    <option value="<?= e($sleutel) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label style="display:flex; align-items:center; gap:6px; font-weight:400; text-transform:none; cursor:pointer;">
                <input type="checkbox" name="nieuw_tab" value="1" style="width:14px; height:14px; margin:0;">
                Open in nieuw tabblad
            </label>
        </div>
        <?php if ($rollen): ?>
        <div class="field field-full">
            <label>Rol(len) — leeg = zichtbaar voor iedereen</label>
            <div class="rollen-grid">
                <?php foreach ($rollen as $r): ?>
                    <label class="rollen-checkbox">
                        <input type="checkbox" name="rollen[]" value="<?= $r['id'] ?>">
                        <?= e($r['naam']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="actions full">
            <button type="submit" class="btn btn-primary">Link toevoegen</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
