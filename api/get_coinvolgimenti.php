<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "fallback_data.php";
$conn = connessioneDbLocaleSeDisponibile();

try {
    if (!$conn instanceof PDO) {
        outputJson(fallbackCoinvolgimenti());
    }

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
    if ($conn instanceof PDO) {
        $coinvolgimenti = caricaCoinvolgimentiConPaesiFallback($conn);

        if (count($coinvolgimenti) > 0) {
            outputJson($coinvolgimenti);
        }
    }

    outputJson(fallbackCoinvolgimenti());
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

function caricaCoinvolgimentiConPaesiFallback(PDO $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT
                idEvento,
                idPaese,
                ruoloNelEvento
            FROM coinvolgimento
            ORDER BY idEvento ASC, idPaese ASC
        ");
        $stmt->execute();

        $coinvolgimenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $paesiFallback = fallbackPaesiPerId();

        foreach ($coinvolgimenti as &$coinvolgimento) {
            $idPaese = (int) $coinvolgimento["idPaese"];
            $paese = $paesiFallback[$idPaese] ?? null;

            if ($paese === null) {
                $coinvolgimento["nomePaese"] = "Paese non trovato";
                $coinvolgimento["codicePaese"] = null;
                $coinvolgimento["areaGeografica"] = null;
                $coinvolgimento["alleanza"] = null;
                $coinvolgimento["ruoloConflitto"] = null;
                continue;
            }

            $coinvolgimento["nomePaese"] = $paese["nomePaese"];
            $coinvolgimento["codicePaese"] = $paese["codicePaese"];
            $coinvolgimento["areaGeografica"] = $paese["areaGeografica"];
            $coinvolgimento["alleanza"] = $paese["alleanza"];
            $coinvolgimento["ruoloConflitto"] = $paese["ruoloConflitto"];
        }

        return $coinvolgimenti;
    } catch (Throwable $e) {
        return [];
    }
}
