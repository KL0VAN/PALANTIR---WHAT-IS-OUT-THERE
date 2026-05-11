<?php

$hostRemoto = "172.16.3.132";
$dbnameRemoto = "palantir";
$usernameRemoto = "root";
$passwordRemoto = "";
$portaRemota = 3306;

try {
    $connRemoto = new PDO(
        "mysql:host=$hostRemoto;port=$portaRemota;dbname=$dbnameRemoto;charset=utf8",
        $usernameRemoto,
        $passwordRemoto
    );

    $connRemoto->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "errore" => "Connessione al database remoto fallita",
        "dettaglio" => $e->getMessage()
    ]);
    exit;
}