<?php
header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : 'Syria';

/*
    ReliefWeb richiede il parametro appname.
    Dal 1 novembre 2025 può essere necessario un appname pre-approvato.
    Se l'appname non è accettato, l'endpoint può fallire e viene usato il fallback.
*/
$appname = "palantir-lite-school-project";

echo json_encode(chiamaReliefWeb($query, $appname), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function chiamaReliefWeb($query, $appname) {
    $url = creaReliefWebUrl($query, $appname);
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'url_chiamata' => $url,
        'query' => $query,
        'appname' => $appname,
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'risposta_grezza' => $raw === false ? '' : $raw
    ];
}

function creaReliefWebUrl($query, $appname) {
    return 'https://api.reliefweb.int/v2/reports?'
        . 'appname=' . rawurlencode($appname)
        . '&limit=5'
        . '&preset=latest'
        . '&query[value]=' . rawurlencode($query)
        . '&fields[include][]=title'
        . '&fields[include][]=date.created'
        . '&fields[include][]=source.name'
        . '&fields[include][]=country.name'
        . '&fields[include][]=url';
}
