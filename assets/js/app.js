let eventi = [];
let coinvolgimenti = [];

document.addEventListener("DOMContentLoaded", () => {
    inizializzaControlliZoomMappa();
    caricaDatiIniziali();

    inizializzaRicercaPaesi();
    caricaPaesiIniziali();

    inizializzaReliefWeb();
    caricaReportReliefWeb("Syria");

    caricaCyberKnowledge();

    caricaMitreAttack();

    const filtro = document.getElementById("filtroTipologia");

    filtro.addEventListener("change", () => {
        mostraEventi(filtro.value);
    });
});

async function caricaDatiIniziali() {
    await caricaCoinvolgimenti();
    await caricaEventi();

    inizializzaMappaRischio();
    caricaStatistiche();
    caricaCyberattacchi();
    caricaInterventi();
}

/*
    Questa funzione carica i coinvolgimenti.
    Il file PHP fa già il lavoro distribuito:

    DB locale → coinvolgimento
    DB remoto → paese
    PHP → JSON finale
*/
async function caricaCoinvolgimenti() {
    try {
        const risposta = await fetchConTimeout("api/get_coinvolgimenti.php", 6000);

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento dei coinvolgimenti");
        }

        coinvolgimenti = await risposta.json();

        console.log("Coinvolgimenti caricati:", coinvolgimenti);

    } catch (errore) {
        console.error("Errore coinvolgimenti:", errore);
        coinvolgimenti = [];
    }
}

function fetchConTimeout(url, timeout = 6000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);

    return fetch(url, { signal: controller.signal })
        .finally(() => clearTimeout(timer));
}

async function caricaEventi() {
    try {
        const risposta = await fetch("api/get_eventi.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento degli eventi");
        }

        eventi = await risposta.json();

        mostraEventi("tutti");

    } catch (errore) {
        console.error("Errore eventi:", errore);

        document.getElementById("tabellaEventi").innerHTML = `
            <tr>
                <td colspan="8">Errore nel caricamento degli eventi.</td>
            </tr>
        `;
    }
}

function mostraEventi(filtro) {
    const tabella = document.getElementById("tabellaEventi");
    tabella.innerHTML = "";

    let eventiFiltrati = [...eventi];

    if (filtro !== "tutti") {
        eventiFiltrati = eventi.filter(evento =>
            evento.tipologia.toLowerCase() === filtro
        );
    }

    eventiFiltrati.sort((a, b) => Number(a.idEvento) - Number(b.idEvento));

    eventiFiltrati.forEach(evento => {
        const riga = document.createElement("tr");

        riga.innerHTML = `
            <td>${evento.idEvento}</td>
            <td>${evento.dataEvento}</td>
            <td>${evento.luogo}</td>
            <td>
                <span class="badge ${evento.tipologia.toLowerCase()}">
                    ${evento.tipologia}
                </span>
            </td>
            <td>${evento.numeroStimatoVittime}</td>
            <td class="${evento.livelloGravita.toLowerCase()}">
                ${evento.livelloGravita}
            </td>
            <td>
                <div class="paesi-list">
                    ${formattaPaesiEvento(evento.idEvento)}
                </div>
            </td>
            <td>${evento.fonte}</td>
        `;

        tabella.appendChild(riga);
    });
}

function formattaPaesiEvento(idEvento) {
    const listaPaesi = coinvolgimenti.filter(coinvolgimento =>
        Number(coinvolgimento.idEvento) === Number(idEvento)
    );

    if (listaPaesi.length === 0) {
        return `<span class="muted">Nessun Paese collegato</span>`;
    }

    return listaPaesi.map(coinvolgimento => {
        return `
            <span class="paese-pill">
                <strong>${coinvolgimento.nomePaese}</strong>
                <small>${coinvolgimento.ruoloNelEvento}</small>
                <small>${coinvolgimento.areaGeografica ?? ""}</small>
                <small>${coinvolgimento.alleanza ?? ""}</small>
            </span>
        `;
    }).join("");
}

async function caricaStatistiche() {
    try {
        const risposta = await fetch("api/get_statistiche.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento delle statistiche");
        }

        const statistiche = await risposta.json();

        document.getElementById("totaleEventi").textContent = statistiche.totaleEventi;
        document.getElementById("totaleCyber").textContent = statistiche.totaleCyberattacchi;
        document.getElementById("totaleInterventi").textContent = statistiche.totaleInterventi;
        document.getElementById("totaleCritici").textContent = statistiche.eventiCritici;

    } catch (errore) {
        console.error("Errore statistiche:", errore);
    }
}

