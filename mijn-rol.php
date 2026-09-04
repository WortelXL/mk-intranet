<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

// Gefilterde weergave (V0.1.8): een losse pagina naast de gewone
// Overview (wie geen classificatie-gekoppelde rol heeft, blijft daar
// gewoon alles zien). Stuurt door naar Overview met het
// hoofdclassificatie-filter van de eigen gekoppelde rol al ingesteld --
// hergebruikt bewust de bestaande, al geteste Overview-pagina in plaats
// van dat allemaal te dupliceren. Zelfde opzet als ehbo.php in het
// meldkamersysteem.
$gefilterde_rol = mijn_gefilterde_rol($pdo);

if ($gefilterde_rol) {
    header('Location: /meldingen.php?hoofd=' . (int) $gefilterde_rol['hoofdclassificatie_id']);
    exit;
}

$actief = 'mijn-rol';
$paginatitel = 'Mijn rol';
include __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="eyebrow">MK Intranet</p>
        <h1>Mijn rol</h1>
        <p>Deze pagina toont een gefilterde weergave op basis van de hoofdclassificatie die aan jouw actieve rol gekoppeld is.</p>
    </div>
</div>

<div class="empty">
    Je hebt momenteel geen (actieve) rol met een gekoppelde hoofdclassificatie. Vraag een beheerder om je zo'n rol toe te wijzen via <a href="/gebruikers.php">Beheer &gt; Gebruikers</a> (kolom "Rollen"), en controleer eventueel de koppeling zelf via <a href="/rollen.php">Beheer &gt; Rollen</a>.
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
