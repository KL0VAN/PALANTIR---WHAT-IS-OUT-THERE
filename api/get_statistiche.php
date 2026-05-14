<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "fallback_data.php";
$conn = connessioneDbLocaleSeDisponibile();

try {
    if (!$conn instanceof PDO) {
        outputJson(fallbackStatistiche());
    }

    $statistiche = [];

    $sqlEventi = "SELECT COUNT(*) AS totale FROM evento";
    $statistiche["totaleEventi"] = $conn->query($sqlEventi)->fetch(PDO::FETCH_ASSOC)["totale"];

    $sqlCyber = "SELECT COUNT(*) AS totale FROM cyberattacco";
    $statistiche["totaleCyberattacchi"] = $conn->query($sqlCyber)->fetch(PDO::FETCH_ASSOC)["totale"];

    $sqlInterventi = "SELECT COUNT(*) AS totale FROM interventoumanitario";
    $statistiche["totaleInterventi"] = $conn->query($sqlInterventi)->fetch(PDO::FETCH_ASSOC)["totale"];

    $sqlCritici = "SELECT COUNT(*) AS totale FROM evento WHERE livelloGravita = 'critico'";
    $statistiche["eventiCritici"] = $conn->query($sqlCritici)->fetch(PDO::FETCH_ASSOC)["totale"];

    echo json_encode($statistiche, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    outputJson(fallbackStatistiche());
}