async function caricaCyberattacchi() {
    try {
        const risposta = await fetch("api/get_cyberattacchi.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento dei cyberattacchi");
        }

        const cyberattacchi = await risposta.json();
        const tabella = document.getElementById("tabellaCyberattacchi");

        tabella.innerHTML = "";

        cyberattacchi
            .sort((a, b) => Number(a.idCyberattacco) - Number(b.idCyberattacco))
            .forEach(cyber => {
            const riga = document.createElement("tr");

            riga.innerHTML = `
                <td>${cyber.idCyberattacco}</td>
                <td>${cyber.idEvento}</td>
                <td>${cyber.luogo}</td>
                <td>${cyber.tipoBersaglio}</td>
                <td class="${cyber.livelloImpatto.toLowerCase()}">
                    ${cyber.livelloImpatto}
                </td>
                <td>${cyber.metodoAttacco}</td>
            `;

            tabella.appendChild(riga);
        });

    } catch (errore) {
        console.error("Errore cyberattacchi:", errore);
    }
}

async function caricaInterventi() {
    try {
        const risposta = await fetch("api/get_interventi.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento degli interventi");
        }

        const interventi = await risposta.json();
        const tabella = document.getElementById("tabellaInterventi");

        tabella.innerHTML = "";

        interventi
            .sort((a, b) => Number(a.idIntervento) - Number(b.idIntervento))
            .forEach(intervento => {
            const riga = document.createElement("tr");

            riga.innerHTML = `
                <td>${intervento.idIntervento}</td>
                <td>${intervento.organizzazione}</td>
                <td>${intervento.tipoIntervento}</td>
                <td>${intervento.dataIntervento}</td>
                <td>${intervento.area}</td>
                <td>${intervento.luogoEvento ?? "Nessun evento collegato"}</td>
            `;

            tabella.appendChild(riga);
        });

    } catch (errore) {
        console.error("Errore interventi:", errore);
    }
}

function inizializzaRicercaPaesi() {
    const input = document.getElementById("countrySearchInput");
    const bottone = document.getElementById("countrySearchBtn");

    bottone.addEventListener("click", () => {
        const ricerca = input.value.trim();

        if (ricerca.length === 0) {
            mostraErrorePaesi("Inserisci il nome di un Paese.");
            return;
        }

        cercaPaeseRestCountries(ricerca);
    });

    input.addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            bottone.click();
        }
    });
}

async function caricaPaesiIniziali() {
    await cercaPaeseRestCountries("US,IR,IL", true);
}

async function cercaPaeseRestCountries(ricerca, usaCodiciMultipli = false) {
    try {
        const contenitore = document.getElementById("apiPaesiDemo");

        contenitore.innerHTML = `
            <p class="muted">Caricamento dati da REST Countries...</p>
        `;

        const valoreNormalizzato = normalizzaRicercaPaese(ricerca);

        let url = "";

        if (usaCodiciMultipli) {
            url = `https://restcountries.com/v3.1/alpha?codes=${encodeURIComponent(valoreNormalizzato)}&fields=name,flags,capital,population,region,subregion,cca2,languages,currencies`;
        } else if (valoreNormalizzato.length === 2) {
            url = `https://restcountries.com/v3.1/alpha?codes=${encodeURIComponent(valoreNormalizzato)}&fields=name,flags,capital,population,region,subregion,cca2,languages,currencies`;
        } else {
            url = `https://restcountries.com/v3.1/name/${encodeURIComponent(valoreNormalizzato)}?fields=name,flags,capital,population,region,subregion,cca2,languages,currencies`;
        }

        const risposta = await fetch(url);

        if (!risposta.ok) {
            throw new Error("Paese non trovato");
        }

        const paesi = await risposta.json();

        mostraRisultatiPaesi(paesi);

    } catch (errore) {
        console.error("Errore REST Countries:", errore);
        mostraErrorePaesi("Nessun Paese trovato. Prova con il nome inglese, es. Croatia, Italy, United States.");
    }
}

function normalizzaRicercaPaese(ricerca) {
    const testo = ricerca.toLowerCase().trim();

    const alias = {
        "italia": "IT",
        "croazia": "HR",
        "stati uniti": "US",
        "usa": "US",
        "america": "US",
        "iran": "IR",
        "israele": "IL",
        "germania": "DE",
        "francia": "FR",
        "svizzera": "CH",
        "turchia": "TR",
        "qatar": "QA",
        "siria": "SY",
        "libano": "LB",
        "egitto": "EG",
        "iraq": "IQ",
        "giordania": "JO",
        "arabia saudita": "SA",
        "emirati arabi uniti": "AE"
    };

    if (alias[testo]) {
        return alias[testo];
    }

    return ricerca;
}

