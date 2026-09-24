<?php
/**
 * Zet de eenheidsstatus van iemand anders vanaf het Plotbord (bv. als
 * iemand vergeten is zijn eigen status bij te werken). Alleen beheerder-
 * en medewerker-niveau mag dit (mag_status_wijzigen()); de gekozen
 * status moet ook echt bij de mdt-rol van díe persoon horen
 * (gecontroleerd in zet_eenheidsstatus_vanaf_plotbord()). Geen los
 * scherm, stuurt altijd terug naar het Plotbord.
 */
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && mag_status_wijzigen()) {
    $gebruiker_id = (int) ($_POST['gebruiker_id'] ?? 0);
    $eenheidsstatus_id = (int) ($_POST['eenheidsstatus_id'] ?? 0);
    if ($gebruiker_id > 0 && $eenheidsstatus_id > 0) {
        zet_eenheidsstatus_vanaf_plotbord($pdo, $gebruiker_id, $eenheidsstatus_id);
    }
}

header('Location: /plotbord.php');
exit;
