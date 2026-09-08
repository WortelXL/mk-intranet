<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

// Alleen-lezen: locaties + hun positie beheer je in het meldkamersysteem
// (Beheer > Locaties > "Positie op kaart"), meldingen zelf ook.
$locaties = get_locaties($pdo);
$meldingen = get_actieve_meldingen($pdo);
$mijn_instellingen = huidige_gebruiker_instellingen($pdo);

// Groepeer actieve meldingen per locatienaam (exacte match -- sinds
// V2.0.2.13 is de locatie bij een nieuwe melding een verplichte keuze uit
// dezelfde lijst, dus dit dekt voortaan alle nieuwe meldingen; een oudere
// melding met een niet-matchende vrije-tekst locatie krijgt geen pin).
$meldingen_per_locatie = [];
foreach ($meldingen as $m) {
    $naam = trim((string) ($m['locatie'] ?? ''));
    if ($naam === '') {
        continue;
    }
    $meldingen_per_locatie[$naam][] = $m;
}

// Alleen locaties met een ingestelde positie komen als pin op de kaart.
$locaties_met_positie = array_filter($locaties, fn($l) => $l['plattegrond_x'] !== null && $l['plattegrond_y'] !== null);
$locaties_zonder_positie_aantal = count($locaties) - count($locaties_met_positie);

// Kleur/badge van een pin volgt de hoogst-actieve prioriteit op die
// locatie (zelfde volgorde als overal elders: kritiek > hoog > normaal > laag).
$prioriteit_volgorde = ['kritiek' => 0, 'hoog' => 1, 'normaal' => 2, 'laag' => 3];

$actief = 'plattegrond';
$paginatitel = 'Plattegrond';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Meldingen</p>
        <h1>Plattegrond</h1>
        <p>Actieve meldingen als pins op de plattegrond, op basis van de locatie die aan de melding gekoppeld is (alleen-lezen). Locaties en hun positie beheer je in het meldkamersysteem bij Beheer &gt; Locaties.</p>
    </div>
</div>

<div class="panel">
    <div class="kaart-layout">
        <div class="kaart-frame">
            <img src="/assets/plattegrond.png" alt="Plattegrond">
            <?php foreach ($locaties_met_positie as $loc): ?>
                <?php
                $hier = $meldingen_per_locatie[$loc['naam']] ?? [];
                if (!$hier) {
                    continue;
                }
                usort($hier, fn($a, $b) => ($prioriteit_volgorde[$a['prioriteit']] ?? 9) <=> ($prioriteit_volgorde[$b['prioriteit']] ?? 9));
                $top = $hier[0];
                $kleur = prioriteit_kleur($top['prioriteit']);
                $pulseer = in_array($top['prioriteit'], ['hoog', 'kritiek'], true);
                ?>
                <div class="pin <?= $pulseer ? 'actief' : '' ?>" tabindex="0" style="left:<?= e((string) $loc['plattegrond_x']) ?>%; top:<?= e((string) $loc['plattegrond_y']) ?>%;">
                    <div class="pin-tooltip">
                        <div class="pin-tooltip-locatie"><?= e($loc['naam']) ?></div>
                        <?php foreach (array_slice($hier, 0, 4) as $m): ?>
                            <div class="pin-tooltip-melding">
                                <span class="meld-id"><?= e($m['meld_id']) ?></span>
                                <span class="titel"><?= e($m['titel']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($hier) > 4): ?>
                            <div class="pin-tooltip-meer">+ <?= count($hier) - 4 ?> meer</div>
                        <?php endif; ?>
                    </div>
                    <div class="pin-dot" style="background:<?= e($kleur) ?>;">
                        <?php if (count($hier) > 1): ?>
                            <span><?= count($hier) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="kaart-legenda">
            <div class="legenda-blok">
                <h4>Prioriteit</h4>
                <?php foreach (['kritiek', 'hoog', 'normaal', 'laag'] as $p): ?>
                    <div class="legenda-item"><span class="legenda-dot" style="background:<?= e(prioriteit_kleur($p)) ?>;"></span> <?= e(prioriteit_label($p)) ?></div>
                <?php endforeach; ?>
            </div>
            <div class="legenda-blok">
                <h4>Interactie</h4>
                <div class="legenda-note">Tik of hover op een pin voor de meld-ID's en titels op die locatie. Een pin met een cijfer erop staat voor meerdere actieve meldingen op dezelfde locatie.</div>
            </div>
            <?php if ($locaties_zonder_positie_aantal > 0): ?>
            <div class="legenda-blok">
                <div class="legenda-note"><?= $locaties_zonder_positie_aantal ?> locatie<?= $locaties_zonder_positie_aantal === 1 ? '' : 's' ?> nog zonder positie op de kaart (Beheer &gt; Locaties in het meldkamersysteem).</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$locaties_met_positie): ?>
        <p style="color:var(--muted); margin-top:14px;">Nog geen enkele locatie heeft een positie op de kaart. Stel dit in bij Beheer &gt; Locaties in het meldkamersysteem.</p>
    <?php endif; ?>
</div>

<script>
// Simpele auto-refresh, zelfde persoonlijke instelling als Overview/Plotbord --
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
