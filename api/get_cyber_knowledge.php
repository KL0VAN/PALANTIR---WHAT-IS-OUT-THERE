<?php

header("Content-Type: application/json; charset=UTF-8");

$knowledge_base = [
    "ransomware" => [
        "categoria" => "Malware",
        "descrizioneMetodo" => "Software malevolo che cifra i dati dell'utente e richiede riscatto per la decryption",
        "rischioTecnico" => "Critico",
        "esempio" => "WannaCry, Petya, Ryuk",
        "contromisure" => [
            "Backup regolari offline",
            "Aggiornamenti di sistema tempestivi",
            "Segmentazione di rete",
            "EDR (Endpoint Detection and Response)",
            "Formazione utenti su phishing"
        ]
    ],
    "ddos" => [
        "categoria" => "Attacco di Rete",
        "descrizioneMetodo" => "Sovraccarico di un servizio inviando traffico massivo da più fonti, negando l'accesso agli utenti legittimi",
        "rischioTecnico" => "Alto",
        "esempio" => "Mirai, Botnet Dyn, BGP Hijacking",
        "contromisure" => [
            "Mitigation services (Cloudflare, Akamai)",
            "Rate limiting e filtri di traffico",
            "Ridondanza geografica",
            "Autoscaling infrastructure",
            "Monitoraggio 24/7"
        ]
    ],
    "phishing" => [
        "categoria" => "Ingegneria Sociale",
        "descrizioneMetodo" => "Email o messaggi ingannevoli per indurre utenti a rivelare credenziali o scaricare malware",
        "rischioTecnico" => "Medio-Alto",
        "esempio" => "Spear phishing, Whaling, CEO Fraud",
        "contromisure" => [
            "Autenticazione Multi-fattore (MFA)",
            "Email filtering e DKIM/SPF/DMARC",
            "Sensibilizzazione continua",
            "Password manager aziendale",
            "Analisi di URL e reputazione mittente"
        ]
    ],
    "malware" => [
        "categoria" => "Malware",
        "descrizioneMetodo" => "Software malevolo progettato per infiltrarsi, raccogliere dati o controllare sistemi compromessi",
        "rischioTecnico" => "Critico",
        "esempio" => "Trojan, Worm, Virus, Spyware, Rootkit",
        "contromisure" => [
            "Antivirus/Antimalware aggiornato",
            "Sandbox per file sospetti",
            "Disattivazione AutoPlay USB",
            "Whitelist applicazioni",
            "Isolamento di rete"
        ]
    ],
    "sql injection" => [
        "categoria" => "Injection Attack",
        "descrizioneMetodo" => "Inserimento di comandi SQL malevoli in campi di input per accedere o modificare dati del database",
        "rischioTecnico" => "Critico",
        "esempio" => "Login bypass, Data exfiltration, Database deletion",
        "contromisure" => [
            "Prepared statements e parameterized queries",
            "Input validation e sanitization",
            "Principio del minimo privilegio (DB user)",
            "Web Application Firewall (WAF)",
            "Logging e monitoring query anomale"
        ]
    ],
    "xss" => [
        "categoria" => "Web Vulnerability",
        "descrizioneMetodo" => "Iniezione di codice JavaScript malevolo in pagine web per rubare session o reindirizzare utenti",
        "rischioTecnico" => "Medio-Alto",
        "esempio" => "Stored XSS, Reflected XSS, DOM-based XSS",
        "contromisure" => [
            "Output encoding e escaping",
            "Content Security Policy (CSP)",
            "Input validation",
            "HTTPOnly cookie flag",
            "Regular security testing"
        ]
    ],
    "brute force" => [
        "categoria" => "Credential Attack",
        "descrizioneMetodo" => "Tentativi ripetuti e automatizzati di indovinare password o credenziali di accesso",
        "rischioTecnico" => "Medio",
        "esempio" => "Dictionary attack, Rainbow table, Account lockout bypass",
        "contromisure" => [
            "Rate limiting su tentativi di login falliti",
            "Account lockout temporaneo",
            "Multi-Factor Authentication (MFA)",
            "Password policy robusta",
            "IP reputation service e CAPTCHA"
        ]
    ],
    "zero day" => [
        "categoria" => "Exploitative Attack",
        "descrizioneMetodo" => "Sfruttamento di vulnerabilità sconosciuta non ancora patchata dal vendor",
        "rischioTecnico" => "Critico",
        "esempio" => "Log4j, Microsoft Exchange ProxyLogon, Apple WebKit CVE",
        "contromisure" => [
            "Defense in depth strategy",
            "Monitoraggio comportamentale anomalo",
            "Threat intelligence sharing",
            "Rapid incident response capability",
            "Patch management prioritario"
        ]
    ],
    "social engineering" => [
        "categoria" => "Ingegneria Sociale",
        "descrizioneMetodo" => "Manipolazione psicologica per indurre persone a rivelare informazioni riservate o compromettere sicurezza",
        "rischioTecnico" => "Medio-Alto",
        "esempio" => "Pretexting, Baiting, Tailgating, Vishing",
        "contromisure" => [
            "Programma di sensibilizzazione security awareness",
            "Verification protocol per accesso fisico",
            "Policy di verifica identità robusta",
            "Whistleblower program",
            "Monitoraggio e auditing privileged access"
        ]
    ]
];

try {
    require_once "db.php";

    $query = "
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
        JOIN evento e ON c.idEvento = e.idEvento
        ORDER BY e.dataEvento DESC
        LIMIT 100
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $risultati = [];

    foreach ($rows as $row) {
        $metodo_lower = strtolower(str_replace(" ", "_", $row['metodoAttacco']));

        $knowledge = $knowledge_base[$metodo_lower] ?? [
            "categoria" => "Categoria non definita",
            "descrizioneMetodo" => "Nessuna descrizione disponibile per questo metodo di attacco.",
            "rischioTecnico" => "Sconosciuto",
            "esempio" => "Non specificato",
            "contromisure" => ["Non disponibili"]
        ];

        $risultati[] = [
            "idCyberattacco" => $row['idCyberattacco'],
            "idEvento" => $row['idEvento'],
            "luogo" => $row['luogo'],
            "dataEvento" => $row['dataEvento'],
            "tipoBersaglio" => $row['tipoBersaglio'],
            "livelloImpatto" => $row['livelloImpatto'],
            "metodoAttacco" => $row['metodoAttacco'],
            "categoria" => $knowledge['categoria'],
            "descrizioneMetodo" => $knowledge['descrizioneMetodo'],
            "rischioTecnico" => $knowledge['rischioTecnico'],
            "esempio" => $knowledge['esempio'],
            "contromisure" => $knowledge['contromisure']
        ];
    }

    echo json_encode($risultati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "errore" => "Errore nel recupero della knowledge base",
        "dettaglio" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