function mostraRisultatiPaesi(paesi) {
    const contenitore = document.getElementById("apiPaesiDemo");
    contenitore.innerHTML = "";

    paesi.forEach(paese => {
        const capitale = paese.capital ? paese.capital[0] : "N/D";
        const popolazione = paese.population
            ? paese.population.toLocaleString("it-IT")
            : "N/D";

        const lingue = paese.languages
            ? Object.values(paese.languages).join(", ")
            : "N/D";

        const valute = paese.currencies
            ? Object.values(paese.currencies).map(valuta => valuta.name).join(", ")
            : "N/D";

        const card = document.createElement("div");
        card.classList.add("api-paese-card");

        card.innerHTML = `
            <img src="${paese.flags.png}" alt="Bandiera ${paese.name.common}">

            <h3>${paese.name.common}</h3>

            <p><strong>Nome ufficiale:</strong> ${paese.name.official}</p>
            <p><strong>Capitale:</strong> ${capitale}</p>
            <p><strong>Popolazione:</strong> ${popolazione}</p>
            <p><strong>Regione:</strong> ${paese.region}</p>
            <p><strong>Sottoregione:</strong> ${paese.subregion || "N/D"}</p>
            <p><strong>Lingue:</strong> ${lingue}</p>
            <p><strong>Valute:</strong> ${valute}</p>
            <p><strong>Codice:</strong> ${paese.cca2}</p>
        `;

        contenitore.appendChild(card);
    });
}

function mostraErrorePaesi(messaggio) {
    const contenitore = document.getElementById("apiPaesiDemo");

    contenitore.innerHTML = `
        <p class="muted">${messaggio}</p>
    `;
}

function inizializzaReliefWeb() {
    const input = document.getElementById("reliefSearchInput");
    const bottone = document.getElementById("reliefSearchBtn");
    const sampleButtons = document.querySelectorAll(".sample-btn");

    bottone.addEventListener("click", () => {
        const ricerca = input.value.trim();

        if (ricerca.length === 0) {
            mostraErroreReliefWeb("Inserisci un Paese o una crisi.");
            return;
        }

        caricaReportReliefWeb(ricerca);
    });

    input.addEventListener("keydown", (evento) => {
        if (evento.key === "Enter") {
            bottone.click();
        }
    });

    sampleButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const query = btn.dataset.query;
            input.value = query;
            caricaReportReliefWeb(query);
        });
    });
}

async function caricaReportReliefWeb(ricerca) {
    const contenitore = document.getElementById("reliefReportsBox");

    contenitore.innerHTML = `
        <p class="muted">Richiedo report umanitari a ReliefWeb...</p>
    `;

    const url = `https://api.reliefweb.int/v2/reports?appname=palantir-lite-school-project&limit=5&preset=latest&query[value]=${encodeURIComponent(ricerca)}&fields[include][]=title&fields[include][]=date.created&fields[include][]=source.name&fields[include][]=country.name&fields[include][]=url`;

    try {
        const risposta = await fetch(url);

        if (!risposta.ok) {
            const messaggio = `Errore ReliefWeb: ${risposta.status} ${risposta.statusText}`;
            throw new Error(messaggio);
        }

        const data = await risposta.json();

        if (!Array.isArray(data)) {
            throw new Error("Risposta inattesa da ReliefWeb");
        }

        if (data.length === 0) {
            mostraErroreReliefWeb(`Nessun report trovato per '${ricerca}'. Prova con Gaza, Ucraina o Iran.`);
            return;
        }

        mostraReportReliefWeb(data);

    } catch (errore) {
        console.error("Errore ReliefWeb:", errore);
        const fallback = fallbackReportsFor(ricerca);
        if (fallback) {
            mostraReportReliefWeb(fallback, `Esempio offline per ${ricerca}. La chiamata a ReliefWeb non è disponibile al momento.`);
            return;
        }
        mostraErroreReliefWeb(errore.message || "Impossibile caricare i report ReliefWeb.");
    }
}

function mostraReportReliefWeb(reports, nota) {
    const contenitore = document.getElementById("reliefReportsBox");
    contenitore.innerHTML = "";

    if (nota) {
        contenitore.innerHTML = `
            <p class="muted">${nota}</p>
        `;
    }

    if (!reports || reports.length === 0) {
        mostraErroreReliefWeb("Nessun report trovato.");
        return;
    }

    reports.forEach(report => {
        const card = document.createElement("div");
        card.classList.add("relief-report-card");

        const dataPulita = report.data
            ? new Date(report.data).toLocaleDateString("it-IT")
            : "N/D";

        card.innerHTML = `
            <h3>${report.titolo}</h3>
            <p><strong>Paese:</strong> ${report.paese}</p>
            <p><strong>Fonte:</strong> ${report.fonte}</p>
            <p><strong>Data:</strong> ${dataPulita}</p>
            ${report.url ? `<a href="${report.url}" target="_blank">Apri report</a>` : ""}
        `;

        contenitore.appendChild(card);
    });
}

function mostraErroreReliefWeb(messaggio) {
    const contenitore = document.getElementById("reliefReportsBox");

    contenitore.innerHTML = `
        <p class="muted">${messaggio}</p>
    `;
}

