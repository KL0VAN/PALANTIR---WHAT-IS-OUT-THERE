<?php
header('Content-Type: application/json; charset=utf-8');

$apiKey = leggiVariabileAmbiente('OPENROUTER_API_KEY');
$model = "openai/gpt-oss-120b:free";
$prompt = leggiPrompt();

if ($prompt === '') {
    echo json_encode([
        'success' => false,
        'errore' => 'Prompt vuoto. Inserisci una crisi geopolitica o cyber da analizzare.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($apiKey === '' || $apiKey === 'your_openrouter_api_key_here') {
    echo json_encode([
        'success' => false,
        'errore' => 'Chiave OpenRouter non configurata nel backend PHP.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'model' => $model,
    'max_tokens' => 700,
    'temperature' => 0.3,
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are an intelligence analyst for a crisis dashboard. Provide a concise structured analysis with: summary, key risks, cyber angle if relevant, humanitarian impact, and short outlook. Do not reveal hidden reasoning.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ]
];

$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 90,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$raw = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError !== '') {
    echo json_encode([
        'success' => false,
        'errore' => 'Errore cURL: ' . $curlError
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($raw, true);

if ($httpCode < 200 || $httpCode >= 300) {
    echo json_encode([
        'success' => false,
        'http_code' => $httpCode,
        'errore' => estraiErroreApi($data, $raw)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$rispostaAi = $data['choices'][0]['message']['content'] ?? '';

if ($rispostaAi === '') {
    echo json_encode([
        'success' => false,
        'http_code' => $httpCode,
        'errore' => 'Risposta OpenRouter priva di contenuto utilizzabile.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'http_code' => $httpCode,
    'risposta_ai' => $rispostaAi
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function leggiPrompt() {
    if (isset($_POST['prompt'])) {
        return trim($_POST['prompt']);
    }

    $rawInput = file_get_contents('php://input');
    if ($rawInput !== false && trim($rawInput) !== '') {
        $json = json_decode($rawInput, true);
        if (isset($json['prompt'])) {
            return trim($json['prompt']);
        }
    }

    if (isset($_GET['prompt'])) {
        return trim($_GET['prompt']);
    }

    if (isset($_GET['q'])) {
        return trim($_GET['q']);
    }

    return '';
}

function leggiVariabileAmbiente($nome) {
    $valore = getenv($nome);

    if ($valore !== false && trim($valore) !== '') {
        return trim($valore);
    }

    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    if (!is_readable($envPath)) {
        return '';
    }

    $righe = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($righe as $riga) {
        $riga = trim($riga);

        if ($riga === '' || strpos($riga, '#') === 0 || strpos($riga, '=') === false) {
            continue;
        }

        [$chiave, $valore] = explode('=', $riga, 2);

        if (trim($chiave) === $nome) {
            return trim($valore, " \t\n\r\0\x0B\"'");
        }
    }

    return '';
}

function estraiErroreApi($data, $raw) {
    if (isset($data['error']['message'])) {
        return 'Errore OpenRouter: ' . $data['error']['message'];
    }

    if (isset($data['message'])) {
        return 'Errore OpenRouter: ' . $data['message'];
    }

    return 'Errore OpenRouter non specificato. Risposta grezza: ' . substr((string) $raw, 0, 400);
}
