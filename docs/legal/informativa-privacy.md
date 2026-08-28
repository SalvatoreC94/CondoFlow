# Informativa privacy — CondoFlow

> ⚠️ **BOZZA — non è consulenza legale.** Questo documento è un punto di partenza tecnico, scritto sulla base di come l'applicazione tratta realmente i dati (vedi `ARCHITECTURE.md`), non un'informativa pronta per la pubblicazione. **Deve essere rivista, integrata e validata da un avvocato o un DPO** prima di essere pubblicata o resa vincolante — in particolare i campi tra `[…]`, i tempi di conservazione, le basi giuridiche puntuali e i sub-responsabili effettivamente in uso vanno confermati caso per caso in base a come il servizio viene concretamente offerto (hosting scelto, eventuali fornitori terzi, giurisdizione).

## 1. Titolare del trattamento

**[Ragione sociale / nome del titolare]**
[Indirizzo]
[P.IVA / C.F.]
[Email di contatto privacy]

## 2. Cos'è CondoFlow e chi tratta i dati

CondoFlow è un servizio SaaS (Software-as-a-Service) che l'amministratore di condominio usa per gestire condomini, unità immobiliari, condòmini, segnalazioni, comunicazioni, documenti, contabilità e assemblee. Il fornitore del servizio (di seguito "CondoFlow", "noi") ospita l'infrastruttura e fornisce l'applicazione; l'amministratore di condominio (il cliente che si abbona) resta **titolare del trattamento** dei dati dei propri condòmini/residenti — CondoFlow agisce come **responsabile del trattamento** per questi dati, secondo i termini dell'[Accordo di trattamento dati](accordo-trattamento-dati.md) stipulato con ogni amministratore-cliente.

Questa informativa copre invece il trattamento che CondoFlow effettua **come titolare autonomo**: i dati dell'amministratore-cliente stesso (account, fatturazione dell'abbonamento) e l'uso tecnico della piattaforma.

## 3. Dati trattati

| Categoria | Esempi | Chi li fornisce |
|---|---|---|
| Dati account amministratore | Nome, email, numero di cellulare, password (con hash) | L'amministratore, alla registrazione/invito |
| Dati di abbonamento | Piano, stato (prova/attivo/sospeso), scadenza | Generati dal sistema o inseriti dal team CondoFlow |
| Dati tecnici | Indirizzo IP, user agent, log applicativi | Raccolti automaticamente durante l'uso |
| Dati dei condòmini/residenti | Nome, contatto, unità di appartenenza, segnalazioni, documenti, quote condominiali | Caricati dall'amministratore-cliente — vedi [Accordo di trattamento dati](accordo-trattamento-dati.md) |

## 4. Finalità e base giuridica

| Finalità | Base giuridica |
|---|---|
| Erogazione del servizio (account, funzionalità dell'app) | Esecuzione del contratto (art. 6.1.b GDPR) |
| Fatturazione e gestione dell'abbonamento SaaS | Esecuzione del contratto / obblighi legali (fatturazione) |
| Supporto tecnico e assistenza | Esecuzione del contratto / legittimo interesse |
| Sicurezza, prevenzione frodi, log di accesso | Legittimo interesse (art. 6.1.f GDPR) |
| Comunicazioni di servizio (es. scadenza abbonamento) | Esecuzione del contratto |
| [Comunicazioni commerciali/marketing, se previste] | [Consenso — solo se effettivamente previste] |

## 5. Accesso ai dati da parte del personale CondoFlow

Il team CondoFlow può accedere ai dati dell'account tramite un pannello di gestione interno (`/platform`), **separato** dall'applicazione usata quotidianamente dall'amministratore e dai condòmini, per le seguenti finalità:

- assistenza e supporto tecnico su richiesta del cliente;
- gestione dello stato dell'abbonamento (attivazione, rinnovo, sospensione);
- manutenzione dei dati di riferimento condivisi (es. categorie di segnalazione/documento);
- diagnosi e risoluzione di problemi tecnici.

Questo accesso **non comprende** la lettura ordinaria di segnalazioni, documenti, contabilità o dati dei singoli condòmini/residenti — il pannello interno espone solo dati amministrativi dell'account (nome, contatto, stato abbonamento) e metadati sui condomini gestiti (nome, indirizzo, conteggio unità), non il contenuto operativo del servizio.

**Ogni accesso e ogni azione del personale CondoFlow su questo pannello è registrato** (chi, cosa, quando) in un log di controllo interno, consultabile su richiesta per finalità di accountability (rendicontazione) verso il Garante o il cliente.

## 6. Conservazione dei dati

[Definire i tempi effettivi: es. per la durata del contratto + N anni per obblighi fiscali/contabili; cancellazione o anonimizzazione entro N giorni dalla disdetta, salvo obblighi di legge.]

## 7. Sub-responsabili e trasferimenti

[Elencare i fornitori terzi effettivamente usati: hosting (specificare paese/regione del datacenter), provider email transazionali, eventuale CDN. Se qualcuno è extra-UE, indicare la garanzia adottata — Clausole Contrattuali Standard, adeguatezza, ecc.]

## 8. Diritti dell'interessato

Gli utenti (amministratori, condòmini, custodi) possono esercitare in ogni momento i diritti previsti dagli artt. 15-22 GDPR (accesso, rettifica, cancellazione, limitazione, portabilità, opposizione) scrivendo a **[email privacy]**. È inoltre possibile proporre reclamo al Garante per la Protezione dei Dati Personali (www.garanteprivacy.it).

## 9. Misure di sicurezza (sintesi tecnica)

- Autenticazione basata su sessione (Laravel Sanctum), password sempre salvate con hash, mai in chiaro
- Isolamento dei dati tra condomini/tenant a livello di autorizzazione applicativa (Policy), verificato da test automatici anti-IDOR
- Il pannello di gestione interno (`/platform`) usa un sistema di autenticazione **separato** da quello dei clienti, con log di ogni accesso e azione (vedi punto 5)
- Download di documenti/allegati sempre autenticato, mai tramite link pubblici diretti
- [Aggiungere: cifratura in transito (HTTPS/TLS), politiche di backup, eventuale cifratura a riposo, retention dei log]

---

*Ultimo aggiornamento bozza: da compilare alla pubblicazione.*
