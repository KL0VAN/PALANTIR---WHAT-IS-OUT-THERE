<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

try {
    $tabellaPaesi = trovaTabellaPaesi($conn);
    $colonnePaese = colonneTabella($conn, $tabellaPaesi);

    $colonnaNome = primaColonnaDisponibile($colonnePaese, [
        "nome",
        "nomePaese",
        "name"
    ]);

    if ($colonnaNome === null) {
        throw new RuntimeException("La tabella $tabellaPaesi non contiene una colonna nome/nomePaese/name.");
    }

    $sql = "
        SELECT
            c.idEvento,
            c.idPaese,
            c.ruoloNelEvento,
            " . colonnaSql($colonnaNome, "nomePaese") . ",
            " . colonnaOpzionaleSql($colonnePaese, [
                "codicePaese",
                "codiceISO",
                "codiceIso",
                "codice_iso",
                "iso2",
                "iso_2",
                "cca2",
                "alpha2",
                "codiceAlpha2"
            ], "codicePaese") . ",
            " . colonnaOpzionaleSql($colonnePaese, [
                "areaGeografica",
                "area_geografica",
                "area"
            ], "areaGeografica") . ",
            " . colonnaOpzionaleSql($colonnePaese, [
                "alleanza",
                "alliance"
            ], "alleanza") . ",
            " . colonnaOpzionaleSql($colonnePaese, [
                "ruolo",
                "ruoloConflitto"
            ], "ruoloConflitto") . "
        FROM coinvolgimento c
        INNER JOIN " . quoteIdentificatore($tabellaPaesi) . " p ON c.idPaese = p.idPaese
        ORDER BY c.idEvento ASC, c.idPaese ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $coinvolgimenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($coinvolgimenti, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        "errore" => "Errore nel recupero dei coinvolgimenti",
        "dettaglio" => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function trovaTabellaPaesi(PDO $conn) {
    $tabelle = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $candidate = ["Paese", "paese", "Paesi", "paesi"];

    foreach ($candidate as $nomeTabella) {
        if (in_array($nomeTabella, $tabelle, true)) {
            return $nomeTabella;
        }
    }

    foreach ($tabelle as $nomeTabella) {
        if (strcasecmp($nomeTabella, "paese") === 0 || strcasecmp($nomeTabella, "paesi") === 0) {
            return $nomeTabella;
        }
    }

    throw new RuntimeException("Tabella Paese/paese non trovata nel database locale.");
}

function colonneTabella(PDO $conn, $tabella) {
    $stmt = $conn->query("SHOW COLUMNS FROM " . quoteIdentificatore($tabella));
    $colonne = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($colonna) {
        return $colonna["Field"];
    }, $colonne);
}

function primaColonnaDisponibile(array $colonne, array $candidate) {
    foreach ($candidate as $candidata) {
        foreach ($colonne as $colonna) {
            if (strcasecmp($colonna, $candidata) === 0) {
                return $colonna;
            }
        }
    }

    return null;
}

function colonnaOpzionaleSql(array $colonne, array $candidate, $alias) {
    $colonna = primaColonnaDisponibile($colonne, $candidate);

    if ($colonna === null) {
        return "NULL AS " . quoteIdentificatore($alias);
    }

    return colonnaSql($colonna, $alias);
}

function colonnaSql($colonna, $alias) {
    return "p." . quoteIdentificatore($colonna) . " AS " . quoteIdentificatore($alias);
}

function quoteIdentificatore($identificatore) {
    return "`" . str_replace("`", "``", $identificatore) . "`";
}