function fallbackReportsFor(ricerca) {
    const term = ricerca.toLowerCase();

    const fallbackData = {
        gaza: [
            {
                titolo: "Aggiornamento umanitario su Gaza: sfide di accesso" ,
                data: "2025-11-10",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Gaza",
                url: ""
            },
            {
                titolo: "Carenze di acqua e medicine nella Striscia di Gaza",
                data: "2025-11-08",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Gaza",
                url: ""
            }
        ],
        ukraine: [
            {
                titolo: "Report sulle condizioni dei rifugiati in Ucraina orientale",
                data: "2025-10-29",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Ucraina",
                url: ""
            },
            {
                titolo: "Supporto umanitario coordinato per le famiglie ucraine",
                data: "2025-10-20",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Ucraina",
                url: ""
            }
        ],
        iran: [
            {
                titolo: "Analisi delle risposte umanitarie in Iran dopo la siccità",
                data: "2025-09-15",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Iran",
                url: ""
            },
            {
                titolo: "Sforzi di soccorso per le comunità rurali in Iran",
                data: "2025-09-10",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Iran",
                url: ""
            }
        ]
    };

    if (term.includes('gaza')) {
        return fallbackData.gaza;
    }
    if (term.includes('ukraine') || term.includes('ucraina')) {
        return fallbackData.ukraine;
    }
    if (term.includes('iran')) {
        return fallbackData.iran;
    }
    if (term.includes('syria') || term.includes('siria')) {
        return [
            {
                titolo: "Sintesi dei report umanitari sulla Siria",
                data: "2025-10-02",
                fonte: "ReliefWeb (esempio offline)",
                paese: "Syria",
                url: ""
            }
        ];
    }

    return null;
}

async function caricaCyberKnowledge() {
    try {
        const risposta = await fetch("api/get_cyber_knowledge.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento della knowledge base");
        }

        const cyberData = await risposta.json();

        mostraCyberKnowledge(cyberData);

    } catch (errore) {
        console.error("Errore Cyber Knowledge:", errore);
        document.getElementById("cyberKnowledgeBox").innerHTML = `
            <p class="muted">Errore nel caricamento della knowledge base.</p>
        `;
    }
}

function mostraCyberKnowledge(cyberData) {
    const contenitore = document.getElementById("cyberKnowledgeBox");
    contenitore.innerHTML = "";

    if (!cyberData || cyberData.length === 0) {
        contenitore.innerHTML = `
            <p class="muted">Nessun cyberattacco disponibile.</p>
        `;
        return;
    }

    cyberData.forEach(attack => {
        const card = document.createElement("div");
        card.classList.add("cyber-card");
        card.classList.add(`risk-${attack.rischioTecnico.toLowerCase().replace("-", "-")}`);

        const dataPulita = attack.dataEvento
            ? new Date(attack.dataEvento).toLocaleDateString("it-IT")
            : "N/D";

        const contromisureHTML = attack.contromisure
            .map(cm => `<li>${cm}</li>`)
            .join("");

        card.innerHTML = `
            <div class="cyber-card-header">
                <h3>${attack.metodoAttacco.toUpperCase()}</h3>
                <span class="risk-badge risk-${attack.rischioTecnico.toLowerCase().replace("-", "-")}">${attack.rischioTecnico}</span>
            </div>
            <div class="cyber-card-body">
                <p><strong>Categoria:</strong> ${attack.categoria}</p>
                <p><strong>Descrizione:</strong> ${attack.descrizioneMetodo}</p>
                <p><strong>Bersaglio:</strong> ${attack.tipoBersaglio}</p>
                <p><strong>Luogo:</strong> ${attack.luogo}</p>
                <p><strong>Data:</strong> ${dataPulita}</p>
                <p><strong>Livello Impatto:</strong> <span class="impact-badge impact-${attack.livelloImpatto.toLowerCase()}">${attack.livelloImpatto}</span></p>
                <p><strong>Esempi:</strong> ${attack.esempio}</p>
                <div class="cyber-card-countermeasures">
                    <strong>Contromisure:</strong>
                    <ul>
                        ${contromisureHTML}
                    </ul>
                </div>
            </div>
        `;
        contenitore.appendChild(card);
    });
}

async function caricaMitreAttack() {
    try {
        const risposta = await fetch("api/get_mitre_attack.php");

        if (!risposta.ok) {
            throw new Error("Errore nel caricamento delle tecniche MITRE");
        }

        const mitreData = await risposta.json();

        mostraMitreAttack(mitreData);

    } catch (errore) {
        console.error("Errore MITRE ATT&CK:", errore);
        document.getElementById("mitreTechniquesBox").innerHTML = `
            <p class="muted">Errore nel caricamento delle tecniche MITRE.</p>
        `;
    }
}

