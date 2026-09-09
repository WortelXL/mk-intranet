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
    <a href="/teams.php" class="beheer-card">
        <h3>Teams beheren</h3>
        <p>Leden toevoegen aan of verwijderen uit een team voor het Plotbord. Teams zelf aanmaken of verwijderen blijft een taak van het meldkamersysteem.</p>
    </a>
    <a href="/gepland.php" class="beheer-card">
        <h3>Geplande meldingen</h3>
        <p>Meldingen inplannen voor een toekomstig tijdstip, evt. met herhaling. Zelfde gedeelde lijst als het meldkamersysteem — het daadwerkelijk laten verschijnen gebeurt via mkapp.</p>
    </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
