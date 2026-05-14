<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "fallback_data.php";
$conn = connessioneDbLocaleSeDisponibile();

try {
    if (!$conn instanceof PDO) {
        outputJson(fallbackEventi());
    }

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

} catch (Throwable $e) {
    outputJson(fallbackEventi());
}
