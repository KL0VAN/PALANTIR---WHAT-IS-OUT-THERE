<?php

function connessioneDbLocaleSeDisponibile() {
    static $connessioneCaricata = false;
    static $connessione = null;

    if ($connessioneCaricata) {
        return $connessione;
    }

    $connessioneCaricata = true;
    $configDefault = [
        "DB_HOST" => "localhost",
        "DB_NAME" => "palantir",
        "DB_USER" => "root",
        "DB_PASS" => "",
        "DB_PORT" => "3306"
    ];

    $configEnv = leggiEnvFallback();
    $configurazioni = [];

    if (count($configEnv) > 0) {
        $configurazioni[] = array_merge($configDefault, $configEnv);
    }

    $configurazioni[] = $configDefault;

    foreach ($configurazioni as $config) {
        try {
            $dsn = "mysql:host={$config["DB_HOST"]};port={$config["DB_PORT"]};dbname={$config["DB_NAME"]};charset=utf8";
            $connessione = new PDO($dsn, $config["DB_USER"], $config["DB_PASS"]);
            $connessione->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $connessione;
        } catch (Throwable $e) {
            $connessione = null;
        }
    }

    return $connessione;
}

function leggiEnvFallback() {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . ".env";

    if (!is_readable($envPath)) {
        return [];
    }

    $config = [];
    $righe = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($righe as $riga) {
        $riga = trim($riga);

        if ($riga === "" || strpos($riga, "#") === 0 || strpos($riga, "=") === false) {
            continue;
        }

        [$chiave, $valore] = explode("=", $riga, 2);
        $config[trim($chiave)] = trim($valore, " \t\n\r\0\x0B\"'");
    }

    return $config;
}

function fallbackEventi() {
    return [
        [
            "idEvento" => 1,
            "dataEvento" => "2026-05-14",
            "luogo" => "Iran",
            "tipologia" => "Militare/Cyber",
            "numeroStimatoVittime" => 120,
            "descrizione" => "Escalation regionale con rischio militare e cyber.",
            "latitudine" => 35.6892,
            "longitudine" => 51.3890,
            "fonte" => "Fallback offline locale",
            "livelloGravita" => "critico"
        ],
        [
            "idEvento" => 2,
            "dataEvento" => "2026-05-14",
            "luogo" => "Israele",
            "tipologia" => "Militare/Cyber",
            "numeroStimatoVittime" => 85,
            "descrizione" => "Area critica esposta a tensione militare e operazioni informatiche.",
            "latitudine" => 31.7683,
            "longitudine" => 35.2137,
            "fonte" => "Fallback offline locale",
            "livelloGravita" => "critico"
        ],
        [
            "idEvento" => 3,
            "dataEvento" => "2026-05-14",
            "luogo" => "Siria",
            "tipologia" => "Umanitaria",
            "numeroStimatoVittime" => 45,
            "descrizione" => "Crisi umanitaria con accesso limitato ad aiuti e servizi essenziali.",
            "latitudine" => 33.5138,
            "longitudine" => 36.2765,
            "fonte" => "Fallback offline locale",
            "livelloGravita" => "alto"
        ],
        [
            "idEvento" => 4,
            "dataEvento" => "2026-05-14",
            "luogo" => "Ucraina",
            "tipologia" => "Militare",
            "numeroStimatoVittime" => 40,
            "descrizione" => "Conflitto attivo con impatto su infrastrutture e popolazione civile.",
            "latitudine" => 50.4501,
            "longitudine" => 30.5234,
            "fonte" => "Fallback offline locale",
            "livelloGravita" => "alto"
        ]
    ];
}

function fallbackCoinvolgimenti() {
    return [
        [
            "idEvento" => 1,
            "idPaese" => 109,
            "ruoloNelEvento" => "Area critica",
            "nomePaese" => "Iran",
            "codicePaese" => "IR",
            "areaGeografica" => "Medio Oriente",
            "alleanza" => "N/D",
            "ruoloConflitto" => "Area critica"
        ],
        [
            "idEvento" => 2,
            "idPaese" => 90,
            "ruoloNelEvento" => "Area critica",
            "nomePaese" => "Israele",
            "codicePaese" => "IL",
            "areaGeografica" => "Medio Oriente",
            "alleanza" => "N/D",
            "ruoloConflitto" => "Area critica"
        ],
        [
            "idEvento" => 3,
            "idPaese" => 110,
            "ruoloNelEvento" => "Crisi umanitaria",
            "nomePaese" => "Siria",
            "codicePaese" => "SY",
            "areaGeografica" => "Medio Oriente",
            "alleanza" => "N/D",
            "ruoloConflitto" => "Crisi umanitaria"
        ],
        [
            "idEvento" => 4,
            "idPaese" => 150,
            "ruoloNelEvento" => "Conflitto attivo",
            "nomePaese" => "Ucraina",
            "codicePaese" => "UA",
            "areaGeografica" => "Europa orientale",
            "alleanza" => "N/D",
            "ruoloConflitto" => "Conflitto attivo"
        ]
    ];
}

