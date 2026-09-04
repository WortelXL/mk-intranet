<?php
require_once __DIR__ . '/includes/functions.php';
vereis_beheerder();
$pdo = get_pdo();

$actief = 'beheer';
$paginatitel = 'Beheer';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Beheer</h1>
        <p>Alleen zichtbaar voor beheerders.</p>
    </div>
</div>

<div class="beheer-grid">
    <a href="/berichten.php" class="beheer-card">
        <h3>Berichten beheren</h3>
        <p>Mededelingen aanmaken, bewerken en verwijderen. Verschijnen voor iedereen op het dashboard.</p>
    </a>
    <a href="/gebruikers.php" class="beheer-card">
        <h3>Gebruikers beheren</h3>
        <p>Accounts aanmaken, rol en wachtwoord wijzigen, activeren of deactiveren. Zelfde gedeelde inlogtabel als het meldkamersysteem.</p>
    </a>
    <a href="/rollen.php" class="beheer-card">
        <h3>Rollen beheren</h3>
        <p>Benoemde rollen aanmaken, niveau instellen en optioneel koppelen aan een hoofdclassificatie voor een gefilterde weergave. Zelfde rollensysteem als het meldkamersysteem.</p>
    </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
