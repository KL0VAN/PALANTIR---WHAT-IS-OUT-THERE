<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";          // DB locale tuo
require_once "db_remoto.php";   // DB remoto del compagno

try {
    /*
        1. Prendo i coinvolgimenti dal DB locale.
        Qui ci sono:
        - idEvento
        - idPaese
        - ruoloNelEvento
    */

    $sqlCoinvolgimenti = "
        SELECT 
            idEvento,
            idPaese,
            ruoloNelEvento
        FROM coinvolgimento
        ORDER BY idEvento ASC
    ";

    $stmt = $conn->prepare($sqlCoinvolgimenti);
    $stmt->execute();

    $coinvolgimenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($coinvolgimenti) === 0) {
        echo json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /*
        2. Estraggo tutti gli idPaese presenti nella tabella Coinvolgimento.
    */

    $idPaesi = [];

    foreach ($coinvolgimenti as $coinvolgimento) {
        $idPaesi[] = (int)$coinvolgimento["idPaese"];
    }

    $idPaesi = array_values(array_unique($idPaesi));

    /*
        3. Recupero dal DB remoto i dati dei Paesi.
    */

    $placeholders = implode(",", array_fill(0, count($idPaesi), "?"));

    $sqlPaesi = "
        SELECT 
            idPaese,
            nome,
            areaGeografica,
            alleanza,
            ruolo
        FROM Paese
        WHERE idPaese IN ($placeholders)
    ";

    $stmtPaesi = $connRemoto->prepare($sqlPaesi);
    $stmtPaesi->execute($idPaesi);

    $paesi = $stmtPaesi->fetchAll(PDO::FETCH_ASSOC);

    /*
        4. Creo una mappa:
        idPaese → dati completi del Paese
    */

    $mappaPaesi = [];

    foreach ($paesi as $paese) {
        $mappaPaesi[(int)$paese["idPaese"]] = $paese;
    }

    /*
        5. Unisco i dati locali con quelli remoti.
    */

    foreach ($coinvolgimenti as &$coinvolgimento) {
        $idPaese = (int)$coinvolgimento["idPaese"];

        if (isset($mappaPaesi[$idPaese])) {
            $coinvolgimento["nomePaese"] = $mappaPaesi[$idPaese]["nome"];
            $coinvolgimento["areaGeografica"] = $mappaPaesi[$idPaese]["areaGeografica"];
            $coinvolgimento["alleanza"] = $mappaPaesi[$idPaese]["alleanza"];
            $coinvolgimento["ruoloConflitto"] = $mappaPaesi[$idPaese]["ruolo"];
        } else {
            $coinvolgimento["nomePaese"] = "Paese non trovato";
            $coinvolgimento["areaGeografica"] = null;
            $coinvolgimento["alleanza"] = null;
            $coinvolgimento["ruoloConflitto"] = null;
        }
    }

    echo json_encode($coinvolgimenti, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "errore" => "Errore nel recupero dei coinvolgimenti",
        "dettaglio" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}