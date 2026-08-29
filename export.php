<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

// Zelfde filters als het archief, zodat de PDF precies aansluit op wat er
// op het scherm stond.
$hoofdclassificatie_id = isset($_GET['hoofd']) && $_GET['hoofd'] !== '' ? (int) $_GET['hoofd'] : null;
$prioriteit = $_GET['prioriteit'] ?? '';
$prioriteit = in_array($prioriteit, ['laag', 'normaal', 'hoog', 'kritiek'], true) ? $prioriteit : null;
$label_id = isset($_GET['label']) && $_GET['label'] !== '' ? (int) $_GET['label'] : null;

$meldingen = get_archief_meldingen($pdo, $hoofdclassificatie_id, $prioriteit, $label_id);
$labels_per_melding = get_labels_per_melding($pdo, array_column($meldingen, 'id'));

require_once __DIR__ . '/includes/minipdf.php';

$pdf = new MiniPdf();
$marge = $pdf->marge();
$volle_breedte = $pdf->paginaBreedte();

$pdf->setFontSize(16);
$pdf->tekstOp($marge, 'Archief - ' . event_naam($pdo), true);
$pdf->nieuweRegel();
$pdf->setFontSize(9);
$pdf->tekstOp($marge, 'Gegenereerd op ' . (new DateTime())->format('d-m-Y H:i') . ' door ' . huidige_gebruiker_naam() . ' - ' . count($meldingen) . ' melding(en)');
$pdf->nieuweRegel();
$pdf->nieuweRegel();

if (!$meldingen) {
    $pdf->tekstOp($marge, 'Geen afgeronde meldingen gevonden voor deze filters.');
    $pdf->nieuweRegel();
}

foreach ($meldingen as $m) {
    $classificatie = trim(($m['hoofd_naam'] ?: '') . ($m['sub_naam'] ? ' - ' . $m['sub_naam'] : ''));
    $labels_tekst = !empty($labels_per_melding[$m['id']]) ? implode(', ', array_column($labels_per_melding[$m['id']], 'naam')) : '';

    $pdf->ruimteNodig(50);

    $pdf->setFontSize(12);
    $pdf->tekstOp($marge, $m['meld_id'] . ' - ' . $m['titel'], true);
    $pdf->nieuweRegel();

    $pdf->setFontSize(9);
    $pdf->tekstOp($marge, ($classificatie ?: 'Geen classificatie') . '  |  ' . prioriteit_label($m['prioriteit']) . '  |  ' . status_label($pdo, $m['status']));
    $pdf->nieuweRegel();
    $pdf->tekstOp($marge, 'Locatie: ' . ($m['locatie'] ?: '-') . '  |  Gemeld door: ' . ($m['gemeld_door'] ?: '-'));
    $pdf->nieuweRegel();
    $pdf->tekstOp($marge, 'Aangemaakt: ' . (new DateTime($m['aangemaakt_op']))->format('d-m-Y H:i'));
    $pdf->nieuweRegel();
    if ($labels_tekst) {
        $pdf->tekstOp($marge, 'Labels: ' . $labels_tekst);
        $pdf->nieuweRegel();
    }

    if (!empty($m['omschrijving'])) {
        $pdf->nieuweRegel();
        $pdf->tekstOp($marge, 'Omschrijving:', true);
        $pdf->nieuweRegel();
        $pdf->paragraaf($marge, $m['omschrijving'], $volle_breedte);
    }

    $pdf->nieuweRegel();
    $pdf->lijn();
    $pdf->nieuweRegel();
    $pdf->nieuweRegel();
}

$pdf->versturen('archief_' . date('Y-m-d_His') . '.pdf');