function mostraMitreAttack(mitreData) {
    const contenitore = document.getElementById("mitreTechniquesBox");
    contenitore.innerHTML = "";

    if (!mitreData || mitreData.length === 0) {
        contenitore.innerHTML = `
            <p class="muted">Nessuna tecnica MITRE disponibile.</p>
        `;
        return;
    }

    const tecnicheUniche = [];
    const tecnicheGiaViste = new Set();

    mitreData.forEach(attack => {
        const chiaveTecnica = attack.tecnicaId || attack.nomeTecnica;

        if (!tecnicheGiaViste.has(chiaveTecnica)) {
            tecnicheGiaViste.add(chiaveTecnica);
            tecnicheUniche.push(attack);
        }
    });

    const righe = tecnicheUniche.map(attack => {
        const dataPulita = attack.dataEvento
            ? new Date(attack.dataEvento).toLocaleDateString("it-IT")
            : "N/D";
        const descrizioneBreve = accorciaTesto(attack.descrizioneTecnica, 95);

        const tatticheHTML = attack.tattiche && attack.tattiche.length > 0
            ? attack.tattiche.map(t => `<span class="mitre-tag">${t}</span>`).join("")
            : `<span class="muted">N/D</span>`;

        const contromisure = attack.contromisure && attack.contromisure.length > 0
            ? attack.contromisure.join(", ")
            : "Non disponibili";

        const fonteClass = attack.fonte.includes("TAXII") ? "mitre-source-live" : "mitre-source-fallback";
        const fonteLabel = attack.fonte.includes("TAXII") ? "API MITRE" : "Fallback";
        const linkMitre = attack.urlMitre
            ? `<a href="${attack.urlMitre}" target="_blank" class="mitre-link">Apri</a>`
            : `<span class="muted">N/D</span>`;

        return `
            <tr>
                <td>
                    <strong>${attack.tecnicaId}</strong>
                    <span class="mitre-method">${attack.metodoAttacco}</span>
                </td>
                <td>
                    <strong>${attack.nomeTecnica}</strong>
                    <span class="mitre-description" title="${escapeHtml(attack.descrizioneTecnica)}">${descrizioneBreve}</span>
                </td>
                <td>${tatticheHTML}</td>
                <td>${contromisure}</td>
                <td>
                    <span class="mitre-source ${fonteClass}">${fonteLabel}</span>
                    ${linkMitre}
                </td>
                <td>
                    <span>${attack.luogo}</span>
                    <span class="mitre-method">${dataPulita}</span>
                </td>
            </tr>
        `;
    }).join("");

    contenitore.innerHTML = `
        <table class="mitre-table">
            <thead>
                <tr>
                    <th>Tecnica</th>
                    <th>Dettaglio MITRE</th>
                    <th>Tattiche</th>
                    <th>Contromisure</th>
                    <th>Fonte</th>
                    <th>Evento</th>
                </tr>
            </thead>
            <tbody>
                ${righe}
            </tbody>
        </table>
    `;

}

function accorciaTesto(testo, limite = 95) {
    const valore = String(testo || "").replace(/\s+/g, " ").trim();

    if (valore.length <= limite) {
        return escapeHtml(valore);
    }

    return escapeHtml(valore.slice(0, limite).trim()) + "...";
}

function inizializzaMappaRischio() {
    const mapObject = document.getElementById("worldRiskMap");
    const tooltip = document.getElementById("worldMapTooltip");
    const list = document.getElementById("worldRiskList");

    if (!mapObject || !tooltip || !list) {
        return;
    }

    const riskByCountry = creaRischioPaesi();
    mostraListaRischioPaesi(riskByCountry, list);

    const renderMap = () => {
        const svgDoc = mapObject.contentDocument;

        if (!svgDoc) {
            list.innerHTML = `<p class="muted">Mappa non disponibile nel browser.</p>`;
            return;
        }

        coloraMappaRischio(svgDoc, riskByCountry, tooltip);
        preparaSvgMappa(svgDoc);
    };

    mapObject.addEventListener("load", renderMap);

    if (mapObject.contentDocument) {
        renderMap();
    }
}

function inizializzaControlliZoomMappa() {
    const mapObject = document.getElementById("worldRiskMap");
    const mapCanvas = document.getElementById("worldMapCanvas");
    const zoomOut = document.getElementById("worldMapZoomOut");
    const zoomIn = document.getElementById("worldMapZoomIn");
    const zoomReset = document.getElementById("worldMapZoomReset");
    const zoomValue = document.getElementById("worldMapZoomValue");

    if (!mapObject || !mapCanvas || !zoomOut || !zoomIn || !zoomReset || !zoomValue) {
        return;
    }

    const zoomState = {
        scale: 1,
        min: 0.75,
        max: 4,
        step: 0.25,
        baseViewBox: { x: 0, y: 0, width: 1009.6727, height: 665.96301 },
        currentViewBox: { x: 0, y: 0, width: 1009.6727, height: 665.96301 }
    };

    const updateZoom = nextScale => {
        const oldCenter = centroViewBox(zoomState.currentViewBox);
        zoomState.scale = Math.max(zoomState.min, Math.min(zoomState.max, nextScale));
        applicaZoomSvg(mapObject, zoomState, oldCenter);
        zoomValue.textContent = `${Math.round(zoomState.scale * 100)}%`;
    };

    zoomOut.addEventListener("click", () => updateZoom(zoomState.scale - zoomState.step));
    zoomIn.addEventListener("click", () => updateZoom(zoomState.scale + zoomState.step));
    zoomReset.addEventListener("click", () => updateZoom(1));

    mapObject.addEventListener("load", () => {
        const svgDoc = mapObject.contentDocument;
        preparaSvgMappa(svgDoc);
        applicaZoomSvg(mapObject, zoomState);
        inizializzaTrascinamentoMappa(svgDoc, mapCanvas, mapObject, zoomState);

        if (svgDoc && !svgDoc.__wheelZoomReady) {
            svgDoc.__wheelZoomReady = true;
            svgDoc.addEventListener("wheel", event => {
                event.preventDefault();
                const direction = event.deltaY > 0 ? -1 : 1;
                updateZoom(zoomState.scale + direction * zoomState.step);
            }, { passive: false });
        }
    });
}