function fallbackCyberattacchi() {
    return [
        [
            "idCyberattacco" => 1,
            "idEvento" => 1,
            "luogo" => "Iran",
            "dataEvento" => "2026-05-14",
            "livelloGravita" => "critico",
            "tipoBersaglio" => "Infrastrutture critiche",
            "livelloImpatto" => "critico",
            "metodoAttacco" => "DDoS"
        ],
        [
            "idCyberattacco" => 2,
            "idEvento" => 2,
            "luogo" => "Israele",
            "dataEvento" => "2026-05-14",
            "livelloGravita" => "critico",
            "tipoBersaglio" => "Sistemi governativi",
            "livelloImpatto" => "alto",
            "metodoAttacco" => "Phishing"
        ],
        [
            "idCyberattacco" => 3,
            "idEvento" => 4,
            "luogo" => "Ucraina",
            "dataEvento" => "2026-05-14",
            "livelloGravita" => "alto",
            "tipoBersaglio" => "Reti energetiche",
            "livelloImpatto" => "alto",
            "metodoAttacco" => "Malware"
        ]
    ];
}

function fallbackInterventi() {
    return [
        [
            "idIntervento" => 1,
            "organizzazione" => "ONU",
            "tipoIntervento" => "Aiuti umanitari",
            "dataIntervento" => "2026-05-14",
            "area" => "Medio Oriente",
            "idEvento" => 3,
            "luogoEvento" => "Siria",
            "tipologiaEvento" => "Umanitaria"
        ],
        [
            "idIntervento" => 2,
            "organizzazione" => "Croce Rossa",
            "tipoIntervento" => "Supporto sanitario",
            "dataIntervento" => "2026-05-14",
            "area" => "Europa orientale",
            "idEvento" => 4,
            "luogoEvento" => "Ucraina",
            "tipologiaEvento" => "Militare"
        ]
    ];
}

function fallbackStatistiche() {
    $eventi = fallbackEventi();
    $cyberattacchi = fallbackCyberattacchi();
    $interventi = fallbackInterventi();
    $critici = array_filter($eventi, function ($evento) {
        return strtolower($evento["livelloGravita"]) === "critico";
    });

    return [
        "totaleEventi" => count($eventi),
        "totaleCyberattacchi" => count($cyberattacchi),
        "totaleInterventi" => count($interventi),
        "eventiCritici" => count($critici)
    ];
}

function fallbackPaesiPerId() {
    return [
        2 => paeseFallback("Francia", "FR", "Europa occidentale", "NATO / UE", "Mediatore"),
        3 => paeseFallback("Russia", "RU", "Europa orientale / Asia", "N/D", "Attore cyber"),
        4 => paeseFallback("Italia", "IT", "Europa meridionale", "NATO / UE", "Supporto diplomatico"),
        21 => paeseFallback("Germania", "DE", "Europa centrale", "NATO / UE", "Supporto diplomatico"),
        22 => paeseFallback("Svizzera", "CH", "Europa centrale", "Neutrale", "Paese ospitante"),
        32 => paeseFallback("Egitto", "EG", "Nord Africa / Mar Rosso", "N/D", "Area strategica"),
        48 => paeseFallback("Stati Uniti", "US", "Nord America", "NATO", "Attore militare/cyber"),
        89 => paeseFallback("Arabia Saudita", "SA", "Medio Oriente", "N/D", "Supporto regionale"),
        90 => paeseFallback("Israele", "IL", "Medio Oriente", "N/D", "Area critica"),
        91 => paeseFallback("Emirati Arabi Uniti", "AE", "Medio Oriente", "N/D", "Supporto regionale"),
        92 => paeseFallback("Qatar", "QA", "Medio Oriente", "N/D", "Mediatore"),
        95 => paeseFallback("Giordania", "JO", "Medio Oriente", "N/D", "Area limitrofa"),
        97 => paeseFallback("Iraq", "IQ", "Medio Oriente", "N/D", "Area colpita"),
        98 => paeseFallback("Libano", "LB", "Medio Oriente", "N/D", "Area colpita"),
        105 => paeseFallback("Turchia", "TR", "Medio Oriente / Europa", "NATO", "Mediatore regionale"),
        109 => paeseFallback("Iran", "IR", "Medio Oriente", "N/D", "Area critica"),
        110 => paeseFallback("Siria", "SY", "Medio Oriente", "N/D", "Crisi umanitaria"),
        150 => paeseFallback("Ucraina", "UA", "Europa orientale", "N/D", "Conflitto attivo")
    ];
}

function paeseFallback($nome, $codice, $area, $alleanza, $ruolo) {
    return [
        "nomePaese" => $nome,
        "codicePaese" => $codice,
        "areaGeografica" => $area,
        "alleanza" => $alleanza,
        "ruoloConflitto" => $ruolo
    ];
}

function outputJson($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
