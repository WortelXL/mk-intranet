<?php
/** Verwacht optioneel: $paginatitel (string), $actief (string) */
$paginatitel = $paginatitel ?? 'Intranet';
$actief = $actief ?? '';
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
                <a href="/crew.php" class="<?= $actief === 'crew' ? 'active' : '' ?>">Crew</a>
                <?php if (is_beheerder()): ?>
                    <a href="/berichten.php" class="<?= $actief === 'berichten' ? 'active' : '' ?>">Berichten beheren</a>
                <?php endif; ?>
                <span class="user-chip">
                    <?= e(huidige_gebruiker_naam()) ?>
                    <span class="rol-badge rol-<?= e(huidige_gebruiker_rol()) ?>"><?= e(rol_label(huidige_gebruiker_rol())) ?></span>
                </span>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Inloggen</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<div class="wrap">
