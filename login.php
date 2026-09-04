<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = get_pdo();

if (is_ingelogd()) {
    header('Location: /index.php');
    exit;
}

$fout = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
    $wachtwoord     = $_POST['wachtwoord'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM gebruikers WHERE gebruikersnaam = :u');
    $stmt->execute(['u' => $gebruikersnaam]);
    $gebruiker = $stmt->fetch();

    if ($gebruiker && !$gebruiker['actief']) {
        $fout = 'Dit account is gedeactiveerd. Neem contact op met een beheerder.';
    } elseif ($gebruiker && !gebruiker_mag_inloggen($gebruiker, 'mag_inloggen_mkintranet')) {
        $fout = 'Dit account heeft geen toegang tot MK Intranet. Neem contact op met een beheerder.';
    } elseif ($gebruiker && password_verify($wachtwoord, $gebruiker['wachtwoord_hash'])) {
        session_regenerate_id(true);
        $_SESSION['gebruiker_id']   = $gebruiker['id'];
        $_SESSION['gebruiker_naam'] = $gebruiker['naam'];
        $_SESSION['gebruiker_rol']  = $gebruiker['rol'];

        // V0.1.8: heeft deze gebruiker (nieuwe) rollen toegewezen gekregen,
        // dan bepaalt de actieve rol voortaan de rechten -- net als in het
        // meldkamersysteem. Zonder toegewezen rollen verandert er niets
        // t.o.v. de klassieke rol hierboven.
        $rol = actieve_rol($pdo);
        if ($rol) {
            $_SESSION['gebruiker_rol'] = $rol['niveau'];
        }

        header('Location: /index.php');
        exit;
    } else {
        $fout = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
}

$paginatitel = 'Inloggen';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Inloggen</h1>
        <p>Log in met je account van het meldkamersysteem.</p>
    </div>
</div>

<?php if ($fout): ?>
    <div class="alert alert-error"><?= e($fout) ?></div>
<?php endif; ?>

<div class="panel panel-narrow">
    <form method="post">
        <div class="field">
            <label for="gebruikersnaam">Gebruikersnaam</label>
            <input type="text" id="gebruikersnaam" name="gebruikersnaam" required autofocus>
        </div>
        <div class="field">
            <label for="wachtwoord">Wachtwoord</label>
            <input type="password" id="wachtwoord" name="wachtwoord" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Inloggen</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
