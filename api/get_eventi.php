<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

try {
    $sql = "
        SELECT 
            idEvento,
            dataEvento,
            luogo,
            tipologia,
            numeroStimatoVittime,
            descrizione,
            latitudine,
            longitudine,
            fonte,
            livelloGravita
        FROM evento
        ORDER BY idEvento ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($eventi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "errore" => "Errore nel recupero degli eventi",
        "dettaglio" => $e->getMessage()
    ]);
}