function preparaSvgMappa(svgDoc) {
    const svg = svgDoc ? svgDoc.querySelector("svg") : null;

    if (!svg) {
        return;
    }

    svg.setAttribute("viewBox", "0 0 1009.6727 665.96301");
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
    svg.style.width = "100%";
    svg.style.height = "100%";
}

function applicaZoomSvg(mapObject, zoomState, center) {
    const svgDoc = mapObject.contentDocument;
    const svg = svgDoc ? svgDoc.querySelector("svg") : null;

    if (!svg) {
        return;
    }

    const base = zoomState.baseViewBox;
    const width = base.width / zoomState.scale;
    const height = base.height / zoomState.scale;
    const currentCenter = center || centroViewBox(zoomState.currentViewBox) || centroViewBox(base);
    const requested = {
        x: currentCenter.x - width / 2,
        y: currentCenter.y - height / 2,
        width,
        height
    };
    const nextViewBox = limitaViewBox(requested, base);

    zoomState.currentViewBox = nextViewBox;
    svg.setAttribute("viewBox", `${nextViewBox.x} ${nextViewBox.y} ${nextViewBox.width} ${nextViewBox.height}`);
}

function inizializzaTrascinamentoMappa(svgDoc, mapCanvas, mapObject, zoomState) {
    const svg = svgDoc ? svgDoc.querySelector("svg") : null;

    if (!svg || svgDoc.__dragPanReady) {
        return;
    }

    svgDoc.__dragPanReady = true;
    let dragStart = null;

    svg.addEventListener("pointerdown", event => {
        dragStart = {
            clientX: event.clientX,
            clientY: event.clientY,
            viewBox: { ...zoomState.currentViewBox }
        };
        mapCanvas.classList.add("is-dragging");
        svg.setPointerCapture(event.pointerId);
    });

    svg.addEventListener("pointermove", event => {
        if (!dragStart) {
            return;
        }

        event.preventDefault();
        const rect = mapObject.getBoundingClientRect();
        const dx = ((event.clientX - dragStart.clientX) / rect.width) * dragStart.viewBox.width;
        const dy = ((event.clientY - dragStart.clientY) / rect.height) * dragStart.viewBox.height;
        const requested = {
            ...dragStart.viewBox,
            x: dragStart.viewBox.x - dx,
            y: dragStart.viewBox.y - dy
        };
        const nextViewBox = limitaViewBox(requested, zoomState.baseViewBox);

        zoomState.currentViewBox = nextViewBox;
        svg.setAttribute("viewBox", `${nextViewBox.x} ${nextViewBox.y} ${nextViewBox.width} ${nextViewBox.height}`);
    });

    const endDrag = event => {
        if (!dragStart) {
            return;
        }

        dragStart = null;
        mapCanvas.classList.remove("is-dragging");

        if (event && svg.hasPointerCapture(event.pointerId)) {
            svg.releasePointerCapture(event.pointerId);
        }
    };

    svg.addEventListener("pointerup", endDrag);
    svg.addEventListener("pointercancel", endDrag);
    svg.addEventListener("mouseleave", endDrag);
}

function centroViewBox(viewBox) {
    if (!viewBox) {
        return null;
    }

    return {
        x: viewBox.x + viewBox.width / 2,
        y: viewBox.y + viewBox.height / 2
    };
}

function limitaViewBox(viewBox, base) {
    if (viewBox.width >= base.width || viewBox.height >= base.height) {
        return {
            x: base.x + (base.width - viewBox.width) / 2,
            y: base.y + (base.height - viewBox.height) / 2,
            width: viewBox.width,
            height: viewBox.height
        };
    }

    return {
        x: Math.max(base.x, Math.min(viewBox.x, base.x + base.width - viewBox.width)),
        y: Math.max(base.y, Math.min(viewBox.y, base.y + base.height - viewBox.height)),
        width: viewBox.width,
        height: viewBox.height
    };
}

