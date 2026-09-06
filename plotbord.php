<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$teams = plotbord_teams($pdo);
$individueel = plotbord_individueel($pdo);
$mijn_instellingen = huidige_gebruiker_instellingen($pdo);

$actief = 'plotbord';
$paginatitel = 'Plotbord';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Meldingen</p>
        <h1>Plotbord</h1>
        <p>Alle teams en losse MDT-gebruikers in 1 oogopslag (alleen-lezen), met hun actuele eenheidsstatus en — indien van toepassing — de melding waar ze nu aan werken. Eenheidsstatussen en teams zelf beheer je in het meldkamersysteem.</p>
    </div>
</div>

<div class="panel">
    <h3>Teams</h3>
    <?php if (!$teams): ?>
        <p style="color:var(--muted);">Nog geen teams aangemaakt.</p>
    <?php else: ?>
    <div class="plotbord-grid">
        <?php foreach ($teams as $team): ?>
            <div class="plotbord-card <?= $team['status_afkorting'] ? '' : 'plotbord-card-leeg' ?>">
                <div class="plotbord-naam"><?= e($team['naam']) ?></div>
                <div class="plotbord-persoon"><?= $team['gebruiker_naam'] ? e($team['gebruiker_naam']) : '— onbemand —' ?></div>
                <?php if ($team['status_afkorting']): ?>
                    <div class="plotbord-status"><span class="afk"><?= e($team['status_afkorting']) ?></span><span class="naam"><?= e($team['status_naam']) ?></span></div>
                <?php else: ?>
                    <div class="plotbord-status plotbord-status-onbekend">geen status</div>
                <?php endif; ?>
                <?php if ($team['actieve_melding']): ?>
                    <div class="plotbord-melding"><?= e($team['actieve_melding']['meld_id']) ?> · <?= e($team['actieve_melding']['titel']) ?></div>
                <?php else: ?>
                    <div class="plotbord-melding plotbord-melding-leeg">geen actieve melding</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="panel">
    <h3>Losse MDT-gebruikers</h3>
    <p style="color:var(--muted); font-size:12.5px; margin-top:-4px;">MDT-gebruikers die niet als vaste bezetting aan een team gekoppeld zijn — die staan hierboven al bij hun team. Klik op een naam voor de actieve melding.</p>
    <?php if (!$individueel): ?>
        <p style="color:var(--muted);">Geen losse MDT-gebruikers.</p>
    <?php else: ?>
    <div class="plotbord-lijst">
        <?php foreach ($individueel as $i => $gebruiker): ?>
            <div class="plotbord-rij <?= $gebruiker['status_afkorting'] ? '' : 'plotbord-card-leeg' ?>">
                <label for="plotbord-toggle-<?= (int) $gebruiker['id'] ?>" class="plotbord-rij-kop">
                    <span class="plotbord-rij-links">
                        <span class="plotbord-naam"><?= e($gebruiker['naam']) ?></span>
                        <?php if ($gebruiker['status_afkorting']): ?>
                            <span class="plotbord-status"><span class="afk"><?= e($gebruiker['status_afkorting']) ?></span><span class="naam"><?= e($gebruiker['status_naam']) ?></span></span>
                        <?php else: ?>
                            <span class="plotbord-status plotbord-status-onbekend">geen status</span>
                        <?php endif; ?>
                    </span>
                    <span class="log-toggle-switch"></span>
                </label>
                <input type="checkbox" id="plotbord-toggle-<?= (int) $gebruiker['id'] ?>" class="log-toggle-checkbox">
                <div class="row-log">
                    <?php if ($gebruiker['actieve_melding']): ?>
                        <div class="plotbord-melding"><?= e($gebruiker['actieve_melding']['meld_id']) ?> · <?= e($gebruiker['actieve_melding']['titel']) ?></div>
                    <?php else: ?>
                        <div class="plotbord-melding plotbord-melding-leeg">geen actieve melding</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Simpele auto-refresh, zelfde persoonlijke instelling als Overview --
// geen geluid hier, dit is een passief overzichtsscherm zonder formulieren.
(function () {
    const ververs_seconden = <?= (int) $mijn_instellingen['auto_refresh_seconden'] ?>;
    if (ververs_seconden > 0) {
        setInterval(function () {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, ververs_seconden * 1000);
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
