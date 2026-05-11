<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

$mitreCollectionId = "x-mitre-collection--1f5f1533-f617-4ca8-9ab4-6a02367fa019";
$mitreBaseUrl = "https://attack-taxii.mitre.org/api/v21";

$mitreMap = [
    "ransomware" => [
        "tecnicaId" => "T1486",
        "objectId" => "attack-pattern--b80d107d-fa0d-4b60-9684-b0433e8bdba0",
        "nomeFallback" => "Data Encrypted for Impact"
    ],
    "ddos" => [
        "tecnicaId" => "T1498",
        "objectId" => "attack-pattern--d74c4a7e-ffbf-432f-9365-7ebf1f787cab",
        "nomeFallback" => "Network Denial of Service"
    ],
    "phishing avanzato" => [
        "tecnicaId" => "T1566.001",
        "objectId" => "attack-pattern--2e34237d-8574-43f6-aace-ae2915de8597",
        "nomeFallback" => "Spearphishing Attachment"
    ],
    "malware" => [
        "tecnicaId" => "T1204.002",
        "objectId" => "attack-pattern--232b7f21-adf9-4b42-b936-b9d6f7df856e",
        "nomeFallback" => "Malicious File"
    ]
];

$fallback = [
    "ransomware" => [
        "tecnicaId" => "T1486",
        "nomeTecnica" => "Data Encrypted for Impact",
        "descrizioneTecnica" => "Tecnica in cui l'attaccante cifra dati o sistemi per interrompere operazioni e causare impatto.",
        "tattiche" => ["impact"],
        "contromisure" => ["backup", "segmentazione", "EDR", "least privilege"],
        "urlMitre" => "https://attack.mitre.org/techniques/T1486/",
        "fonte" => "Fallback locale"
    ],
    "ddos" => [
        "tecnicaId" => "T1498",
        "nomeTecnica" => "Network Denial of Service",
        "descrizioneTecnica" => "Attacco che mira a rendere indisponibili servizi o reti tramite sovraccarico.",
        "tattiche" => ["impact"],
        "contromisure" => ["rate limiting", "CDN", "anti-DDoS", "firewall"],
        "urlMitre" => "https://attack.mitre.org/techniques/T1498/",
        "fonte" => "Fallback locale"
    ],
    "phishing avanzato" => [
        "tecnicaId" => "T1566.001",
        "nomeTecnica" => "Spearphishing Attachment",
        "descrizioneTecnica" => "Tecnica di accesso iniziale basata su messaggi ingannevoli per ottenere credenziali o esecuzione.",
        "tattiche" => ["initial-access"],
        "contromisure" => ["MFA", "email filtering", "formazione utenti", "zero trust"],
        "urlMitre" => "https://attack.mitre.org/techniques/T1566/001/",
        "fonte" => "Fallback locale"
    ],
    "malware" => [
        "tecnicaId" => "T1204.002",
        "nomeTecnica" => "Malicious File",
        "descrizioneTecnica" => "Software malevolo usato per compromettere, danneggiare o controllare sistemi.",
        "tattiche" => ["execution"],
        "contromisure" => ["EDR", "patching", "sandboxing", "antivirus", "least privilege"],
        "urlMitre" => "https://attack.mitre.org/techniques/T1204/002/",
        "fonte" => "Fallback locale"
    ]
];

function normalizzaMetodo($metodoAttacco) {
    $metodo = strtolower(trim($metodoAttacco));

    if ($metodo === "phishing") {
        return "phishing avanzato";
    }

    return $metodo;
}

function getFallbackTecnica($metodo, $fallback) {
    if (isset($fallback[$metodo])) {
        return $fallback[$metodo];
    }

    return [
        "tecnicaId" => "N/D",
        "nomeTecnica" => "Tecnica non mappata",
        "descrizioneTecnica" => "Metodo di attacco non presente nella knowledge base locale.",
        "tattiche" => [],
        "contromisure" => [],
        "urlMitre" => "",
        "fonte" => "Fallback locale"
    ];
}

