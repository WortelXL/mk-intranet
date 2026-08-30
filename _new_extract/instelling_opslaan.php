<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seconden'])) {
    stel_auto_refresh_in($pdo, (int) $_POST['seconden']);
}

// Alleen terug naar een relatief pad binnen deze app -- nooit naar een
// extern adres, ook al zou iemand dat in het formulier proberen te zetten.
$terug = $_POST['terug'] ?? '/index.php';
if (!is_string($terug) || !preg_match('#^/[a-zA-Z0-9/_.?=&-]*$#', $terug)) {
    $terug = '/index.php';
}

header('Location: ' . $terug);
exit;