function creaRischioPaesi() {
    const rischio = new Map();

    coinvolgimenti.forEach(coinvolgimento => {
        const evento = eventi.find(item =>
            Number(item.idEvento) === Number(coinvolgimento.idEvento)
        );

        if (!evento) {
            return;
        }

        const codice = codicePaeseDaNome(coinvolgimento.nomePaese);

        if (!codice) {
            return;
        }

        const livello = normalizzaLivelloRischio(evento.livelloGravita);
        const vittime = Number(evento.numeroStimatoVittime) || 0;

        if (!rischio.has(codice)) {
            rischio.set(codice, {
                codice,
                nome: coinvolgimento.nomePaese,
                livello,
                vittime: 0,
                eventi: [],
                ruoli: new Set(),
                aree: new Set()
            });
        }

        const paese = rischio.get(codice);
        paese.livello = livelloPiuAlto(paese.livello, livello);
        paese.vittime += vittime;
        paese.ruoli.add(coinvolgimento.ruoloNelEvento || coinvolgimento.ruoloConflitto || "Coinvolto");

        if (coinvolgimento.areaGeografica) {
            paese.aree.add(coinvolgimento.areaGeografica);
        }

        paese.eventi.push({
            luogo: evento.luogo,
            tipologia: evento.tipologia,
            descrizione: evento.descrizione,
            fonte: evento.fonte,
            statoEvento: evento.livelloGravita,
            latitudine: evento.latitudine,
            longitudine: evento.longitudine,
            livello
        });
    });

    if (rischio.size === 0) {
        datiRischioFallback().forEach(paese => {
            rischio.set(paese.codice, paese);
        });
    }

    return Array.from(rischio.values()).sort((a, b) => {
        const diff = pesoLivelloRischio(b.livello) - pesoLivelloRischio(a.livello);
        return diff || a.nome.localeCompare(b.nome);
    });
}

function coloraMappaRischio(svgDoc, riskByCountry, tooltip) {
    const countries = svgDoc.querySelectorAll("path");
    const riskMap = new Map(riskByCountry.map(paese => [paese.codice, paese]));

    countries.forEach(country => {
        country.style.fill = "#1e293b";
        country.style.stroke = "#0f172a";
        country.style.strokeWidth = "0.8";
        country.style.transition = "fill 0.2s ease, filter 0.2s ease, stroke 0.2s ease";
        country.style.cursor = "default";
        country.style.opacity = "0.9";

        const info = riskMap.get(country.id);

        if (!info) {
            return;
        }

        country.style.fill = coloreRischio(info.livello);
        country.style.cursor = "pointer";
        country.style.opacity = "1";
        aggiornaAttributiSvgPaese(country, info);

        country.addEventListener("mouseenter", event => {
            country.style.filter = "brightness(1.18)";
            country.style.stroke = "#f8fafc";
            country.style.strokeWidth = "1.3";
            tooltip.style.display = "block";
            tooltip.innerHTML = creaTooltipRischio(info);
            posizionaTooltipDestra(event, tooltip);
        });

        country.addEventListener("mousemove", event => {
            posizionaTooltipDestra(event, tooltip);
        });

        country.addEventListener("mouseleave", () => {
            country.style.filter = "none";
            country.style.stroke = "#0f172a";
            country.style.strokeWidth = "0.8";
            tooltip.style.display = "none";
        });
    });
}

function mostraListaRischioPaesi(riskByCountry, list) {
    if (riskByCountry.length === 0) {
        list.innerHTML = `<p class="muted">Nessun Paese in pericolo rilevato.</p>`;
        return;
    }

    list.innerHTML = riskByCountry.map(paese => `
        <div class="world-risk-item risk-${classeLivelloRischio(paese.livello)}">
            <strong>${escapeHtml(paese.nome)}</strong>
            <span class="world-risk-badge risk-${classeLivelloRischio(paese.livello)}">${escapeHtml(paese.livello)}</span>
            <span>${paese.eventi.length} eventi collegati</span>
            <span>Vittime stimate: ${paese.vittime}</span>
        </div>
    `).join("");
}

function creaTooltipRischio(info) {
    const eventoPrincipale = info.eventi[0] || {};
    const statoEvento = eventoPrincipale.statoEvento ?? info.livello ?? "N/D";
    const latitudine = formattaCoordinata(eventoPrincipale.latitudine);
    const longitudine = formattaCoordinata(eventoPrincipale.longitudine);

    return `
        <p><strong>Stato dell'evento:</strong> ${escapeHtml(statoEvento)}</p>
        <p><strong>Vittime stimate:</strong> ${info.vittime}</p>
        <p><strong>Latitudine:</strong> ${latitudine}</p>
        <p><strong>Longitudine:</strong> ${longitudine}</p>
    `;
}

function aggiornaAttributiSvgPaese(country, info) {
    const eventoPrincipale = info.eventi[0] || {};
    const latitudine = formattaCoordinata(eventoPrincipale.latitudine);
    const longitudine = formattaCoordinata(eventoPrincipale.longitudine);
    const statoEvento = eventoPrincipale.statoEvento ?? info.livello ?? "N/D";
    const label = `${info.nome} - stato ${statoEvento}, vittime stimate ${info.vittime}, latitudine ${latitudine}, longitudine ${longitudine}`;

    country.setAttribute("data-lat", latitudine);
    country.setAttribute("data-lng", longitudine);
    country.setAttribute("title", label);
    country.setAttribute("aria-label", label);
}