function chiamaMitreTaxii($objectId, $collectionId, $baseUrl) {
    $url = $baseUrl . "/collections/" . $collectionId . "/objects/" . $objectId . "/";

    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "timeout" => 8,
            "header" => "Accept: application/taxii+json;version=2.1\r\n"
        ]
    ]);

    $risposta = @file_get_contents($url, false, $context);

    if ($risposta === false) {
        return null;
    }

    $json = json_decode($risposta, true);

    if (!isset($json["objects"][0]) || !is_array($json["objects"][0])) {
        return null;
    }

    return $json["objects"][0];
}

function normalizzaTecnicaMitre($oggettoMitre, $fallbackTecnica) {
    $tecnicaId = $fallbackTecnica["tecnicaId"];
    $urlMitre = $fallbackTecnica["urlMitre"];

    if (isset($oggettoMitre["external_references"]) && is_array($oggettoMitre["external_references"])) {
        foreach ($oggettoMitre["external_references"] as $reference) {
            if (($reference["source_name"] ?? "") === "mitre-attack") {
                $tecnicaId = $reference["external_id"] ?? $tecnicaId;
                $urlMitre = $reference["url"] ?? $urlMitre;
                break;
            }
        }
    }

    $tattiche = [];
    if (isset($oggettoMitre["kill_chain_phases"]) && is_array($oggettoMitre["kill_chain_phases"])) {
        foreach ($oggettoMitre["kill_chain_phases"] as $fase) {
            if (isset($fase["phase_name"])) {
                $tattiche[] = $fase["phase_name"];
            }
        }
    }

    if (count($tattiche) === 0) {
        $tattiche = $fallbackTecnica["tattiche"];
    }

    return [
        "tecnicaId" => $tecnicaId,
        "nomeTecnica" => $oggettoMitre["name"] ?? $fallbackTecnica["nomeTecnica"],
        "descrizioneTecnica" => $oggettoMitre["description"] ?? $fallbackTecnica["descrizioneTecnica"],
        "tattiche" => $tattiche,
        "contromisure" => $fallbackTecnica["contromisure"],
        "urlMitre" => $urlMitre,
        "fonte" => "MITRE ATT&CK TAXII API"
    ];
}

try {
    $sql = "
        SELECT
            c.idCyberattacco,
            c.idEvento,
            c.tipoBersaglio,
            c.livelloImpatto,
            c.metodoAttacco,
            e.luogo,
            e.dataEvento,
            e.livelloGravita
        FROM cyberattacco c
        INNER JOIN evento e ON c.idEvento = e.idEvento
        ORDER BY e.dataEvento DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $cyberattacchi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $risultati = [];
    $cacheMitre = [];

    foreach ($cyberattacchi as $cyberattacco) {
        $metodo = normalizzaMetodo($cyberattacco["metodoAttacco"]);
        $fallbackTecnica = getFallbackTecnica($metodo, $fallback);
        $tecnica = $fallbackTecnica;

        if (isset($mitreMap[$metodo])) {
            $objectId = $mitreMap[$metodo]["objectId"];

            if (!array_key_exists($objectId, $cacheMitre)) {
                $cacheMitre[$objectId] = chiamaMitreTaxii($objectId, $mitreCollectionId, $mitreBaseUrl);
            }

            if ($cacheMitre[$objectId] !== null) {
                $tecnica = normalizzaTecnicaMitre($cacheMitre[$objectId], $fallbackTecnica);
            }
        }

        $risultati[] = [
            "idCyberattacco" => $cyberattacco["idCyberattacco"],
            "idEvento" => $cyberattacco["idEvento"],
            "luogo" => $cyberattacco["luogo"],
            "dataEvento" => $cyberattacco["dataEvento"],
            "livelloGravita" => $cyberattacco["livelloGravita"],
            "tipoBersaglio" => $cyberattacco["tipoBersaglio"],
            "livelloImpatto" => $cyberattacco["livelloImpatto"],
            "metodoAttacco" => $cyberattacco["metodoAttacco"],
            "tecnicaId" => $tecnica["tecnicaId"],
            "nomeTecnica" => $tecnica["nomeTecnica"],
            "descrizioneTecnica" => $tecnica["descrizioneTecnica"],
            "tattiche" => $tecnica["tattiche"],
            "contromisure" => $tecnica["contromisure"],
            "urlMitre" => $tecnica["urlMitre"],
            "fonte" => $tecnica["fonte"]
        ];
    }

    echo json_encode($risultati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "errore" => "Errore nel recupero dati MITRE ATT&CK",
        "dettaglio" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
