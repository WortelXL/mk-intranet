<?php
/** Verwacht optioneel: $paginatitel (string), $actief (string) */
$paginatitel = $paginatitel ?? 'Intranet';
$actief = $actief ?? '';
$in_meldingen_menu = in_array($actief, ['meldingen', 'statistieken'], true);

// V0.1.8: rollen (het meldkamersysteem-rollensysteem). Heeft de actieve
// rol een gekoppelde hoofdclassificatie, dan beperkt dat de navigatie tot
// alleen de eigen gefilterde weergave + eigen instellingen/uitloggen --
// zie $rol_beperkt hieronder en vereis_rol_beperking() in functions.php
// (die de bijbehorende pagina's ook echt blokkeert, niet alleen de
// navigatie).
$mijn_rollen_navbar = [];
$mijn_actieve_rol = null;
if (is_ingelogd()) {
    $mijn_rollen_navbar = gebruiker_rollen($pdo, (int) $_SESSION['gebruiker_id']);
    if ($mijn_rollen_navbar) {
        $mijn_actieve_rol = actieve_rol($pdo);
    }
}
$rol_beperkt = $mijn_actieve_rol && $mijn_actieve_rol['hoofdclassificatie_id'] !== null;
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($paginatitel) ?> — MK Intranet</title>
<link rel="stylesheet" href="/assets/style.css?v=<?= urlencode(APP_VERSION) ?>">
</head>
<body>
<div class="topbar">
    <div class="topbar-inner">
        <a href="<?= is_ingelogd() && $rol_beperkt ? '/mijn-rol.php' : '/index.php' ?>" class="brand">
            <span class="brand-mark">MK</span>
            <span>
                <span class="brand-name">Intranet</span><br>
                <span class="brand-event"><?= e(event_naam($pdo)) ?></span>
            </span>
        </a>
        <nav class="mainnav">
            <?php if (is_ingelogd()): ?>
                <?php if ($rol_beperkt): ?>
                    <a href="/mijn-rol.php" class="<?= $actief === 'mijn-rol' ? 'active' : '' ?>"><?= e($mijn_actieve_rol['naam']) ?></a>
                <?php else: ?>
                    <a href="/index.php" class="<?= $actief === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                    <div class="nav-dropdown">
                        <details>
                            <summary class="<?= $in_meldingen_menu ? 'active' : '' ?>">Meldingen</summary>
                            <div class="nav-dropdown-menu">
                                <a href="/meldingen.php" class="<?= $actief === 'meldingen' ? 'active' : '' ?>">Overview</a>
                                <a href="/statistieken.php" class="<?= $actief === 'statistieken' ? 'active' : '' ?>">Statistieken</a>
                            </div>
                        </details>
                    </div>
                    <a href="/crew.php" class="<?= $actief === 'crew' ? 'active' : '' ?>">Crew</a>
                    <a href="/archief.php" class="<?= $actief === 'archief' ? 'active' : '' ?>">Archief</a>
                    <?php if (is_beheerder()): ?>
                        <a href="/beheer.php" class="<?= $actief === 'beheer' ? 'active' : '' ?>">Beheer</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="/instellingen.php" class="user-chip" title="Mijn instellingen">
                    <?= e(huidige_gebruiker_naam()) ?>
                    <span class="rol-badge rol-<?= e(huidige_gebruiker_rol()) ?>"><?= e(rol_label(huidige_gebruiker_rol())) ?></span>
                </a>
                <?php if (count($mijn_rollen_navbar) > 1): ?>
                <form method="post" action="/wissel_rol.php" class="rol-wisselaar-form">
                    <input type="hidden" name="terug" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                    <select name="rol_id" onchange="this.form.submit()" class="rol-tekst rol-tekst-klikbaar" title="Actieve rol — klik om te wisselen. Bepaalt je rechten en, bij een gekoppelde classificatie, een eigen gefilterde weergave">
                        <?php foreach ($mijn_rollen_navbar as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $mijn_actieve_rol && (int) $mijn_actieve_rol['id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['naam']) ?> (<?= e(rol_label($r['niveau'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($rol_beperkt): ?>
                    <span class="rol-wisselaar-hint" title="Actieve rol — bepaalt je rechten en, bij een gekoppelde classificatie, een eigen gefilterde weergave">(beperkte weergave)</span>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Inloggen</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<div class="wrap">
