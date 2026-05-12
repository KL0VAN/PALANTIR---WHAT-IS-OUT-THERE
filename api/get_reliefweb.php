<?php
header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : 'Syria';

/*
    ReliefWeb richiede il parametro appname.
    Dal 1 novembre 2025 può essere necessario un appname pre-approvato.
    Se l'appname non è accettato, l'endpoint può fallire e viene usato il fallback.
*/
$appname = "palantir-lite-school-project";

$debug = chiamaReliefWeb($query, $appname);
$payload = json_decode($debug['risposta_grezza'], true);

if ($debug['http_code'] === 200 && isset($payload['data']) && is_array($payload['data'])) {
    echo json_encode([
        'modalita' => 'live',
        'fonte' => 'ReliefWeb API',
        'query' => $query,
        'reports' => array_map('normalizzaReportReliefWeb', $payload['data'])
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'modalita' => 'fallback',
    'fonte' => 'Fallback offline locale',
    'query' => $query,
    'motivoFallback' => creaMotivoFallback($debug, $payload),
    'reports' => fallbackReportsFor($query)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

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

function normalizzaReportReliefWeb($item) {
    $fields = isset($item['fields']) && is_array($item['fields']) ? $item['fields'] : [];

    return [
        'titolo' => valoreCampo($fields, 'title', 'Titolo non disponibile'),
        'data' => isset($fields['date']['created']) ? $fields['date']['created'] : '',
        'fonte' => valoreCampoNome($fields, 'source', 'Fonte non disponibile'),
        'paese' => valoreCampoNome($fields, 'country', 'Paese non disponibile'),
        'url' => valoreCampo($fields, 'url', '')
    ];
}

function valoreCampo($fields, $key, $default) {
    return isset($fields[$key]) && $fields[$key] !== '' ? $fields[$key] : $default;
}

function valoreCampoNome($fields, $key, $default) {
    if (!isset($fields[$key]) || !is_array($fields[$key]) || count($fields[$key]) === 0) {
        return $default;
    }

    $nomi = [];
    foreach ($fields[$key] as $item) {
        if (isset($item['name']) && $item['name'] !== '') {
            $nomi[] = $item['name'];
        }
    }

    return count($nomi) > 0 ? implode(', ', $nomi) : $default;
}

function creaMotivoFallback($debug, $payload) {
    if ($debug['curl_error'] !== '') {
        return 'Errore cURL: ' . $debug['curl_error'];
    }

    if ($debug['http_code'] !== 200) {
        return 'ReliefWeb non disponibile o appname non approvato. HTTP code: ' . $debug['http_code'];
    }

    if (!is_array($payload)) {
        return 'Risposta ReliefWeb non leggibile come JSON.';
    }

    if (!isset($payload['data']) || !is_array($payload['data'])) {
        return 'Risposta ReliefWeb senza data[].';
    }

    return 'ReliefWeb non disponibile. Visualizzazione fallback locale.';
}

function fallbackReportsFor($query) {
    $term = strtolower(trim($query));
    $fallbackData = [
        'syria' => [
            ['titolo' => 'Sintesi locale: accesso umanitario e bisogni essenziali in Siria', 'data' => '2025-10-02', 'fonte' => 'Fallback offline locale', 'paese' => 'Syria', 'url' => ''],
            ['titolo' => 'Aggiornamento locale: supporto sanitario e sfollamento interno in Siria', 'data' => '2025-09-24', 'fonte' => 'Fallback offline locale', 'paese' => 'Syria', 'url' => '']
        ],
        'lebanon' => [
            ['titolo' => 'Sintesi locale: assistenza umanitaria e protezione in Libano', 'data' => '2025-10-06', 'fonte' => 'Fallback offline locale', 'paese' => 'Lebanon', 'url' => ''],
            ['titolo' => 'Aggiornamento locale: bisogni alimentari e accoglienza in Libano', 'data' => '2025-09-28', 'fonte' => 'Fallback offline locale', 'paese' => 'Lebanon', 'url' => '']
        ],
        'ukraine' => [
            ['titolo' => 'Sintesi locale: condizioni dei rifugiati in Ucraina orientale', 'data' => '2025-10-29', 'fonte' => 'Fallback offline locale', 'paese' => 'Ukraine', 'url' => ''],
            ['titolo' => 'Aggiornamento locale: supporto umanitario coordinato per famiglie ucraine', 'data' => '2025-10-20', 'fonte' => 'Fallback offline locale', 'paese' => 'Ukraine', 'url' => '']
        ],
        'gaza' => [
            ['titolo' => 'Sintesi locale: sfide di accesso umanitario a Gaza', 'data' => '2025-11-10', 'fonte' => 'Fallback offline locale', 'paese' => 'Gaza', 'url' => ''],
            ['titolo' => 'Aggiornamento locale: carenze di acqua e medicine nella Striscia di Gaza', 'data' => '2025-11-08', 'fonte' => 'Fallback offline locale', 'paese' => 'Gaza', 'url' => '']
        ],
        'iran' => [
            ['titolo' => 'Sintesi locale: risposta umanitaria in Iran dopo la siccità', 'data' => '2025-09-15', 'fonte' => 'Fallback offline locale', 'paese' => 'Iran', 'url' => ''],
            ['titolo' => 'Aggiornamento locale: soccorso per comunità rurali in Iran', 'data' => '2025-09-10', 'fonte' => 'Fallback offline locale', 'paese' => 'Iran', 'url' => '']
        ]
    ];

    $aliases = [
        'siria' => 'syria',
        'syria' => 'syria',
        'libano' => 'lebanon',
        'lebanon' => 'lebanon',
        'ucraina' => 'ukraine',
        'ukraine' => 'ukraine',
        'gaza' => 'gaza',
        'iran' => 'iran'
    ];

    foreach ($aliases as $needle => $key) {
        if (strpos($term, $needle) !== false) {
            return $fallbackData[$key];
        }
    }

    return [
        ['titolo' => 'Nessun fallback disponibile per questa ricerca.', 'data' => '', 'fonte' => 'Fallback offline locale', 'paese' => $query, 'url' => '']
    ];
}
