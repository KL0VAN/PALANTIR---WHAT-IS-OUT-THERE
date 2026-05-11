<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db_remoto.php";

try {
    $sql = "SELECT COUNT(*) AS totalePaesi FROM Paese";
    $stmt = $connRemoto->query($sql);
    $risultato = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "stato" => "Connessione remota OK",
        "totalePaesi" => $risultato["totalePaesi"]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "errore" => "Errore nel test remoto",
        "dettaglio" => $e->getMessage()
    ]);
}