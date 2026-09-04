<?php
/** Verwacht optioneel: $paginatitel (string), $actief (string) */
$paginatitel = $paginatitel ?? 'Intranet';
$actief = $actief ?? '';
$in_meldingen_menu = in_array($actief, ['meldingen', 'statistieken'], true);
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
        <a href="/index.php" class="brand">
            <span class="brand-mark">MK</span>
            <span>
                <span class="brand-name">Intranet</span><br>
                <span class="brand-event"><?= e(event_naam($pdo)) ?></span>
            </span>
        </a>
        <nav class="mainnav">
            <?php if (is_ingelogd()): ?>
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
                <a href="/instellingen.php" class="user-chip" title="Mijn instellingen">
                    <?= e(huidige_gebruiker_naam()) ?>
                    <span class="rol-badge rol-<?= e(huidige_gebruiker_rol()) ?>"><?= e(rol_label(huidige_gebruiker_rol())) ?></span>
                </a>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Inloggen</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<div class="wrap">
