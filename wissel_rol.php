<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rol_id = (int) ($_POST['rol_id'] ?? 0);
    $mijn_rollen = gebruiker_rollen($pdo, (int) $_SESSION['gebruiker_id']);
    $gekozen_rol = null;
    foreach ($mijn_rollen as $r) {
        if ((int) $r['id'] === $rol_id) {
            $gekozen_rol = $r;
            break;
        }
    }

    if ($gekozen_rol) {
        $_SESSION['actieve_rol_id'] = $rol_id;

        // De nieuw gekozen rol bepaalt voortaan ook echt de rechten (niet
        // alleen de gefilterde weergave bij een gekoppelde classificatie).
        $_SESSION['gebruiker_rol'] = $gekozen_rol['niveau'];
    }
}

// Alleen terug naar een relatief pad binnen de app -- nooit naar een
// extern domein doorsturen (open-redirect-bescherming).
$terug = $_POST['terug'] ?? '/index.php';
if (!is_string($terug) || $terug === '' || $terug[0] !== '/' || str_starts_with($terug, '//')) {
    $terug = '/index.php';
}

header('Location: ' . $terug);
exit;
