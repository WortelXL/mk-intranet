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
                <a href="/meldingen.php" class="<?= $actief === 'meldingen' ? 'active' : '' ?>">Meldingen</a>
                <a href="/crew.php" class="<?= $actief === 'crew' ? 'active' : '' ?>">Crew</a>
                <a href="/archief.php" class="<?= $actief === 'archief' ? 'active' : '' ?>">Archief</a>
                <?php if (is_beheerder()): ?>
                    <a href="/beheer.php" class="<?= $actief === 'beheer' ? 'active' : '' ?>">Beheer</a>
                <?php endif; ?>
                <div class="user-chip-wrap">
                    <span class="user-chip">
                        <?= e(huidige_gebruiker_naam()) ?>
                        <span class="rol-badge rol-<?= e(huidige_gebruiker_rol()) ?>"><?= e(rol_label(huidige_gebruiker_rol())) ?></span>
                    </span>
                    <form method="post" action="/instelling_opslaan.php" class="auto-refresh-form">
                        <input type="hidden" name="terug" value="<?= e($_SERVER['REQUEST_URI'] ?? '/index.php') ?>">
                        <label for="auto-refresh-select">Auto-verversen</label>
                        <?php
                            $huidige_auto_refresh = huidige_gebruiker_auto_refresh($pdo);
                            $auto_refresh_opties = [0 => 'Uit', 15 => '15s', 30 => '30s', 60 => '60s', 120 => '2 min'];
                            // Kan al een andere waarde hebben staan (bv. ingesteld vanuit het
                            // meldkamersysteem zelf) -- dan die erbij zetten i.p.v. verliezen.
                            if (!array_key_exists($huidige_auto_refresh, $auto_refresh_opties)) {
                                $auto_refresh_opties[$huidige_auto_refresh] = $huidige_auto_refresh . 's';
                                ksort($auto_refresh_opties);
                            }
                        ?>
                        <select id="auto-refresh-select" name="seconden" onchange="this.form.submit()">
                            <?php foreach ($auto_refresh_opties as $sec => $label): ?>
                                <option value="<?= $sec ?>" <?= $huidige_auto_refresh === $sec ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Inloggen</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<div class="wrap">
