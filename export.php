<?php
require_once __DIR__ . '/includes/functions.php';
vereis_login();
$pdo = get_pdo();

// Twee manieren om te bepalen wélke meldingen geëxporteerd worden:
// 1) een handmatige selectie (aangevinkte checkboxes op archief.php, POST);
// 2) anders de filters van het archief zelf (GET), zodat "exporteer alles"
//    precies aansluit op wat er op het scherm stond.
$geselecteerde_ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    $geselecteerde_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['ids']))));
}

if ($geselecteerde_ids) {
    $plekhouders = implode(',', array_fill(0, count($geselecteerde_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT m.*, h.naam AS hoofd_naam, h.kleur AS hoofd_kleur, s.naam AS sub_naam
         FROM meldingen m
         LEFT JOIN hoofdclassificaties h ON h.id = m.hoofdclassificatie_id
         LEFT JOIN subclassificaties s ON s.id = m.subclassificatie_id
         WHERE m.id IN ($plekhouders)
         ORDER BY m.aangemaakt_op DESC"
    );
    $stmt->execute($geselecteerde_ids);
    $meldingen = $stmt->fetchAll();
} else {
    $hoofdclassificatie_id = isset($_REQUEST['hoofd']) && $_REQUEST['hoofd'] !== '' ? (int) $_REQUEST['hoofd'] : null;
    $subclassificatie_id = isset($_REQUEST['sub']) && $_REQUEST['sub'] !== '' ? (int) $_REQUEST['sub'] : null;
    $prioriteit = $_REQUEST['prioriteit'] ?? '';
    $prioriteit = in_array($prioriteit, ['laag', 'normaal', 'hoog', 'kritiek'], true) ? $prioriteit : null;
    $label_id = isset($_REQUEST['label']) && $_REQUEST['label'] !== '' ? (int) $_REQUEST['label'] : null;

    $meldingen = get_archief_meldingen($pdo, $hoofdclassificatie_id, $prioriteit, $label_id, $subclassificatie_id);
}

$labels_per_melding = get_labels_per_melding($pdo, array_column($meldingen, 'id'));
$afgeronde_sleutels = statussen_sleutels(get_afgeronde_statussen($pdo));

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
    $pdf->tekstOp($marge, 'Geen meldingen gevonden voor deze selectie/filters.');
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

    // Statusverloop + doorlooptijd
    $geschiedenis = get_status_geschiedenis($pdo, $m['id']);
    $tijdvakken = bereken_status_tijdvakken($geschiedenis, $afgeronde_sleutels, $m['bijgewerkt_op']);
    if ($tijdvakken) {
        $pdf->nieuweRegel();
        $totale_seconden = array_sum(array_column($tijdvakken, 'duur_seconden'));
        $pdf->tekstOp($marge, 'Statusverloop (totale doorlooptijd: ' . format_duur($totale_seconden) . '):', true);
        $pdf->nieuweRegel();
        foreach ($tijdvakken as $v) {
            $pdf->ruimteNodig(14);
            $regel = status_label($pdo, $v['status']) . ': ' . $v['van']->format('d-m-Y H:i')
                . ' tot ' . ($v['lopend'] ? 'nu' : $v['tot']->format('d-m-Y H:i'))
                . '  (' . format_duur($v['duur_seconden']) . ($v['lopend'] ? ', loopt nog' : '') . ')'
                . ($v['gebruiker'] ? '  - ' . $v['gebruiker'] : '');
            $pdf->tekstOp($marge, $regel);
            $pdf->nieuweRegel();
        }
    }

    // Logboek
    $notities = get_notities_voor_melding($pdo, $m['id']);
    if ($notities) {
        $pdf->nieuweRegel();
        $pdf->tekstOp($marge, 'Logboek:', true);
        $pdf->nieuweRegel();
        foreach ($notities as $n) {
            $pdf->ruimteNodig(20);
            $pdf->tekstOp($marge, (new DateTime($n['aangemaakt_op']))->format('d-m-Y H:i') . ' - ' . ($n['auteur'] ?: 'onbekend'), true);
            $pdf->nieuweRegel();
            $pdf->paragraaf($marge + 12, $n['notitie'], $volle_breedte - 12);
        }
    }

    // Gekoppelde protocollen + subtaken
    $protocollen = get_protocollen_voor_melding($pdo, $m['id']);
    if ($protocollen) {
        $pdf->nieuweRegel();
        $pdf->tekstOp($marge, 'Gekoppelde protocollen:', true);
        $pdf->nieuweRegel();
        foreach ($protocollen as $p) {
            $pdf->ruimteNodig(20);
            $pdf->tekstOp($marge, $p['titel'], true);
            $pdf->nieuweRegel();
            $pdf->paragraaf($marge + 12, $p['inhoud'], $volle_breedte - 12);

            foreach ($p['subtaken'] as $t) {
                $pdf->ruimteNodig(14);
                $vinkje = $t['afgevinkt'] ? '[x]' : '[ ]';
                $regel = $vinkje . ' ' . $t['omschrijving'];
                if ($t['afgevinkt']) {
                    $regel .= ' - afgevinkt door ' . ($t['afgevinkt_door_naam'] ?: 'onbekend')
                        . ' op ' . (new DateTime($t['afgevinkt_op']))->format('d-m-Y H:i');
                }
                $pdf->paragraaf($marge + 12, $regel, $volle_breedte - 12);
            }
        }
    }

    // Losse taken
    $losse_taken = get_losse_taken_voor_melding($pdo, $m['id']);
    if ($losse_taken) {
        $pdf->nieuweRegel();
        $pdf->tekstOp($marge, 'Losse taken:', true);
        $pdf->nieuweRegel();
        foreach ($losse_taken as $t) {
            $pdf->ruimteNodig(14);
            $vinkje = $t['afgevinkt'] ? '[x]' : '[ ]';
            $regel = $vinkje . ' ' . $t['omschrijving'];
            if ($t['afgevinkt']) {
                $regel .= ' - afgevinkt door ' . ($t['afgevinkt_door_naam'] ?: 'onbekend')
                    . ' op ' . (new DateTime($t['afgevinkt_op']))->format('d-m-Y H:i');
            }
            $pdf->paragraaf($marge, $regel, $volle_breedte);
        }
    }

    $pdf->nieuweRegel();
    $pdf->lijn();
    $pdf->nieuweRegel();
    $pdf->nieuweRegel();
}

$pdf->versturen('archief_' . date('Y-m-d_His') . '.pdf');
