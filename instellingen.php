<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

$fout = '';
$succes = '';
$ww_fout = '';
$ww_succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = $_POST['actie'] ?? 'instellingen';

    if ($actie === 'instellingen') {
        $auto_refresh = (int) ($_POST['auto_refresh_seconden'] ?? 20);
        $geluid       = isset($_POST['geluid_nieuwe_melding']) ? 1 : 0;

        // Zelfde toegestane intervallen als het meldkamersysteem.
        $toegestane_intervallen = [0, 10, 15, 20, 30, 60];
        if (!in_array($auto_refresh, $toegestane_intervallen, true)) {
            $fout = 'Ongeldige verversingstijd gekozen.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE gebruikers SET auto_refresh_seconden = :a, geluid_nieuwe_melding = :g WHERE id = :id'
            );
            $stmt->execute([
                'a'  => $auto_refresh,
                'g'  => $geluid,
                'id' => $_SESSION['gebruiker_id'],
            ]);
            $succes = 'Instellingen opgeslagen.';
        }
    }

    if ($actie === 'wachtwoord_wijzigen') {
        $huidig_wachtwoord = $_POST['huidig_wachtwoord'] ?? '';
        $nieuw_wachtwoord  = $_POST['nieuw_wachtwoord'] ?? '';
        $nieuw_wachtwoord2 = $_POST['nieuw_wachtwoord2'] ?? '';

        $stmt = $pdo->prepare('SELECT wachtwoord_hash FROM gebruikers WHERE id = :id');
        $stmt->execute(['id' => $_SESSION['gebruiker_id']]);
        $huidige_hash = $stmt->fetchColumn();

        if (!$huidige_hash || !password_verify($huidig_wachtwoord, $huidige_hash)) {
            $ww_fout = 'Je huidige wachtwoord klopt niet.';
        } elseif (strlen($nieuw_wachtwoord) < 8) {
            $ww_fout = 'Gebruik een nieuw wachtwoord van minimaal 8 tekens.';
        } elseif ($nieuw_wachtwoord !== $nieuw_wachtwoord2) {
            $ww_fout = 'De nieuwe wachtwoorden komen niet overeen.';
        } else {
            $stmt = $pdo->prepare('UPDATE gebruikers SET wachtwoord_hash = :h WHERE id = :id');
            $stmt->execute([
                'h'  => password_hash($nieuw_wachtwoord, PASSWORD_DEFAULT),
                'id' => $_SESSION['gebruiker_id'],
            ]);
            $ww_succes = 'Wachtwoord gewijzigd.';
        }
    }
}

// Ná een eventuele wijziging opnieuw ophalen, zodat het formulier de
// zojuist opgeslagen waarden toont in plaats van de oude uit de statische
// cache van huidige_gebruiker_instellingen().
$stmt = $pdo->prepare('SELECT auto_refresh_seconden, geluid_nieuwe_melding FROM gebruikers WHERE id = :id');
$stmt->execute(['id' => $_SESSION['gebruiker_id']]);
$instellingen = $stmt->fetch() ?: ['auto_refresh_seconden' => 20, 'geluid_nieuwe_melding' => 1];

$actief = '';
$paginatitel = 'Mijn instellingen';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">Persoonlijk</p>
        <h1>Mijn instellingen</h1>
        <p>Deze instellingen gelden alleen voor jouw account, <?= e(huidige_gebruiker_naam()) ?>, en zijn hetzelfde in het meldkamersysteem.</p>
    </div>
</div>

<div class="panel">
    <h3>Meldingen &amp; geluid</h3>
    <?php if ($fout): ?><div class="alert alert-error"><?= e($fout) ?></div><?php endif; ?>
    <?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="instellingen">
        <div class="field field-full">
            <label for="auto_refresh_seconden">Automatisch verversen elke</label>
            <select id="auto_refresh_seconden" name="auto_refresh_seconden">
                <?php foreach ([0 => 'Uit', 10 => '10 seconden', 15 => '15 seconden', 20 => '20 seconden', 30 => '30 seconden', 60 => '60 seconden'] as $waarde => $label): ?>
                    <option value="<?= $waarde ?>" <?= (int) $instellingen['auto_refresh_seconden'] === $waarde ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="section-note">Geldt voor de Meldingen-pagina. Pauzeert vanzelf zolang dit tabblad niet actief in beeld is.</p>
        </div>

        <div class="field field-full">
            <label style="display:flex; align-items:center; gap:8px; text-transform:none; font-size:14px; color:var(--text); font-weight:500;">
                <input type="checkbox" name="geluid_nieuwe_melding" value="1" <?= $instellingen['geluid_nieuwe_melding'] ? 'checked' : '' ?> style="width:16px; height:16px;">
                Speel een geluid af bij een nieuwe (of nieuw-attentie) melding
            </label>
            <p class="section-note">Werkt alleen zolang de Meldingen-pagina open staat in je browser. Sommige browsers blokkeren geluid totdat je ergens op de pagina hebt geklikt -- dat is een browserbeveiliging, geen fout.</p>
        </div>

        <div class="actions full">
            <button type="submit" class="btn btn-primary">Opslaan</button>
        </div>
    </form>
</div>

<div class="panel">
    <h3>Wachtwoord wijzigen</h3>
    <?php if ($ww_fout): ?><div class="alert alert-error"><?= e($ww_fout) ?></div><?php endif; ?>
    <?php if ($ww_succes): ?><div class="alert alert-success"><?= e($ww_succes) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="actie" value="wachtwoord_wijzigen">
        <div class="field">
            <label for="huidig_wachtwoord">Huidig wachtwoord</label>
            <input type="password" id="huidig_wachtwoord" name="huidig_wachtwoord" required autocomplete="current-password">
        </div>
        <div class="field">
            <label for="nieuw_wachtwoord">Nieuw wachtwoord</label>
            <input type="password" id="nieuw_wachtwoord" name="nieuw_wachtwoord" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field">
            <label for="nieuw_wachtwoord2">Herhaal nieuw wachtwoord</label>
            <input type="password" id="nieuw_wachtwoord2" name="nieuw_wachtwoord2" required minlength="8" autocomplete="new-password">
        </div>
        <p class="section-note field-full">Minimaal 8 tekens. Werkt hier of in het meldkamersysteem -- het is hetzelfde account. Wachtwoord kwijt? Vraag een beheerder om het via Beheer &rarr; Gebruikers opnieuw in te stellen.</p>

        <div class="actions full">
            <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
