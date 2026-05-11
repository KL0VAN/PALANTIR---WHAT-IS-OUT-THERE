<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

try {
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

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "errore" => "Errore nel recupero degli interventi umanitari",
        "dettaglio" => $e->getMessage()
    ]);
}
