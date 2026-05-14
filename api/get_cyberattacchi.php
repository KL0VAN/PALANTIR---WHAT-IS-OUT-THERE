<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "fallback_data.php";
$conn = connessioneDbLocaleSeDisponibile();

try {
    if (!$conn instanceof PDO) {
        outputJson(fallbackCyberattacchi());
    }

    $sql = "
        SELECT 
            c.idCyberattacco,
            c.idEvento,
            e.luogo,
            e.dataEvento,
            e.livelloGravita,
            c.tipoBersaglio,
            c.livelloImpatto,
            c.metodoAttacco
        FROM cyberattacco c
        INNER JOIN evento e ON c.idEvento = e.idEvento
        ORDER BY c.idCyberattacco ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $cyberattacchi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cyberattacchi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    outputJson(fallbackCyberattacchi());
}
