<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "fallback_data.php";
$conn = connessioneDbLocaleSeDisponibile();

try {
    if (!$conn instanceof PDO) {
        outputJson(fallbackInterventi());
    }

    $sql = "
        SELECT 
            i.idIntervento,
            i.organizzazione,
            i.tipoIntervento,
            i.dataIntervento,
            i.area,
            e.idEvento,
            e.luogo AS luogoEvento,
            e.tipologia AS tipologiaEvento
        FROM interventoumanitario i
        LEFT JOIN interventoevento ie ON i.idIntervento = ie.idIntervento
        LEFT JOIN evento e ON ie.idEvento = e.idEvento
        ORDER BY i.idIntervento ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $interventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($interventi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    outputJson(fallbackInterventi());
}