function posizionaTooltipDestra(event, tooltip) {
    const offset = 18;
    const margin = 12;
    const rightPosition = event.clientX + offset;
    const fallbackLeft = event.clientX - tooltip.offsetWidth - offset;
    const left = rightPosition + tooltip.offsetWidth <= window.innerWidth - margin
        ? rightPosition
        : Math.max(margin, fallbackLeft);
    const top = Math.max(
        margin,
        Math.min(event.clientY + offset, window.innerHeight - tooltip.offsetHeight - margin)
    );

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
}

function formattaCoordinata(coordinata) {
    if (coordinata === undefined || coordinata === null || coordinata === "") {
        return "N/D";
    }

    const valore = Number(coordinata);

    if (Number.isNaN(valore)) {
        return "N/D";
    }

    return valore.toFixed(4);
}

function codicePaeseDaNome(nomePaese) {
    if (!nomePaese) {
        return null;
    }

    const nome = nomePaese.toLowerCase().trim();
    const alias = {
        "afghanistan": "AF",
        "arabia saudita": "SA",
        "china": "CN",
        "cina": "CN",
        "egitto": "EG",
        "emirati arabi uniti": "AE",
        "francia": "FR",
        "germania": "DE",
        "giordania": "JO",
        "iran": "IR",
        "iraq": "IQ",
        "israel": "IL",
        "israele": "IL",
        "italia": "IT",
        "lebanon": "LB",
        "libano": "LB",
        "palestina": "PS",
        "palestine": "PS",
        "qatar": "QA",
        "regno unito": "GB",
        "russia": "RU",
        "siria": "SY",
        "stati uniti": "US",
        "stati uniti d'america": "US",
        "syria": "SY",
        "turchia": "TR",
        "ucraina": "UA",
        "ukraine": "UA",
        "united kingdom": "GB",
        "united states": "US",
        "usa": "US"
    };

    return alias[nome] || null;
}

function normalizzaLivelloRischio(livello) {
    const valore = String(livello || "").toLowerCase();

    if (valore.includes("critic")) {
        return "critico";
    }
    if (valore.includes("alto")) {
        return "alto";
    }
    if (valore.includes("medio")) {
        return "medio";
    }
    if (valore.includes("basso")) {
        return "basso";
    }

    return "medio";
}

function livelloPiuAlto(attuale, nuovo) {
    return pesoLivelloRischio(nuovo) > pesoLivelloRischio(attuale) ? nuovo : attuale;
}

function pesoLivelloRischio(livello) {
    const pesi = {
        basso: 1,
        medio: 2,
        alto: 3,
        critico: 4
    };

    return pesi[normalizzaLivelloRischio(livello)] || 0;
}

function coloreRischio(livello) {
    const colori = {
        basso: "#facc15",
        medio: "#fb923c",
        alto: "#ef4444",
        critico: "#991b1b"
    };

    return colori[normalizzaLivelloRischio(livello)] || "#fb923c";
}

function classeLivelloRischio(livello) {
    const classi = {
        basso: "low",
        medio: "medium",
        alto: "high",
        critico: "critical"
    };

    return classi[normalizzaLivelloRischio(livello)] || "medium";
}

function datiRischioFallback() {
    return [
        {
            codice: "IR",
            nome: "Iran",
            livello: "critico",
            vittime: 120,
            eventi: [{ luogo: "Iran", tipologia: "Militare/Cyber", fonte: "Fallback locale", statoEvento: "critico", latitudine: 35.6892, longitudine: 51.3890 }],
            ruoli: new Set(["Area critica"]),
            aree: new Set(["Medio Oriente"])
        },
        {
            codice: "IL",
            nome: "Israele",
            livello: "critico",
            vittime: 85,
            eventi: [{ luogo: "Israele", tipologia: "Militare/Cyber", fonte: "Fallback locale", statoEvento: "critico", latitudine: 31.7683, longitudine: 35.2137 }],
            ruoli: new Set(["Area critica"]),
            aree: new Set(["Medio Oriente"])
        },
        {
            codice: "SY",
            nome: "Siria",
            livello: "alto",
            vittime: 45,
            eventi: [{ luogo: "Siria", tipologia: "Umanitaria", fonte: "Fallback locale", statoEvento: "alto", latitudine: 33.5138, longitudine: 36.2765 }],
            ruoli: new Set(["Crisi umanitaria"]),
            aree: new Set(["Medio Oriente"])
        },
        {
            codice: "UA",
            nome: "Ucraina",
            livello: "alto",
            vittime: 40,
            eventi: [{ luogo: "Ucraina", tipologia: "Militare", fonte: "Fallback locale", statoEvento: "alto", latitudine: 50.4501, longitudine: 30.5234 }],
            ruoli: new Set(["Conflitto attivo"]),
            aree: new Set(["Europa orientale"])
        }
    ];
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
